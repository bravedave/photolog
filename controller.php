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
					'files' => $dao->getFiles( $dto),
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

}
