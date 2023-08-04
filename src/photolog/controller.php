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

use application;
use bravedave\dvc\{json, logger, Response, userAgent};
use cms, cms\currentUser;
use green, strings;
use SplFileInfo;

class controller extends cms\controller {

	protected function _index() {

		if ($pid = (int)$this->getParam('property')) {

			$this->data = (object)[
				'aside' => array_merge(['index'], config::index_set),
				'dtoSet' => (new dao\property_photolog)->getForProperty($pid),
				'referer' => (new dao\properties)->getByID($pid),
				'latescripts' => [sprintf(
					'<script type="text/javascript" src="%s"></script>',
					strings::url($this->route . '/js')
				)],
				'pageUrl' => strings::url($this->route . '/?property=' . $pid),
				'searchFocus' => false,
				'title' => $this->title = config::label,
			];

			if ($this->data->referer) {
				$this->data->title = sprintf('%s : %s', $this->data->referer->address_street, $this->data->title);
			}

			$this->renderBS5([
				'main' => fn () => $this->load('report')
			]);
		} else {

			$dao = new dao\property_photolog;
			$this->data = (object)[
				'aside' => array_merge(['index'], config::index_set),
				'dtoSet' => $dao->getPropertySummary(),
				'referer' => false,
				'latescripts' => [sprintf(
					'<script type="text/javascript" src="%s"></script>',
					strings::url($this->route . '/js')
				)],
				'pageUrl' => strings::url($this->route),
				'searchFocus' => false,
				'title' => $this->title = config::label,
				'bootstrap' => '5',
			];

			// logger::info( sprintf('<%s> %s', application::timer()->elapsed(), __METHOD__));
			$this->renderBS5([
				'main' => fn () => $this->load('summary')
			]);
		}
	}

	protected function before() {

		config::photolog_checkdatabase();
		$this->viewPath[] = __DIR__ . '/views/';

		parent::before();
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
						json::ack($action)
							->add('id', $id);
					} else {
						json::nak($action);
					}
				} else {
					$id = $dao->Insert($a);
					json::ack($action)
						->add('id', $id);
				}
			} else {
				json::nak($action);
			}
		} elseif ('delete' == $action) {

			if ($id = $this->getPost('id')) {

				$dao = new dao\property_photolog;
				if ($dto = $dao->getByID($id)) {

					$storage = $dao->DiskFileStorage($dto->id, $create = false);
					if ($storage->isValid()) {

						$_file = trim($this->getPost('file'), './ ');
						if ($_file) {

							$storage->deleteFile($_file);
							$storage->deleteFile($_file . config::photolog_prestamp);

							$Qstorage = $storage->subFolder('queue', $create = false);
							if ($Qstorage->isValid()) {

								if ($Qstorage->file_exists($_file)) {

									$errfile = sprintf(
										'%s.err',
										basename($Qstorage->getPath($_file))
									);
									$Qstorage->deleteFile($_file);
									$Qstorage->deleteFile($errfile);
								}
							}
						}
					}

					json::ack($action);
				} else {

					json::nak($action);
				}
			} else {

				json::nak($action);
			}
		} elseif ('delete-entry' == $action) {

			if ($id = $this->getPost('id')) {

				$dao = new dao\property_photolog;
				if ($dto = $dao->getByID($id)) {

					if (0 == $dto->files->total) {

						$storage = $dao->DiskFileStorage($dto->id, $create = false);
						if ($storage->isValid()) {

							$Qstorage = $storage->subFolder('queue');
							if ($Qstorage->isValid()) $Qstorage->delete();

							if ($storage->file_exists('_info.json')) $storage->deleteFile('_info.json');
							$storage->delete();
						}

						$dao->delete($id);
						json::ack($action);
					} else {
						json::nak(sprintf('%s %d files', $action, $dto->files->total));
					}
				} else {
					json::nak($action);
				}
			} else {
				json::nak($action);
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
				json::ack($action)
					->add('data', $dao->getForProperty($pid));	// dtoSet
			} else {

				json::nak($action);
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

				json::ack($action)
					->add('alarms', $alarms);
			} else {
				json::nak($action);
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
					json::ack($action);
				} else {
					json::nak($action);
				}
			} else {
				json::nak($action);
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
					json::ack($action);
				} else {
					json::nak($action);
				}
			} else {
				json::nak($action);
			}
		} elseif ('public-link-get' == $action) {

			if ($id = $this->getPost('id')) {
				$dao = new dao\property_photolog;
				if ($dto = $dao->getByID($id)) {
					if (strtotime($dto->public_link_expires) > time()) {
						json::ack($action)
							->add('url', sprintf('%spl/%s', config::$PORTAL, $dto->public_link))
							->add('expires', $dto->public_link_expires);
					} else {
						json::nak($action);
					}
				} else {
					json::nak($action);
				}
			} else {
				json::nak($action);
			}
		} elseif ('rename-file' == $action) {

			if ($id = (int)$this->getPost('id')) {

				$oldfile = trim($this->getPost('file'), './ ');
				if ($oldfile) {

					$dao = new dao\property_photolog;
					if ($dto = $dao->getByID($id)) {

						$storage = $dao->DiskFileStorage($dto->id, $create = false);
						if ($storage->isValid()) {

							if ($storage->file_exists($oldfile)) {

								$newfile = strings::safe_file_name(trim($this->getPost('newfile'), './ '));
								if (!$newfile) {

									json::nak(sprintf('%s : invalid new name', $action));
									return;
								}

								// the new file must preserve the extension
								$ext = '.' . pathinfo($storage->getPath($oldfile), PATHINFO_EXTENSION);
								if (substr($newfile, -strlen($ext)) != $ext) {

									$newfile .= $ext;
								}

								if ($storage->file_exists($newfile)) {

									json::nak(sprintf('%s : %s already exists', $action, $newfile));
									return;
								}

								$storage->rename($oldfile, $newfile);
								$dao->renameImageInfo($dto, $oldfile, $newfile);
								json::ack($action);
							} else {

								json::nak($action);
							}
						} else {

							json::nak($action);
						}
					} else {

						json::nak($action);
					}
				} else {

					json::nak($action);
				}
			} else {

				json::nak($action);
			}
		} elseif ('rotate-left' == $action || 'rotate-right' == $action || 'rotate-180' == $action) {

			if ($id = (int)$this->getPost('id')) {

				$dao = new dao\property_photolog;
				if ($dto = $dao->getByID($id)) {

					$_file = trim($this->getPost('file'), './ ');
					$storage = $dao->DiskFileStorage($dto->id, $create = false);
					if ($storage->isValid()) {

						if ($storage->file_exists($_file)) {

							$file = $storage->getPath($_file);
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

										json::ack($action)
											->add('data', $returnfile);
									} else {

										json::nak($action);
									}
								} else {

									json::nak($action);
								}
							} else {

								json::nak(sprintf('missing pre-stamp : %s', $action));
							}
						} else {

							json::nak(sprintf('not found - %s', $action));
						}
					} else {

						json::nak(sprintf('not found - %s', $action));
					}
				} else {

					json::nak(sprintf('not found - %s', $action));
				}
			} else {

				json::nak($action);
			}
		} elseif ('save-notepad' == $action) {
			if ($id = $this->getPost('id')) {
				$dao = new dao\property_photolog;
				if ($dto = $dao->getByID($id)) {

					$a = [
						'notes' => $this->getPost('notes')

					];

					$dao->UpdateByID($a, $dto->id);
					json::ack($action)
						->add('data', $a);
				} else {
					json::nak($action);
				}
			} else {
				json::nak($action);
			}
		} elseif ('search-properties' == $action) {
			if ($term = $this->getPost('term')) {
				json::ack($action)
					->add('term', $term)
					->add('data', green\search::properties($term));
			} else {
				json::nak($action);
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
							json::ack($action);
						} else {

							json::nak($action);
						}
					} else {

						json::nak($action);
					}
				} else {

					json::nak($action);
				}
			} else {

				json::nak($action);
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

						json::ack($action);
					} else {
						json::nak($action);
					}
				} else {
					json::nak($action);
				}
			} else {
				json::nak($action);
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

			$videoTypes = ['video/quicktime', 'video/mp4'];

			$accept = [
				'application/pdf',
				'image/jpeg',
				'image/pjpeg',
				'image/png'
			];

			if (config::$PHOTOLOG_ENABLE_VIDEO) {
				$accept[] = 'video/quicktime';
				$accept[] = 'video/mp4';
			}

			/** heic are not current supported on fedora */
			if (config::$PHOTOLOG_ENABLE_HEIC) {
				$accept[] = 'image/heic';
				$accept[] = 'image/heif';
			}

			if ($id) {

				$dao = new dao\property_photolog;
				if ($dto = $dao->getByID($id)) {

					$storage = $dao->DiskFileStorage($dto->id, $create = true);
					$Qstorage = $storage->subFolder('queue');

					$response = [
						'response' => 'ack',
						'description' => '',
						'files' => []
					];

					foreach ($_FILES as $file) {

						$Qstorage->touch('.upload-in-progress');

						set_time_limit(60);
						if ($debug) logger::debug(sprintf('<%s> %s', $file['name'], __METHOD__));

						if ($file['error'] == 2) {

							logger::info(sprintf('<%s is too large> %s', $file['name'], __METHOD__));
							$response['response'] = 'nak';
							$response['description'] = $file['name'] . ' is too large ..';
						} elseif (is_uploaded_file($file['tmp_name'])) {

							$strType = mime_content_type($file['tmp_name']);
							if ($debug) logger::debug(sprintf('<%s (%s)> %s', $file['name'], $strType, __METHOD__));

							if (in_array($strType, $accept)) {

								if ($debug) logger::debug(sprintf('<%s (%s) acceptable> : %s', $file['name'], $strType, __METHOD__));

								$target = '';
								if ('application/pdf' == $strType || in_array($strType, $videoTypes)) {

									$target = $storage->storeFile($file);
								} else {

									$target = $Qstorage->storeFile($file);
								}

								if ($target) {

									chmod($target, 0666);
									// logger::info(sprintf('<%s> %s', $target, __METHOD__));

									if ($debug) logger::debug(sprintf('upload: %s (%s) accepted : %s', $file['name'], $strType, __METHOD__));
									$response['files'][] = [
										'description' => $file['name'],
										'url' => strings::url(sprintf($this->route . '/img/%d?img=%s&t=%s', $dto->id, $file['name'], filemtime($target)))
									];

									if ($location) {

										if (!('application/pdf' == $strType || in_array($strType, $videoTypes))) {

											// do this now to improve response for autoupdate
											utility::stampone(
												$target,
												sprintf('%s/%s', $storage->getPath(), $file['name']),
												$dto
											);
										}

										$info = $dao->getImageInfo($dto, $file['name']);
										$info->location = $location;
										$dao->setImageInfo($dto, $file['name'], $info);
									}
								} else {

									logger::info("Possible file property_photolog/upload attack!  Here's some debugging info:\n" . var_export($_FILES, TRUE));
								}
							} elseif ($strType == "") {

								logger::info(sprintf('<%s invalid file type> : %s', $file['name'], __METHOD__));
								$response['response'] = 'nak';
								$response['description'] = $file['name'] . ' invalid file type ..';
							} else {

								logger::info(sprintf('<file type not permitted : %s> %s', $strType, __METHOD__));
								// \sys::notifySupport(
								// 	'PhotoLog Error',
								// 	implode(PHP_EOL, [
								// 		sprintf('Trying to upload : %s', $strType, __METHOD__),
								// 		sprintf('File   ...: %s(%s)', $file['name'], $strType),
								// 		sprintf('User   ...: %s', currentUser::name()),
								// 		sprintf('UserAgent : %s', userAgent::toString()),
								// 	])
								// );

								// config::$DEBUG_REJECT_TYPES = true;
								if (config::$DEBUG_REJECT_TYPES) {

									$trash = $storage->subFolder('.trash');
									if ($target = $trash->storeFile($file)) {

										chmod($target, 0666);
										logger::debug(sprintf('<save debug file : %s> %s', $target, __METHOD__));
									}
								}

								$response['response'] = 'nak';
								$response['description'] = $file['name'] . ' file type not permitted ..: ' . $strType;
							}
						} else {

							logger::info(sprintf('<what the dickens : %s> %s', $file['error'], __METHOD__));
						}
					}

					new json($response);
				} else {

					json::nak($action);
				}
			} else {

				json::nak($action);
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

		if ($id = (int)$id) {

			if ($img = $this->getParam('img')) {

				if (!(preg_match('@(\.\.|\/)@', $img)) && preg_match('@.(png|jp[e]?g|jfif|mov|mp4|pdf|heic)$@i', $img)) {

					$storage = (new dao\property_photolog)->DiskFileStorage($id, $create = false);
					if ($storage->file_exists($img)) {

						$mimetype = $storage->mime_type($img);
						if ('full' != $this->getParam('v') && 'application/pdf' == $mimetype) {

							Response::serve(sprintf('%s/resources/images/acrobat.png', __DIR__));
						} elseif ('full' != $this->getParam('v') && 'video/quicktime' == $mimetype) {

							Response::serve(sprintf('%s/resources/images/mov-extension-filetype.png', __DIR__));
						} elseif ('full' != $this->getParam('v') && 'video/mp4' == $mimetype) {

							Response::serve(sprintf('%s/resources/images/mp4-extension-filetype.png', __DIR__));
						} else {

							$storage->serve($img);
						}
					} elseif ($storage->subFolder('queue')->file_exists($img)) {

						Response::serve(config::photolog_default_image800x600_inqueue);
					} else {

						logger::info(sprintf('<NOT found %s> %s', $img, __METHOD__));
					}
				}
			}
		} else {

			Response::serve(config::photolog_default_image800x600);
		}
	}	// 769

	public function js($lib = '') {
		$s = ['@{{route}}@'];
		$r = [strings::url($this->route)];

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
					'title' => $this->title = sprintf('%s - notepad', config::label),
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
					'aside' => array_merge(['index'], config::index_set),
					'dto' => $dto,
					'files' => $dao->getFiles($dto, $this->route),
					'pageUrl' => strings::url($this->route . '/view/' . $dto->id),
					'referer' => false,
					'searchFocus' => true,
					'title' => $this->title = config::label_view,
				];

				if ($referer = $this->getParam('f')) {

					$this->data->referer = (new dao\properties)->getByID($referer);
				}

				$this->renderBS5([
					'main' => fn () => $this->load('view')
				]);
			} else {

				print 'not found';
			}
		} else {

			print 'invalid';
		}
	}

	public function zip($id) {
		$debug = false;
		// $debug = true;

		if ($id = (int)$id) {

			$dao = new dao\property_photolog;
			if ($dto = $dao->getByID($id)) {

				$filename = sprintf('%sphotolog-%d.zip', config::tempdir(), $dto->id);
				if (file_exists($filename)) unlink($filename);

				if ($debug) logger::debug(sprintf('<%s> : %s', $filename, __METHOD__));

				$zip = new \ZipArchive;

				if ($zip->open($filename, \ZipArchive::CREATE) !== TRUE) {

					logger::info(sprintf('<cannot open %s> : %s', $filename, __METHOD__));
					printf('<cannot open archive> %s', __METHOD__);
				} else {

					$ifiles = 0;
					$storage = $dao->DiskFileStorage($dto->id, $create = false);
					if ($fit = $storage->FilesystemIterator()) {

						foreach ($fit as $file) {

							if (preg_match('@(jp[e]?g|mov|mp4|pdf)$@i', $file->getExtension())) {

								$files[] = (object)[
									'description' => $file->getFilename(),
									'path' => $file->getPathname()
								];

								if ($debug) logger::debug(sprintf('property_photolog/zip : adding <%s>', $file->getPathname()));
								$zip->addFile($file->getPathname(), $file->getFilename());
								$ifiles++;
							}
						}
					}

					if ($debug) logger::debug(sprintf('<numfiles : %s> %s', $zip->numFiles, __METHOD__));
					if ($debug) logger::debug(sprintf('<status : %s> %s', $zip->status, __METHOD__));

					$zip->close();

					if ($ifiles) {

						Response::serve($filename);
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
