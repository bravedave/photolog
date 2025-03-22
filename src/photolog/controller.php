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

use bravedave\dvc\{logger, Response, ServerRequest};
use cms\{currentUser, strings, userrestrictions};

use cms;

class controller extends cms\controller {

	protected function _index() {

		if ($pid = (int)$this->getParam('property')) {

			$this->data = (object)[
				'aside' => array_merge(['index'], config::index_set),
				'help' => cms\docs\config::help_photolog,
				'dtoSet' => (new dao\property_photolog)->getForProperty($pid),
				'referer' => $referer = (new dao\properties)->getByID($pid),
				'pageUrl' => strings::url($this->route . '/?property=' . $pid),
				'searchFocus' => false,
				'title' => $this->title = config::label,
			];

			if ($referer) {

				$this->data->title = sprintf(
					'%s : %s',
					$referer->address_street,
					$this->title
				);
			}

			$scripts = [sprintf('<script src="%s"></script>', strings::url($this->route . '/js'))];
			// array_walk($scripts, fn ($_) => logger::info(sprintf('has script tag %s', preg_match('/^<script/', $_) ? 'yes' : 'no')));
			$this->renderBS5([
				'main' => fn() => $this->load('report'),
				'scripts' => $scripts,
			]);
		} else {

			$dao = new dao\property_photolog;
			$this->data = (object)[
				'aside' => array_merge(['index'], config::index_set),
				'dtoSet' => $dao->getPropertySummary(),
				'help' => cms\docs\config::help_photolog,
				'referer' => false,
				'pageUrl' => strings::url($this->route),
				'searchFocus' => false,
				'title' => $this->title = config::label,
				'bootstrap' => '5',
			];

			// logger::info( sprintf('<%s> %s', application::timer()->elapsed(), __METHOD__));
			$this->renderBS5([
				'main' => fn() => $this->load('summary'),
				'scripts' => [sprintf(
					'<script type="text/javascript" src="%s"></script>',
					strings::url($this->route . '/js')
				)]
			]);
		}
	}

	protected function access_control() {

		if (currentUser::restriction(userrestrictions::photolog) == 'yes') return true;
		if (currentUser::restriction(userrestrictions::smokeAlarm) == 'yes') return true;
		return parent::access_control();
	}

	protected function before() {

		config::photolog_checkdatabase();
		parent::before();
		$this->viewPath[] = __DIR__ . '/views/';
	}

	protected function posthandler() {

		$request = new ServerRequest;
		$action = $request('action');

		/*
			_brayworth_.fetch.post(_brayworth_.url('property_photolog'), {
				action : 'property-smokealarms',
				id : 3694
			}).then(console.log);

			_brayworth_.fetch.post(_brayworth_.url('photolog'), {
				'action' : 'cron',
			}).then(console.log);

			_brayworth_.fetch.post( _brayworth_.url( 'property_photolog'),{
					action : 'get-photolog',
					property : 2
			}).then( ('ack' == d.response) ? console.table( d.data) : _brayworth_.growl( d));

			_brayworth_.fetch.post( _brayworth_.url( 'property_photolog'),{
					action : 'get-photolog-file',
					id : 16240,
					file : 'IMG_2547.jpeg'
			}).then(console.log);
		*/
		return match ($action) {
			'add-entry' => handler::save($request),
			'analyse-damage' => handler::analyseDamage($request),
			'analyse-damage-reprocess' => handler::analyseDamageReprocess($request),
			'cron' => handler::cron($request),
			'delete' => handler::delete($request),
			'delete-entry' => handler::deleteEntry($request),
			'entry-condition-report-set' => handler::entryConditionReportSet($request),
			'get-photolog' => handler::getPhotolog($request),
			'get-photolog-file' => handler::getPhotologFile($request),
			'openai-cache-file-delete' => handler::openaiCacheFileDelete($request),
			'openai-cache-file-exists' => handler::openaiCacheFileExists($request),
			'photolog-tag-clear' => handler::tagClear($request),
			'photolog-tag-to-room' => handler::tagToRoom($request),
			'property-smokealarms' => handler::propertySmokeAlarms($request),
			'public-link-clear' => handler::publicLinkClear($request),
			'public-link-create' => handler::publicLinkCreate($request),
			'public-link-get' => handler::publicLinkGet($request),
			'rename-file' => handler::renameFile($request),
			'rotate-left' => handler::rotate($request),
			'rotate-right' => handler::rotate($request),
			'rotate-180' => handler::rotate($request),
			'save-notepad' => handler::saveNotepad($request),
			'search-properties' => handler::searchProperties($request),
			'set-alarm-location' => handler::setAlarmLocation($request),
			'set-alarm-location-clear' => handler::setAlarmLocationClear($request),
			'photolog-set-associated-entry-condition-report' => handler::setAssociatedEntryConditionReport($request),
			'touch' => handler::touch($request),
			'update-entry' => handler::save($request),
			'upload' => handler::upload($request),
			default => parent::postHandler()
		};
	}

	public function entry($id = 0) {

		$this->data = (object)[
			'dto' => (object)[
				'id' => 0,
				'property_id' => 0,
				'address_street' => '',
				'subject' => '',
				'date' => date('Y-m-d'),
			],
			'title' => $this->title = 'add entry'
		];

		if ((int)$id > 0) {

			$dao = new dao\property_photolog;
			if ($dto = $dao($id)) {

				$this->data->title = $this->title = 'edit entry';
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

	public function menu() {

		$this->data = (object)[
			'bootstrap' => 5
		];

		$set = config::index_set;
		print "\t\t\t<div class=\"sidebar pt-3 pb-5\"><!-- theme start -->\n";
		array_walk($set, fn($e) => $this->load($e));
		print "\n\t\t\t</div><!-- theme end -->\n";
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

		$js = file_get_contents(__DIR__ . '/js/custom.js');
		$js = preg_replace($s, $r, $js);

		Response::javascript_headers();
		print $js;
	}

	public function notepad($id) {

		if ($id = (int)$id) {

			$dao = new dao\property_photolog;
			if ($dto = $dao($id)) {

				$this->data = (object)[
					'title' => $this->title = sprintf('%s - notepad', config::label),
					'dto' => $dto,
				];

				$this->load('notepad');
				return;
			}
		}
		print 'invalid';
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
				return;
			}
		}
		$this->load('invalid');
	}

	public function view($id = 0) {

		if ($id = (int)$id) {

			$dao = new dao\property_photolog;
			if ($dto = $dao($id)) {

				/** @var dao\dto\property_photolog $dto */

				$enableAI = false;
				$verbatimAI = 'not enabled';
				if ($dto->entryexit_entry_conditions_reports_id) {

					$daoEECR = new cms\entryexit\dao\entryexit_entry_conditions_reports;
					if ($eecr = $daoEECR($dto->entryexit_entry_conditions_reports_id)) {

						/** @var cms\entryexit\dao\dto\entryexit_entry_conditions_reports $eecr */

						if ($eecr->issued_by < 1) {

							if (! $eecr->tenant_signed) {

								$enableAI = true;
								$verbatimAI = 'enabled';
							} else {

								$verbatimAI = 'tenant signed - ai disabled';
							}
						} else {

							$verbatimAI = 'issued to tenant - ai disabled';
						}
					}
				}

				$this->data = (object)[
					'aside' => ['index'],
					'dto' => $dto,
					'enableAI' => $enableAI,
					'verbatimAI' => $verbatimAI,
					'files' => $dao->getFiles($dto, $this->route),
					'help' => cms\docs\config::help_photolog,
					'pageUrl' => strings::url($this->route . '/view/' . $dto->id),
					'referer' => false,
					'rooms' => (new cms\dao\property_rooms)->getMatrix($id),
					'searchFocus' => true,
					'title' => $this->title = config::label_view,
				];

				if ($dto->address_street) {

					$this->data->title = $this->title = strings::GoodStreetString($dto->address_street) . ' - ' . config::label;
				}


				if ($referer = $this->getParam('f')) {

					$this->data->referer = (new dao\properties)->getByID($referer);
				}

				// logger::dump($dto->property_photolog_rooms_tags);

				$this->renderBS5([
					'main' => fn() => $this->load('view')
				]);
			} else print 'not found';
		} else print 'invalid';
	}

	public function zip($id) {
		$debug = false;
		// $debug = true;

		if ($id = (int)$id) {

			$dao = new dao\property_photolog;
			if ($dto = $dao($id)) {

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
