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

use cms;
use CallbackFilterIterator, FilesystemIterator;
use strings;

use bravedave\dvc\{dao, dto, dtoSet, logger};
use cms\currentUser;
use photolog\{config, DiskFileStorage};

class property_photolog extends dao {
	protected $_db_name = 'property_photolog';

	protected function dirSize($path) {
		$io = popen('/usr/bin/du -sk ' . $path, 'r');
		$size = fgets($io, 4096);
		$size = substr($size, 0, strpos($size, "\t"));
		pclose($io);
		//~ echo 'Directory: ' . $f . ' => Size: ' . $size;

		return $size;
	}

	protected function _dtoExpand($dto) {
		$debug = false;
		// $debug = true;
		// $debug = currentUser::isDavid();

		$dto->files = (object)[
			'processed' => 0,
			'queued' => 0,
			'errors' => 0,
			'total' => 0,
			'dirSize' => 0,
		];

		$storage = $this->DiskFileStorage($dto->id, $create = false);
		if ($storage->isValid()) {

			if ($debug) logger::info(sprintf('<valid storage %s> %s', $storage->getPath(), __METHOD__));
			$Qstorage = $storage->subFolder('queue', $create = false);

			$dirModTime = max($Qstorage->modified(), $storage->modified());
			if ($dirModTime > strtotime($dto->dirModTime)) {

				$dto->files->dirSize = $this->dirSize($storage->getPath());

				$files = new FilesystemIterator($storage->getPath(), FilesystemIterator::SKIP_DOTS);
				$filter = new CallbackFilterIterator($files, function ($cur, $key, $iter) {

					if ('_info.json' == $cur->getFilename()) return false;
					if (preg_match('/\-prestamp$/', $cur->getFilename())) return false;
					return $cur->isFile();
				});

				$i = iterator_count($filter);
				$dto->files->processed += $i;
				$dto->files->total += $i;
				if ($Qstorage->isValid()) {

					$errors = 0;
					$files = new FilesystemIterator($Qstorage->getPath(), FilesystemIterator::SKIP_DOTS);
					$filter = new CallbackFilterIterator($files, function ($cur, $key, $iter) use (&$errors) {

						if ('.upload-in-progress' == $cur->getFilename()) return false;
						if ($cur->getExtension() == 'err') {

							$errors++;
							return false;
						} else {

							if (preg_match('@(jp[e]?g|png)$@i', $cur->getExtension())) {

								if (10 > $cur->getSize()) $errors++;
							}
						}

						return $cur->isFile();
					});

					$i = iterator_count($filter);
					$dto->files->queued += $i;
					$dto->files->errors += $errors;
					$dto->files->total += $i;
				}

				$this->UpdateByID([
					'dirModTime' => date('Y-m-d H:i:s', $dirModTime),
					'dirStats' => json_encode($dto->files)
				], $dto->id);

				if ($debug) logger::debug(sprintf(
					'<could NOT use cache : %s %s %s> %s',
					$storage->getPath(),
					date('Y-m-d H:i:s', $dirModTime),
					$dto->dirModTime,
					__METHOD__
				));
			} else {

				$dto->files = json_decode($dto->dirStats);
				if (!isset($dto->files->errors)) $dto->files->errors = 0;
				if ($debug) logger::debug(sprintf('<using cache> <%s | %s> %s', date('Y-m-d', $dirModTime), $dto->dirModTime, __METHOD__));
			}
		}

		return $dto;
	}

	protected function _dtoSet($res) {

		return $res->dtoSet(fn ($dto) => $this->_dtoExpand($dto));
	}

	protected function _getInfoFile(dto $dto): string {

		return implode(DIRECTORY_SEPARATOR, [
			$this->store($dto->id),
			'_info.json'
		]);
	}

	protected function _getInfo(dto $dto): object {

		if ($path = realpath($this->_getInfoFile($dto))) {

			if (file_exists($path)) {

				return (object)json_decode(file_get_contents($path));
			}
		}

		return (object)[];
	}

	protected function _setInfo(dto $dto, object $info) {

		$this->store($dto->id, $create = true);
		if ($path = $this->_getInfoFile($dto)) {

			if (file_exists($path)) @unlink($path);	// avoid updating linked data
			file_put_contents($path, json_encode($info, JSON_PRETTY_PRINT));
		}
	}

	public function getByID($id) {

		if ($dto = parent::getByID($id)) {

			$dto = $this->_dtoExpand($dto);

			$dao = new properties;
			$dto->address_street = $dao->getFieldByID($dto->property_id, 'address_street');
		}

		return $dto;
	}

	public function getFiles(dto $dto, string $route): array {

		$files = [];
		$path = $this->store($dto->id);
		$info = $this->_getInfo($dto);
		if (is_dir($path)) {

			$_files = new FilesystemIterator($path);
			foreach ($_files as $file) {
				if ('_info.json' == $file->getFilename()) continue;

				if (preg_match('@(jp[e]?g|png|mov|mp4|pdf)$@i', $file->getExtension())) {
					$location = '';
					$fileName = $file->getFilename();
					if (isset($info->{$fileName})) {
						$fileInfo = (object)$info->{$fileName};
						if (isset($fileInfo->location)) {
							$location = (string)$fileInfo->location;
						}
					}

					$files[] = (object)[
						'description' => $fileName,
						'extension' => $file->getExtension(),
						'url' => strings::url(sprintf('%s/img/%d?img=%s&t=%s', $route, $dto->id, urlencode($file->getFilename()), $file->getMTime())),
						'error' => false,
						'size' => $file->getSize(),
						'location' => $location,
						'prestamp' => file_exists($file->getRealPath() . config::photolog_prestamp)

					];
				}
			}

			if (is_dir($queue = $path . '/queue')) {
				$_files = new FilesystemIterator($queue);
				foreach ($_files as $file) {
					if (preg_match('@(heic|png|jp[e]?g|jfif)$@i', $file->getExtension())) {

						$parts = pathinfo($file->getRealpath());
						$errfile = sprintf(
							'%s/%s.err',
							$parts['dirname'],
							$parts['filename']

						);


						$location = '';
						$fileName = $file->getFilename();
						if (isset($info->{$fileName})) {
							$fileInfo = (object)$info->{$fileName};
							if (isset($fileInfo->location)) {
								$location = (string)$fileInfo->location;
							}
						}

						$files[] = (object)[
							'description' => $fileName,
							'extension' => $file->getExtension(),
							'url' => strings::url(sprintf('%s/img/%d?img=%s', $route, $dto->id, urlencode($file->getFilename()))),
							'error' => file_exists($errfile) || 10 > $file->getSize(),
							'size' => $file->getSize(),
							'location' => $location,

						];
					}
				}
			}
		}

		return $files;
	}

	public function getForProperty(int $pid): array {

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
				`date` DESC',
			$this->db_name(),
			$pid
		);

		return (new dtoSet)($sql, fn ($dto) => $this->_dtoExpand($dto));
	}

	public function getPropertySummary() {
		$debug = false;
		//~ $debug = true;
		$timer = false;
		// $timer = application::timer();

		$this->Q(sprintf(
			'CREATE TEMPORARY TABLE _t AS
			SELECT
				l.*,
				p.`address_street`,
				p.`address_suburb`,
				p.`street_index`
			FROM
				(
					SELECT
						`id`,
						`property_id`,
						count( *) entries
					FROM
						`%s`
					GROUP BY `property_id`
				) l
					LEFT JOIN
				properties p ON p.`id` = l.`property_id`',
			$this->db_name()
		));

		if ($timer) logger::info(sprintf('<populated temporary table : %s> %s', $timer->elapsed(), __METHOD__));

		(new dtoSet)(
			'SELECT id, address_street, street_index, property_id FROM _t',
			function ($dto) {

				if (!$dto->street_index) {

					$street_index = strings::street_index($dto->address_street);

					$this->db->Update(
						'_t',
						['street_index' => $street_index],
						sprintf('WHERE id = %d', $dto->id),
						$flush = false
					);

					(new cms\properties\dao\properties)->UpdateByID(
						['street_index' => $street_index],
						$dto->property_id
					);
				}
			}
		);

		if ($timer) logger::info(sprintf('<start final parse : %s> %s', $timer->elapsed(), __METHOD__));

		$sql = 'SELECT * FROM _t ORDER BY address_suburb, street_index, address_street';
		$dtoSet = (new dtoSet)($sql, function ($dto) {

			$props = $this->getForProperty($dto->property_id);
			$dto->files = (object)[
				'processed' => 0,
				'queued' => 0,
				'errors' => 0,
				'total' => 0,
				'dirSize' => 0
			];

			foreach ($props as $prop) {

				$dto->files->processed += $prop->files->processed;
				$dto->files->queued += $prop->files->queued;
				if (isset($prop->files->errors)) $dto->files->errors += $prop->files->errors;
				$dto->files->total += $prop->files->total;
				$dto->files->dirSize += $prop->files->dirSize;
			}

			return $dto;
		});

		if ($timer) logger::info(sprintf('<done : %s> %s', $timer->elapsed(), __METHOD__));
		return $dtoSet;
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


		if ($res = $this->Result($sql)) {
			return $this->_dtoSet($res);
		}

		return [];
	}

	public function getImageInfo(dto $dto, string $file): object {

		if ($json = $this->_getInfo($dto)) {

			if (isset($json->{$file})) return (object)$json->{$file};
		}

		return (object)[];
	}

	public function Insert($a) {

		$a['created'] = $a['updated'] = self::dbTimeStamp();
		return parent::Insert($a);
	}

	/**
	 * preserves any image information during a file rename procedure
	 *
	 * @param dto $dto
	 * @param string $file
	 * @param string $fnewfile
	 * @return void
	 */
	public function renameImageInfo(dto $dto, string $file, string $newfile) {

		if ($json = $this->_getInfo($dto)) {

			if ($info = $json->{$file} ?? false) {

				unset($json->{$file});
				$json->{$newfile} = $info;
				$this->_setInfo($dto, $json);
			} else {

				// there was no specific file information to save
			}
		} else {

			// there was no specific information for this entry
		}
	}

	/**
	 * Sets rich information for the file - e.g. Smokealarm Location
	 *
	 * @param dto $dto
	 * @param string $file
	 * @param object $info
	 * @return void
	 */
	public function setImageInfo(dto $dto, string $file, object $info) {

		if ($json = $this->_getInfo($dto)) {

			$json->{$file} = $info;
			$this->_setInfo($dto, $json);
		} else {

			$this->_setInfo($dto, (object)[
				$file => $json
			]);
		}
	}

	public function DiskFileStorage(int $id, bool $create = false): DiskFileStorage {

		return new DiskFileStorage($this->store($id, $create));
	}

	public function store(int $id, bool $create = false) {

		$path = sprintf('%s%d', config::photologStore(), (int)$id);
		// logger::info( sprintf('<%s> %s', $path, __METHOD__));

		if ($create && !is_dir($path)) {
			mkdir($path, 0777);
			chmod($path, 0777);
		}

		return $path;
	}

	public function UpdateByID($a, $id) {

		$a['updated'] = self::dbTimeStamp();
		return parent::UpdateByID($a, $id);
	}
}
