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

		if ( 'gibblegok' == $action) {
			\Json::ack( $action);

		}
		else {
			parent::postHandler();

		}

	}

}
