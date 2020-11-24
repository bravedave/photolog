<?php
/*
 * David Bray
 * BrayWorth Pty Ltd
 * e. david@brayworth.com.au
 *
 * MIT License
 *
*/

namespace photolog\dao;

use CallbackFilterIterator;
use FilesystemIterator;
use strings;

use dao\_dao;
use photolog\config;

class property_photolog extends _dao {
	protected $_db_name = 'property_photolog';

	protected function dirSize( $path) {
		$io = popen( '/usr/bin/du -sk ' . $path, 'r' );
		$size = fgets( $io, 4096);
		$size = substr ( $size, 0, strpos ( $size, "\t" ) );
		pclose ( $io );
		//~ echo 'Directory: ' . $f . ' => Size: ' . $size;

		return $size;

	}

	protected function _dtoExpand( $dto) {
		$path = $this->store( $dto->id);

		$dto->files = (object)[
			'processed' => 0,
			'queued' => 0,
			'errors' => 0,
			'total' => 0,
			'dirSize' => 0,

		];

		if ( is_dir( $path)) {
			$qpath = $path . '/queue';
			$dirModTime = is_dir( $qpath) ?
				$dirModTime = max( filemtime( $path), filemtime( $qpath)) :
				filemtime( $path);

			if ( $dirModTime > strtotime( $dto->dirModTime)) {
				$dto->files->dirSize = $this->dirSize( $path);
				//~ \sys::logger( sprintf( 'dirSize : %s %s : %s', $path, date( 'Y-m-d H:i:s', $dirModTime), $dto->files->dirSize));

				$files = new FilesystemIterator( $path, FilesystemIterator::SKIP_DOTS);
				$filter= new CallbackFilterIterator($files, function($cur, $key, $iter) {
					if ( '_info.json' == $cur->getFilename()) return false;
					return $cur->isFile();

				});

				$i = iterator_count( $filter);
				$dto->files->processed += $i;
				$dto->files->total += $i;

				if ( is_dir( $qpath)) {
					$errors= 0;
					$files = new FilesystemIterator( $qpath, FilesystemIterator::SKIP_DOTS);
					$filter= new CallbackFilterIterator($files, function($cur, $key, $iter) use ( &$errors) {
						//~ \sys::logger( sprintf( 'error : %s == err', $cur->getExtension()));
						if ( $cur->getExtension() == 'err') {
							$errors ++;
							return false;

						}
						else {
							if ( preg_match( '@(jp[e]?g|png)$@i', $cur->getExtension())) {
								if ( 10 > $cur->getSize()) {
									$errors ++;

								}

							}

						}

						return $cur->isFile();

					});

					$i = iterator_count( $filter);
					$dto->files->queued += $i;
					$dto->files->errors += $errors;
					$dto->files->total += $i;

					//~ \sys::logger( sprintf( 'errors %s', $errors));

				}

				//~ \sys::logger( sprintf( '%s : %s : %s', $path, \db::dbTimeStamp(), json_encode( [ 'dirModTime' => date( 'Y-m-d H:i:s', $dirModTime), 'dirStats' => json_encode( $dto->files)])));

				$this->UpdateByID( [
					'dirModTime' => date( 'Y-m-d H:i:s', $dirModTime),
					'dirStats' => json_encode( $dto->files)

				], $dto->id);
				//~ \sys::logger( sprintf( 'could NOT use cache : %s %s %s', $path, date( 'Y-m-d H:i:s', $dirModTime), $dto->dirModTime));

			}
			else {
				$dto->files = json_decode( $dto->dirStats);
				if ( !isset( $dto->files->errors)) {
					$dto->files->errors = 0;

				}
				//~ \sys::logger( sprintf( 'could use cache : %s %s : %s', $path, $dto->dirModTime, $cache->dirSize));

			}

		}

		//~ \sys::logger( sprintf( '%s : %d/%d : %d', $path, $dto->files->processed, $dto->files->queued, $dto->files->total));

		return ( $dto);

	}

	protected function _dtoSet( $res) {
		return $res->dtoSet( function( $dto) {
			return $this->_dtoExpand( $dto);

		});

	}

  protected function _getInfoFile( \dao\dto\dto $dto) : string {
    return implode( DIRECTORY_SEPARATOR, [
      $this->store( $dto->id),
      '_info.json'

    ]);

	}

  protected function _getInfo( \dao\dto\dto $dto) : object {
    if ( $path = realpath( $this->_getInfoFile( $dto))) {
      if ( file_exists( $path)) {
        return (object)json_decode( file_get_contents( $path));

      }

    }

    return (object)[];

  }

  protected function _setInfo( \dao\dto\dto $dto, object $info) {
    $this->store( $dto->id, $create = true);
    if ( $path = $this->_getInfoFile( $dto)) {
      \file_put_contents( $path, json_encode( $info, JSON_PRETTY_PRINT));

    }

  }

	public function getByID( $id) {
		if ( $dto = parent::getByID( $id)) {
			$dto = $this->_dtoExpand( $dto);

			$dao = new properties;
			$dto->address_street = $dao->getFieldByID( $dto->property_id, 'address_street');

		}

		return $dto;

	}

  public function getFiles( \dao\dto\dto $dto, string $route) : array {

    $files = [];
    $path = $this->store( $dto->id);
    $info = $this->_getInfo( $dto);
    if ( is_dir( $path)) {
      $_files = new FilesystemIterator( $path);
      foreach($_files as $file) {
        if ('_info.json' == $file->getFilename()) continue;

        if ( preg_match( '@(jp[e]?g|png|mov|mp4|pdf)$@i', $file->getExtension())) {
          $location = '';
          $fileName = $file->getFilename();
          if ( isset( $info->{$fileName})) {
            $fileInfo = (object)$info->{$fileName};
            if ( isset( $fileInfo->location)) {
              $location = (string)$fileInfo->location;

            }

          }

          $files[] = (object)[
            'description' => $fileName,
            'extension' => $file->getExtension(),
            'url' => strings::url( sprintf( '%s/img/%d?img=%s&t=%s', $route, $dto->id, urlencode( $file->getFilename()), $file->getMTime())),
            'error' => false,
            'size' => $file->getSize(),
            'location' => $location,

          ];

        }

      }

      if ( is_dir( $queue = $path . '/queue')) {
        $_files = new FilesystemIterator( $queue);
        foreach($_files as $file) {
          if ( preg_match( '@(png|jp[e]?g)$@i', $file->getExtension())) {

            $parts = pathinfo( $file->getRealpath());
            $errfile = sprintf( '%s/%s.err',
              $parts['dirname'],
              $parts['filename']

            );


            $location = '';
            $fileName = $file->getFilename();
            if ( isset( $info->{$fileName})) {
              $fileInfo = (object)$info->{$fileName};
              if ( isset( $fileInfo->location)) {
                $location = (string)$fileInfo->location;

              }

            }

            $files[] = (object)[
              'description' => $fileName,
              'extension' => $file->getExtension(),
              'url' => strings::url( sprintf( '%s/img/%d?img=%s', $route, $dto->id, urlencode( $file->getFilename()))),
              'error' => file_exists( $errfile) || 10 > $file->getSize(),
              'size' => $file->getSize(),
              'location' => $location,

            ];

          }

        }

      }

    }

    return $files;

  }

	public function getForProperty( $pid) {
		$sql = sprintf(
			'SELECT
				photolog.id,
				photolog.date,
				photolog.property_id,
				prop.address_street,
				photolog.subject,
				photolog.updated,
				photolog.dirModTime,
				photolog.dirStats
			FROM
				`%s` photolog
				LEFT JOIN
					properties prop ON prop.id = photolog.property_id
			WHERE
				photolog.property_id = %d
			ORDER BY
				`date` DESC' ,
				$this->db_name(),
				$pid

		);

		//~ return $this->Result( $sql);
		if ( $res = $this->Result( $sql)) {
			return $this->_dtoSet( $res);

		}

		return [];

	}

	public function getPropertySummary() {
		$debug = false;
		//~ $debug = true;
		$timer = false;
		//~ $timer = \Application::timer();

		/**
		 *  SQLite compatible statement
		 */

		$ai = 'sqlite' == config::$DB_TYPE ? '' : 'AUTO_INCREMENT';
		$this->Q( sprintf(
			'CREATE TEMPORARY TABLE _t(
				`id` INT PRIMARY KEY %s,
				property_id INT,
				address_street TEXT,
				address_suburb TEXT,
				street_index TEXT,
				entries INT)', $ai));

		$sql = sprintf(
			'INSERT INTO _t(
				`property_id`,
				`address_street`,
				`address_suburb`,
				`street_index`,
				`entries`
			)
			SELECT
				pl.property_id,
				prop.address_street,
				prop.address_suburb,
				prop.street_index,
				count( *) entries
			FROM
				`%s` pl
				LEFT JOIN
					properties prop ON prop.id = pl.property_id
			GROUP BY pl.property_id',
			$this->db_name()

		);


		$this->Q( $sql);

		if ( $res = $this->Result( 'SELECT id, address_street, street_index FROM _t')) {
			$res->dtoSet( function( $dto) {
				if ( !$dto->street_index) {
					$this->db->Update( '_t',
						['street_index' => strings::street_index( $dto->address_street)],
						sprintf( 'WHERE id = %d', $dto->id),
						$flush = false
					);

				}

			});

			$sql = 'SELECT * FROM _t ORDER BY address_suburb, street_index, address_street';

		}

		if ( $res = $this->Result( $sql)) {
			return $res->dtoSet( function( $dto) use ( $timer) {
				$props = $this->getForProperty( $dto->property_id);
				$dto->files = (object)[
					'processed' => 0,
					'queued' => 0,
					'errors' => 0,
					'total' => 0,
					'dirSize' => 0

				];

				foreach ( $props as $prop) {
					$dto->files->processed += $prop->files->processed;
					$dto->files->queued += $prop->files->queued;
					if ( isset( $prop->files->errors)) $dto->files->errors += $prop->files->errors;
					$dto->files->total += $prop->files->total;
					$dto->files->dirSize += $prop->files->dirSize;

				}

				if ( $timer) \sys::logger(
					sprintf(
						'<getDirDetail : %s : %ss> %s',
						$dto->address_street,
						$timer->elapsed(),
						__METHOD__

					)

				);

				return ( $dto);

			});

		}

		return [];

	}

	public function getRecent() {
		$sql = sprintf(
		'SELECT
				photolog.id,
				photolog.date,
				photolog.property_id,
				prop.address_street,
				photolog.subject,
				photolog.updated
			FROM
				`%s` photolog
				LEFT JOIN
					properties prop ON prop.id = photolog.property_id
			ORDER BY
				date DESC
			LIMIT 20',
			$this->db_name()

		);


		if ( $res = $this->Result( $sql)) {
			return $this->_dtoSet( $res);

		}

		return [];

  }

  public function getImageInfo( \dao\dto\dto $dto, string $file) : object {
    if ( $json = $this->_getInfo( $dto)) {
      if ( isset( $json->{$file})) {
        return (object)$json->{$file};

      }

    }

    return (object)[];

  }

	public function Insert( $a) {
		$a[ 'created'] = $a['updated'] = self::dbTimeStamp();
		return parent::Insert( $a);

	}

  public function setImageInfo( \dao\dto\dto $dto, string $file, object $info) {
    if ( $json = $this->_getInfo( $dto)) {
			if ( isset( $info->location) && $info->location) {
				// must be unique
				foreach ( $json as $k => $o) {
					if ( isset( $o->location)) {
						if ( $info->location == $o->location) {
							$json->{$k}->location = '';

						}

					}

				}

			}

      $json->{$file} = $info;
      $this->_setInfo( $dto, $json);

    }
    else {
      $this->_setInfo( $dto, (object)[
        $file => $json

      ]);

    }

  }

	public function store( int $id, bool $create = false) {
    $path = sprintf( '%s%d', config::photologStore(), (int)$id);

    if ( $create && !is_dir( $path)) {
      mkdir( $path, 0777);
      chmod( $path, 0777);

    }

    return $path;

	}

	public function UpdateByID( $a, $id) {
		$a['updated'] = self::dbTimeStamp();
		return parent::UpdateByID( $a, $id);

	}

}
