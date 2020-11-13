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

	protected function posthandler() {
		$action = $this->getPost('action');

		if ( 'add-entry' == $action || 'update-entry' == $action) {
			if ( $property_id = $this->getPost( 'property_id')) {
				$a = [
					'updated' => db::dbTimeStamp(),
					'property_id' => $property_id,
					'subject' => $this->getPost( 'subject'),
					'date' => $this->getPost( 'date')

				];

				$dao = new dao\property_photolog;

				if ( 'update-entry' == $action) {
					if ( $id = (int)$this->getPost('id')) {
						$dao->UpdateByID( $a, $id);

						\Json::ack( $action)
							->add( 'id', $id);

					} else { \Json::nak( $action); }

				}
				else {
					$a['created'] = $a['updated'];
					$id = $dao->Insert( $a);

					\Json::ack( $action)
						->add( 'id', $id);

				}

			} else { \Json::nak( $action); }

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

}
