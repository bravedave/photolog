<?php
/*
 * David Bray
 * BrayWorth Pty Ltd
 * e. david@brayworth.com.au
 *
 * MIT License
 *
*/

namespace photolog;

use green;
use Json;
use Response;
use sys;
use strings;

class controller extends \Controller {
  protected $viewPath = __DIR__ . '/views/';

	protected function _index() {
		if ( $pid = (int)$this->getParam('property')) {

			$dao = new dao\properties;
			$referer = $dao->getByID( $pid);

			$dao = new dao\property_photolog;
			$this->data = (object)[
				'dtoSet' => $dao->getForProperty( $pid),
				'referer' => $referer

			];

			//~ sys::dump( $this->data->dtoSet);

			$this->render([
				'title' => $this->title = $this->label,
				'primary' => 'report',
				'secondary' => 'index',
				'data' => (object)[
					'searchFocus' => false

				],

			]);

		}
		else {
			$dao = new dao\property_photolog;
			$this->data = (object)[
				'dtoSet' => $dao->getPropertySummary(),
				'referer' => false

			];

			//~ sys::dump( $this->data->dtoSet);

			$this->render([
				'title' => $this->title = $this->label,
				'primary' => ['searchbar','summary'],
				'secondary' => 'index',
				'data' => (object)[
					'searchFocus' => false

				],

			]);

		}

  }

	protected function before() {
    $this->label = 'Photolog';
		config::photolog_checkdatabase();
    parent::before();

	}

  protected function page( $params) {

    if ( !isset( $params['latescripts'])) $params['latescripts'] = [];
    $params['latescripts'][] = sprintf(
      '<script type="text/javascript" src="%s"></script>',
      strings::url( $this->route . '/js')

    );

		return parent::page( $params);

	}

	protected function posthandler() {
		$debug = false;
    //~ $debug = currentUser::isDavid();

		$action = $this->getPost('action');

		if ( 'add-entry' == $action || 'update-entry' == $action) {
			if ( $property_id = $this->getPost( 'property_id')) {
				$a = [
					'updated' => \db::dbTimeStamp(),
					'property_id' => $property_id,
					'subject' => $this->getPost( 'subject'),
					'date' => $this->getPost( 'date')

				];

				$dao = new dao\property_photolog;

				if ( 'update-entry' == $action) {
					if ( $id = (int)$this->getPost('id')) {
						$dao->UpdateByID( $a, $id);

						Json::ack( $action)
							->add( 'id', $id);

					} else { Json::nak( $action); }

				}
				else {
					$a['created'] = $a['updated'];
					$id = $dao->Insert( $a);

					Json::ack( $action)
						->add( 'id', $id);

				}

			} else { Json::nak( $action); }

		}
    elseif ( 'search-properties' == $action) {
			if ( $term = $this->getPost('term')) {
				Json::ack( $action)
					->add( 'term', $term)
					->add( 'data', green\search::properties( $term));

			} else { Json::nak( $action); }

    }
    elseif ( 'upload' == $action) {

			if ( $id = $this->getPost( 'id')) {
				$dao = new dao\property_photolog;
				if ( $dto = $dao->getByID( $id)) {
					$path = $dao->store( $dto->id, $create = true);
					$queue = sprintf( '%s/queue', $path);
					if ( ! is_dir( $queue)) {
						mkdir( $queue, 0777); chmod( $queue, 0777);

					}

					if ( $debug) sys::logger( sprintf( '<%s> %s', $path, __METHOD__));

					$response = [
						'response' => 'ack',
						'description' => '',
						'files' => []	];

					foreach ( $_FILES as $file ) {
						set_time_limit( 60);
						if ( $debug) sys::logger( sprintf( '<%s> %s', $file['name'], __METHOD__));

						if ( $file['error'] == 2 ) {
							sys::logger( sprintf( '<%s is too large> %s', $file['name'], __METHOD__));
							$response['response'] = 'nak';
							$response['description'] = $file['name'] . ' is too large ..';

						}
						elseif ( is_uploaded_file( $file['tmp_name'] )) {
							$strType = $file['type'];
							if ( $debug) sys::logger( sprintf( '<%s (%s)> %s', $file['name'], $strType, __METHOD__));

							$videoTypes = [ 'video/quicktime', 'video/mp4'];
							$accept = [
								'application/pdf',
								'image/jpeg',
								'image/pjpeg',
								'image/png',
								'video/quicktime',
								'video/mp4'

							];
							if ( in_array( $strType, $accept)) {
								if ( $debug) sys::logger( sprintf( '<%s (%s) acceptable> : %s', $file['name'], $strType, __METHOD__));
								$source = $file['tmp_name'];
								if (  'application/pdf' == $strType || in_array( $strType, $videoTypes)) {
									$target = sprintf( '%s/%s', $path, $file['name']);

								}
								else {
									$target = sprintf( '%s/%s', $queue, $file['name']);

								}

								if ( file_exists( $target )) unlink( $target );

								if (move_uploaded_file( $source, $target)) {
									chmod( $target, 0666 );

									if ( $debug) sys::logger( sprintf( 'upload: %s (%s) accepted : %s', $file['name'], $strType, __METHOD__));
									$response['files'][] = [
										'description' => $file['name'],
										'url' => strings::url( sprintf( $this->route . '/img/%d?img=%s&t=%s', $dto->id, $file['name'], filemtime( $target)))

									];

								}
								else {
									sys::logger("Possible file property_photolog/upload attack!  Here's some debugging info:\n" . var_export($_FILES, TRUE));

								}

							}
							elseif ( $strType == "" ) {
								sys::logger( sprintf( '<%s invalid file type> : %s', $file['name'], __METHOD__));
								$response['response'] = 'nak';
								$response['description'] = $file['name'] . ' invalid file type ..';

							}
							else {
								sys::notifySupport( 'CMS Error', sprintf( 'Trying to upload File Type %s in %s', $strType, __METHOD__));

								$response['response'] = 'nak';
								$response['description'] = $file['name'] . ' file type not permitted ..: ' . $strType;

							}

						} else {
              sys::logger( sprintf('<%s> %s', 'what the dickens ?', __METHOD__));

            }
						// elseif ( is_uploaded_file( $file['tmp_name'] )) {

					}

					new Json( $response);

				} else { Json::nak( $action); }

			} else { Json::nak( $action); }

		}
		else {
			parent::postHandler();

		}

	}

	public function entry( $id = 0) {
		$this->title = 'add entry';
		$this->data = (object)[
			'dto' => (object)[
				'id' => 0,
				'property_id' => 0,
				'address_street' => '',
				'subject' => '',
				'date' => date( 'Y-m-d'),

			]

		];

		if ( (int)$id > 0) {
			$dao = new dao\property_photolog;
			if ( $dto = $dao->getByID( $id)) {
				$this->title = 'edit entry';
				$this->data->dto = $dto;

			}

		}
		elseif ( $property = (int)$this->getParam('property')) {
			$dao = new dao\properties;
			if ( $dto = $dao->getByID( $property)) {
				$this->data->dto->property_id = $dto->id;
				$this->data->dto->address_street = $dto->address_street;

			}

		}


		$this->modal([
			'title' => $this->title,
			'load' => 'entry'

		]);

	}

	public function img( $id = 0) {
		//~ $default = sprintf( '%sdefault.jpg', \config::photologStore());

		if ( $id = (int)$id) {
			if ( $img = $this->getParam( 'img')) {
				//~ sys::logger( sprintf( 'property_photolog/img/%d - %s', $id, $img));
				if ( !( preg_match( '@(\.\.|\/)@', $img)) && preg_match( '@.(png|jp[e]?g|mov|mp4|pdf)$@i', $img)) {
					$dao = new dao\property_photolog;
					$path = $dao->store( $id);

					$_file = sprintf( '%s/%s', $path, $img);
					$_queue = sprintf( '%s/queue/%s', $path, $img);
					if ( file_exists( $_file)) {
						if ( 'full' != $this->getParam('v') && 'application/pdf' == mime_content_type(  $_file)) {
							sys::serve( sprintf( '%s/acrobat.png', config::imagePath()));

						}
						elseif ( 'full' != $this->getParam('v') && 'video/quicktime' == mime_content_type(  $_file)) {
							sys::serve( sprintf( '%s/mov-extension-filetype.png', config::imagePath()));

						}
						elseif ( 'full' != $this->getParam('v') && 'video/mp4' == mime_content_type(  $_file)) {
							sys::serve( sprintf( '%s/mp4-extension-filetype.png', config::imagePath()));

						}
						else {
							sys::serve( $_file);

						}

					}
					elseif ( file_exists( $_queue)) {
						sys::serve( config::photolog_default_image800x600_inqueue);

					}

				}

			}

		}
		else {
			sys::serve( config::photolog_default_image800x600);

		}

  }

  public function js( $lib = '') {
    $s = [];
    $r = [];

    $s[] = '@{{route}}@';
    $r[] = strings::url( $this->route);

    $js = \file_get_contents( __DIR__ . '/js/custom.js');
    $js = preg_replace( $s, $r, $js);

    Response::javascript_headers();
    print $js;

  }

	public function view( $id) {
		if ( $id = (int)$id) {
			$dao = new dao\property_photolog;
			if ( $dto = $dao->getByID( $id)) {
				$this->data = (object)[
					'dto' => $dto,
					'files' => $dao->getFiles( $dto, $this->route),
					'referer' => false,

				];

				if ( $referer = $this->getParam( 'f')) {
					$daoP = new dao\properties;
					$this->data->referer = $daoP->getByID( $referer);

        }

				$render = [
					'title' => $this->title = sprintf( '%s - view', $this->label),
					'primary' => 'view',
					'secondary' => 'index',
					'data' => (object)[
						'pageUrl' => strings::url( $this->route . '/view/' . $dto->id),

					],

				];

				$this->render( $render);

			} else { print 'not found'; }

		} else { print 'invalid'; }

  }

	public function zip( $id) {
		//~ $debug = false;
		$debug = true;

		if ( $id = (int)$id) {
			$dao = new dao\property_photolog;
			if ( $dto = $dao->getByID( $id)) {
				$filename = sprintf( '%sphotolog-%d.zip', config::tempdir(), $dto->id);
				if ( file_exists( $filename)) {
					unlink( $filename);

				}

				if ( $debug) sys::logger( sprintf( '<%s> : %s', $filename, __METHOD__));

				$zip = new \ZipArchive;

				if ( $zip->open($filename, \ZipArchive::CREATE) !==TRUE ) {
					\sys::logger( sprintf( '<cannot open %s> : %s', $filename, __METHOD__));
					printf( '<cannot open archive> %s', __METHOD__);

				}
				else {

					$ifiles = 0;
					$path = $dao->store( $dto->id);
					//~ printf( 'good - <%s> > %s', $path, $filename);

					if ( is_dir( $path)) {
						$files = [];
						$fit = new \FilesystemIterator( $path);
						foreach( $fit as $file) {
							if ( preg_match( '@(jp[e]?g|mov|mp4|pdf)$@i', $file->getExtension())) {
								//~ sys::logger( $file->getFilename());
								//~ sys::logger( $file->getExtension());
								$files[] = (object)[
									'description' => $file->getFilename(),
									'path' => $file->getPathname()
									];

								if ( $debug) sys::logger( sprintf( 'property_photolog/zip : adding <%s>', $file->getPathname()));
								$zip->addFile( $file->getPathname(), $file->getFilename());
								$ifiles ++;

							}

						}

					}

					//~ sys::dump( $files);

					if ( $debug) sys::logger( sprintf( '<numfiles : %s> %s', $zip->numFiles, __METHOD__));
					if ( $debug) sys::logger( sprintf( '<status : %s> %s', $zip->status, __METHOD__));

					$zip->close();

					if ( $ifiles) {
						sys::serve( $filename);

					}
					else {
            printf( '<empty archive> %s', __METHOD__);

					}

					if ( file_exists( $filename)) unlink( $filename);

				}

			} else { printf( '<property not found> %s', __METHOD__); }

		} else { printf( '<invalid> %s', __METHOD__); }

  }

}
