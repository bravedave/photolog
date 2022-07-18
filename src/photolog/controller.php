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

use currentUser;
use dvc\userAgent;
use green, Json, Response, sys, strings;
use SplFileInfo;

class controller extends \Controller {
	protected $viewPath = __DIR__ . '/views/';

	protected function _index() {
		if ($pid = (int)$this->getParam('property')) {

			$dao = new dao\properties;
			$referer = $dao->getByID($pid);

			$dao = new dao\property_photolog;
			$this->data = (object)[
				'dtoSet' => $dao->getForProperty($pid),
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
		} else {
			$dao = new dao\property_photolog;
			$this->data = (object)[
				'dtoSet' => $dao->getPropertySummary(),
				'referer' => false

			];

			//~ sys::dump( $this->data->dtoSet);

			$this->render([
				'title' => $this->title = $this->label,
				'primary' => ['searchbar', 'summary'],
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

	protected function page($params) {

		if (!isset($params['latescripts'])) $params['latescripts'] = [];
		$params['latescripts'][] = sprintf(
			'<script type="text/javascript" src="%s"></script>',
			strings::url($this->route . '/js')

		);

		return parent::page($params);
	}

	protected function posthandler() {
		$debug = false;
		// $debug = currentUser::isDavid();

		$action = $this->getPost('action');

		if ('add-entry' == $action || 'update-entry' == $action) {
			if ($property_id = $this->getPost('property_id')) {
				$a = [
					'property_id' => $property_id,
					'subject' => $this->getPost('subject'),
					'date' => $this->getPost('date')

				];

				$dao = new dao\property_photolog;

				if ('update-entry' == $action) {
					if ($id = (int)$this->getPost('id')) {

						$dao->UpdateByID($a, $id);
						Json::ack($action)
							->add('id', $id);
					} else {
						Json::nak($action);
					}
				} else {
					$id = $dao->Insert($a);
					Json::ack($action)
						->add('id', $id);
				}
			} else {
				Json::nak($action);
			}
		} elseif ('delete' == $action) {
			if ($id = $this->getPost('id')) {
				$dao = new dao\property_photolog;
				if ($dto = $dao->getByID($id)) {
					$path = $dao->store($dto->id);
					$_file = trim($this->getPost('file'), './ ');
					$file = $path . '/' . $_file;
					$qfile = $path . '/queue/' . $_file;
					//~ sys::logger( sprintf( 'delete : %s || %s', $file, $qfile));

					if (file_exists($file)) {
						unlink($file);
						clearstatcache();
					}

					if (file_exists($file . config::photolog_prestamp)) {
						unlink($file . config::photolog_prestamp);
						clearstatcache();
					}

					if (file_exists($qfile)) {
						$parts = pathinfo($qfile);

						$errfile = sprintf(
							'%s/%s.err',
							$parts['dirname'],
							$parts['filename']
						);

						//~ sys::logger( $errfile);

						if (file_exists($errfile)) unlink($errfile);
						unlink($qfile);
						clearstatcache();

						if ($debug) sys::logger(sprintf('<unlink( %s)> : %s', $qfile, __METHOD__));
					} else {
						if ($debug) sys::logger(sprintf('<qfile not found ( %s)> : %s', $qfile, __METHOD__));
					}

					Json::ack($action);
				} else {
					Json::nak($action);
				}
			} else {
				Json::nak($action);
			}
		} elseif ('delete-entry' == $action) {
			if ($id = $this->getPost('id')) {
				$dao = new dao\property_photolog;
				if ($dto = $dao->getByID($id)) {

					if (0 == $dto->files->total) {
						$path = $dao->store($dto->id);
						$qpath = $path . '/queue/';
						$infofile = $path . '/_info.json';

						//~ Json::nak( sprintf( '%s : %s, %s, %d files', $action, $path, $qpath, $dto->files->total));

						if (file_exists($infofile)) unlink($infofile);
						if (is_dir($qpath)) rmdir($qpath);
						if (is_dir($path)) rmdir($path);
						$dao->delete($id);
						Json::ack($action);
					} else {
						Json::nak(sprintf('%s %d files', $action, $dto->files->total));
					}
				} else {
					Json::nak($action);
				}
			} else {
				Json::nak($action);
			}
		} elseif ('get-photolog' == $action) {
			/*
      ( _ => {
        _.post({
          url : _.url( 'property_photolog'),
          data : {
            action : 'get-photolog',
            property : 2
          }
        }).then( d => 'ack' == d.response ? console.table( d.data) : _.growl( d));
			})(_brayworth_);
      */
			if ($pid = (int)$this->getPost('property')) {
				$dao = new dao\property_photolog;
				Json::ack($action)
					->add('data', $dao->getForProperty($pid));	// dtoSet

			} else {
				Json::nak($action);
			}
		} elseif ('property-smokealarms' == $action) {
			/*
      ( _ => {
        _.post({
          url : _.url('property_photolog'),
          data : {
            action : 'property-smokealarms',
            id : 3694

          },

        }).then( d => {
          console.log( d);
          _.growl( d);

        });

      }) (_brayworth_);
      */

			if ($id = (int)$this->getPost('id')) {
				$alarms = [];
				if (class_exists('smokealarm\dao\smokealarm')) {
					$dao = new dao\property_photolog;
					if ($dto = $dao->getByID($id)) {
						$dao = new \smokealarm\dao\smokealarm;
						if ($res = $dao->getForProperty($dto->property_id)) {
							$alarms = (array)$res->dtoSet();
						}
					}
				}

				Json::ack($action)
					->add('alarms', $alarms);
			} else {
				Json::nak($action);
			}
		} elseif ('public-link-clear' == $action) {
			if ($id = $this->getPost('id')) {
				$dao = new dao\property_photolog;
				if ($dto = $dao->getByID($id)) {

					$a = [
						'public_link' => '',
						'public_link_expires' => ''

					];
					$dao->UpdateByID($a, $dto->id);
					Json::ack($action);
				} else {
					Json::nak($action);
				}
			} else {
				Json::nak($action);
			}
		} elseif ('public-link-create' == $action) {
			if ($id = $this->getPost('id')) {
				$dao = new dao\property_photolog;
				if ($dto = $dao->getByID($id)) {

					$a = ['public_link_expires' => ''];
					if (strtotime($this->getPost('public_link_expires')) > time()) {
						$a = [
							'public_link' => bin2hex(random_bytes(11)),
							'public_link_expires' => $this->getPost('public_link_expires')

						];
					}

					$dao->UpdateByID($a, $dto->id);
					Json::ack($action);
				} else {
					Json::nak($action);
				}
			} else {
				Json::nak($action);
			}
		} elseif ('public-link-get' == $action) {
			if ($id = $this->getPost('id')) {
				$dao = new dao\property_photolog;
				if ($dto = $dao->getByID($id)) {
					if (strtotime($dto->public_link_expires) > time()) {
						Json::ack($action)
							->add('url', sprintf('%spl/%s', config::$PORTAL, $dto->public_link))
							->add('expires', $dto->public_link_expires);
					} else {
						Json::nak($action);
					}
				} else {
					Json::nak($action);
				}
			} else {
				Json::nak($action);
			}
		} elseif ('rotate-left' == $action || 'rotate-right' == $action || 'rotate-180' == $action) {
			if ($id = $this->getPost('id')) {
				$dao = new dao\property_photolog;
				if ($dto = $dao->getByID($id)) {

					$path = $dao->store($dto->id);

					$_file = trim($this->getPost('file'), './ ');
					$file = $path . '/' . $_file;

					if (file_exists($file . config::photolog_prestamp)) {
						if (utility::rotate(
							$file,
							'rotate-180' == $action ?
								config::photolog_rotate_180 : ('rotate-left' == $action ?
									config::photolog_rotate_left : config::photolog_rotate_right)
						)) {

							if (file_exists($file)) {
								$info = new SplFileInfo($file);
								$imgInfo = $dao->getImageInfo($dto, $info->getFilename());

								$returnfile = (object)[
									'description' => $info->getFilename(),
									'extension' => $info->getExtension(),
									'url' => strings::url(sprintf('%s/img/%d?img=%s&t=%s', $this->route, $dto->id, urlencode($info->getFilename()), $info->getMTime())),
									'error' => false,
									'size' => $info->getSize(),
									'location' => $imgInfo->location ?? '',
									'prestamp' => file_exists($info->getRealPath() . config::photolog_prestamp)
								];

								Json::ack($action)
									->add('data', $returnfile);
							} else {
								Json::nak($action);
							}
						} else {
							Json::nak($action);
						}
					} else {
						Json::nak(sprintf('missing pre-stamp : %s', $action));
					}
				} else {
					Json::nak(sprintf('not found - %s', $action));
				}
			} else {
				Json::nak($action);
			}
		} elseif ('save-notepad' == $action) {
			if ($id = $this->getPost('id')) {
				$dao = new dao\property_photolog;
				if ($dto = $dao->getByID($id)) {

					$a = [
						'notes' => $this->getPost('notes')

					];

					$dao->UpdateByID($a, $dto->id);
					Json::ack($action)
						->add('data', $a);
				} else {
					Json::nak($action);
				}
			} else {
				Json::nak($action);
			}
		} elseif ('search-properties' == $action) {
			if ($term = $this->getPost('term')) {
				Json::ack($action)
					->add('term', $term)
					->add('data', green\search::properties($term));
			} else {
				Json::nak($action);
			}
		} elseif ('set-alarm-location' == $action) {
			if ($id = $this->getPost('id')) {
				if ($file = $this->getPost('file')) {
					if ($location = $this->getPost('location')) {
						$dao = new dao\property_photolog;
						if ($dto = $dao->getByID($id)) {
							$info = $dao->getImageInfo($dto, $file);
							$info->location = $location;
							$dao->setImageInfo($dto, $file, $info);
							Json::ack($action);
						} else {
							Json::nak($action);
						}
					} else {
						Json::nak($action);
					}
				} else {
					Json::nak($action);
				}
			} else {
				Json::nak($action);
			}
		} elseif ('set-alarm-location-clear' == $action) {
			if ($id = $this->getPost('id')) {
				if ($file = $this->getPost('file')) {
					$dao = new dao\property_photolog;
					if ($dto = $dao->getByID($id)) {
						$info = $dao->getImageInfo($dto, $file);
						if (isset($info->location)) {
							unset($info->location);
							$dao->setImageInfo($dto, $file, $info);
						}

						Json::ack($action);
					} else {
						Json::nak($action);
					}
				} else {
					Json::nak($action);
				}
			} else {
				Json::nak($action);
			}
		} elseif ('upload' == $action) {

			$id = (int)$this->getPost('id');
			$location = '';

			if ($tag = $this->getPost('tag')) {

				$id = 0;

				if (class_exists('smokealarm\dao\smokealarm')) {
					if ('smokealarm' == $tag) {
						if ($smokealarm_id = (int)$this->getPost('smokealarm_id')) {
							$dao = new \smokealarm\dao\smokealarm;
							if ($dto = $dao->getByID($smokealarm_id)) {
								if ($dto->properties_id) {
									$dao = new dao\property_photolog;
									if ($logs = $dao->getForProperty($dto->properties_id)) {
										foreach ($logs as $log) {
											if (preg_match('@^smoke alarm audit@i', $log->subject)) {
												if (($t = strtotime($log->date)) > 0) {
													if (date('Y-m') == date('Y-m', $t)) {
														// use this one ..
														$id = $log->id;
														break;
													}
												}
											}
										}
									}
								}

								if (!$id) {
									$dao = new dao\property_photolog;
									$id = $dao->Insert([
										'property_id' => $dto->properties_id,
										'subject' => sprintf('Smoke Alarm Audit %s', date('M Y')),
										'date' => date('Y-m-d')

									]);
								}

								$location = $this->getPost('location');
							}
						}
					}
				}
			}

			if ($id) {
				$dao = new dao\property_photolog;
				if ($dto = $dao->getByID($id)) {
					$path = $dao->store($dto->id, $create = true);
					$queue = sprintf('%s/queue', $path);
					if (!is_dir($queue)) {
						mkdir($queue, 0777);
						chmod($queue, 0777);
					}

					if ($debug) sys::logger(sprintf('<%s> %s', $path, __METHOD__));

					$response = [
						'response' => 'ack',
						'description' => '',
						'files' => []

					];

					foreach ($_FILES as $file) {
						touch($queue . '/.upload-in-progress');
						@chmod($queue . '/.upload-in-progress', 0666);

						set_time_limit(60);
						if ($debug) sys::logger(sprintf('<%s> %s', $file['name'], __METHOD__));

						if ($file['error'] == 2) {
							sys::logger(sprintf('<%s is too large> %s', $file['name'], __METHOD__));
							$response['response'] = 'nak';
							$response['description'] = $file['name'] . ' is too large ..';
						} elseif (is_uploaded_file($file['tmp_name'])) {
							// $strType = $file['type'];
							$strType = mime_content_type($file['tmp_name']);
							if ($debug) sys::logger(sprintf('<%s (%s)> %s', $file['name'], $strType, __METHOD__));

							$videoTypes = ['video/quicktime', 'video/mp4'];

							$accept = [
								'application/pdf',
								'image/jpeg',
								'image/pjpeg',
								'image/png',
								'video/quicktime',
								'video/mp4'

							];

							/** heic are not current supported on fedora */
							if (config::photolog_enable_heic) {
								$accept[] = 'image/heic';
								$accept[] = 'image/heif';
							}

							// \sys::logger( sprintf('<%s> %s', $strType, __METHOD__));

							if (in_array($strType, $accept)) {
								if ($debug) sys::logger(sprintf('<%s (%s) acceptable> : %s', $file['name'], $strType, __METHOD__));
								$source = $file['tmp_name'];
								if ('application/pdf' == $strType || in_array($strType, $videoTypes)) {
									$target = sprintf('%s/%s', $path, $file['name']);
								} else {
									$target = sprintf('%s/%s', $queue, $file['name']);
								}

								if (file_exists($target)) unlink($target);

								if (move_uploaded_file($source, $target)) {
									chmod($target, 0666);

									if ($debug) sys::logger(sprintf('upload: %s (%s) accepted : %s', $file['name'], $strType, __METHOD__));
									$response['files'][] = [
										'description' => $file['name'],
										'url' => strings::url(sprintf($this->route . '/img/%d?img=%s&t=%s', $dto->id, $file['name'], filemtime($target)))

									];

									if ($location) {
										if (!('application/pdf' == $strType || in_array($strType, $videoTypes))) {
											// do this now to improve response for autoupdate
											utility::stampone(
												$target,
												sprintf('%s/%s', $path, $file['name']),
												$dto

											);
										}

										$info = $dao->getImageInfo($dto, $file['name']);
										$info->location = $location;
										$dao->setImageInfo($dto, $file['name'], $info);
									}
								} else {
									sys::logger("Possible file property_photolog/upload attack!  Here's some debugging info:\n" . var_export($_FILES, TRUE));
								}
							} elseif ($strType == "") {
								sys::logger(sprintf('<%s invalid file type> : %s', $file['name'], __METHOD__));
								$response['response'] = 'nak';
								$response['description'] = $file['name'] . ' invalid file type ..';
							} else {
								\sys::logger(sprintf('<file type not permitted : %s> %s', $strType, __METHOD__));
								sys::notifySupport(
									'PhotoLog Error',
									implode(PHP_EOL, [
										sprintf('Trying to upload : %s', $strType, __METHOD__),
										sprintf('File   ...: %s(%s)', $file['name'], $strType),
										sprintf('User   ...: %s', currentUser::name()),
										sprintf('UserAgent : %s', userAgent::toString()),

									])
								);

								$response['response'] = 'nak';
								$response['description'] = $file['name'] . ' file type not permitted ..: ' . $strType;
							}
						} else {
							sys::logger(sprintf('<%s> %s', 'what the dickens ?', __METHOD__));
							sys::logger(sprintf('<%s> %s', $file['error'], __METHOD__));
						}
						// elseif ( is_uploaded_file( $file['tmp_name'] )) {

					}

					new Json($response);
				} else {
					Json::nak($action);
				}
			} else {
				Json::nak($action);
			}
		} else {
			parent::postHandler();
		}
	}

	public function entry($id = 0) {
		$this->title = 'add entry';
		$this->data = (object)[
			'dto' => (object)[
				'id' => 0,
				'property_id' => 0,
				'address_street' => '',
				'subject' => '',
				'date' => date('Y-m-d'),

			]

		];

		if ((int)$id > 0) {
			$dao = new dao\property_photolog;
			if ($dto = $dao->getByID($id)) {
				$this->title = 'edit entry';
				$this->data->dto = $dto;
			}
		} elseif ($property = (int)$this->getParam('property')) {
			$dao = new dao\properties;
			if ($dto = $dao->getByID($property)) {
				$this->data->dto->property_id = $dto->id;
				$this->data->dto->address_street = $dto->address_street;
			}
		}

		$this->load('entry');
	}

	public function img($id = 0) {
		//~ $default = sprintf( '%sdefault.jpg', \config::photologStore());

		if ($id = (int)$id) {

			// sys::logger( sprintf( 'img/%d : %s', $id, __METHOD__));

			if ($img = $this->getParam('img')) {

				// sys::logger( sprintf( 'img/%d - %s: %s', $id, $img, __METHOD__));

				if (!(preg_match('@(\.\.|\/)@', $img)) && preg_match('@.(png|jp[e]?g|jfif|mov|mp4|pdf|heic)$@i', $img)) {
					$dao = new dao\property_photolog;
					$path = $dao->store($id);

					$_file = sprintf('%s/%s', $path, $img);
					$_queue = sprintf('%s/queue/%s', $path, $img);
					if (file_exists($_file)) {
						$mimetype = '';
						if ('full' != $this->getParam('v')) $mimetype = mime_content_type($_file);

						if ('full' != $this->getParam('v') && 'application/pdf' == $mimetype) {
							// sys::logger( sprintf( '%s/resources/images/acrobat.png', __DIR__));
							sys::serve(sprintf('%s/resources/images/acrobat.png', __DIR__));
						} elseif ('full' != $this->getParam('v') && 'video/quicktime' == $mimetype) {
							sys::serve(sprintf('%s/resources/images/mov-extension-filetype.png', __DIR__));
						} elseif ('full' != $this->getParam('v') && 'video/mp4' == $mimetype) {
							sys::serve(sprintf('%s/resources/images/mp4-extension-filetype.png', __DIR__));
						} else {
							sys::serve($_file);
						}
					} elseif (file_exists($_queue)) {
						sys::serve(config::photolog_default_image800x600_inqueue);
					}
				}
			}
		} else {
			sys::serve(config::photolog_default_image800x600);
		}
	}

	public function js($lib = '') {
		$s = [];
		$r = [];

		$s[] = '@{{route}}@';
		$r[] = strings::url($this->route);

		$js = \file_get_contents(__DIR__ . '/js/custom.js');
		$js = preg_replace($s, $r, $js);

		Response::javascript_headers();
		print $js;
	}

	public function notepad($id) {
		if ($id = (int)$id) {
			$dao = new dao\property_photolog;
			if ($dto = $dao->getByID($id)) {
				$this->data = (object)[
					'title' => $this->title = sprintf('%s - notepad', $this->label),
					'dto' => $dto,
				];

				$this->load('notepad');
			} else {
				print 'not found';
			}
		} else {
			print 'invalid';
		}
	}

	public function publicLink($id) {
		if ($id = (int)$id) {
			$dao = new dao\property_photolog;
			if ($dto = $dao->getByID($id)) {
				$this->data = (object)[
					'title' => $this->title = 'Public Link',
					'dto' => $dto,

				];

				$this->load('public-link');
				// $this->load( 'invalid');

			} else {
				$this->load('invalid');
			}
		} else {
			$this->load('invalid');
		}
	}

	public function view($id = 0) {
		if ($id = (int)$id) {
			$dao = new dao\property_photolog;
			if ($dto = $dao->getByID($id)) {
				$this->data = (object)[
					'dto' => $dto,
					'files' => $dao->getFiles($dto, $this->route),
					'referer' => false,

				];

				if ($referer = $this->getParam('f')) {
					$daoP = new dao\properties;
					$this->data->referer = $daoP->getByID($referer);
				}

				$render = [
					'title' => $this->title = sprintf('%s - view', $this->label),
					'primary' => 'view',
					'secondary' => 'index',
					'data' => (object)[
						'pageUrl' => strings::url($this->route . '/view/' . $dto->id),

					],

				];

				$this->render($render);
			} else {
				print 'not found';
			}
		} else {
			print 'invalid';
		}
	}

	public function zip($id) {
		//~ $debug = false;
		$debug = true;

		if ($id = (int)$id) {
			$dao = new dao\property_photolog;
			if ($dto = $dao->getByID($id)) {
				$filename = sprintf('%sphotolog-%d.zip', config::tempdir(), $dto->id);
				if (file_exists($filename)) {
					unlink($filename);
				}

				if ($debug) sys::logger(sprintf('<%s> : %s', $filename, __METHOD__));

				$zip = new \ZipArchive;

				if ($zip->open($filename, \ZipArchive::CREATE) !== TRUE) {
					\sys::logger(sprintf('<cannot open %s> : %s', $filename, __METHOD__));
					printf('<cannot open archive> %s', __METHOD__);
				} else {

					$ifiles = 0;
					$path = $dao->store($dto->id);
					//~ printf( 'good - <%s> > %s', $path, $filename);

					if (is_dir($path)) {
						$files = [];
						$fit = new \FilesystemIterator($path);
						foreach ($fit as $file) {
							if (preg_match('@(jp[e]?g|mov|mp4|pdf)$@i', $file->getExtension())) {
								//~ sys::logger( $file->getFilename());
								//~ sys::logger( $file->getExtension());
								$files[] = (object)[
									'description' => $file->getFilename(),
									'path' => $file->getPathname()
								];

								if ($debug) sys::logger(sprintf('property_photolog/zip : adding <%s>', $file->getPathname()));
								$zip->addFile($file->getPathname(), $file->getFilename());
								$ifiles++;
							}
						}
					}

					//~ sys::dump( $files);

					if ($debug) sys::logger(sprintf('<numfiles : %s> %s', $zip->numFiles, __METHOD__));
					if ($debug) sys::logger(sprintf('<status : %s> %s', $zip->status, __METHOD__));

					$zip->close();

					if ($ifiles) {
						sys::serve($filename);
					} else {
						printf('<empty archive> %s', __METHOD__);
					}

					if (file_exists($filename)) unlink($filename);
				}
			} else {
				printf('<property not found> %s', __METHOD__);
			}
		} else {
			printf('<invalid> %s', __METHOD__);
		}
	}
}
