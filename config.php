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

use Json;

class config extends \config {
	const photolog_db_version = 0.01;

  static protected $_PHOTOLOG_VERSION = 0;

	static function photolog_checkdatabase() {
		if ( self::photolog_version() < self::photolog_db_version) {
      $dao = new dao\dbinfo;
			$dao->dump( $verbose = false);

			config::photolog_version( self::photolog_db_version);

		}

	}

	static function photolog_config() {
		return implode( DIRECTORY_SEPARATOR, [
      rtrim( self::dataPath(), '/ '),
      'photolog.json'

    ]);

  }

	static protected function photolog_version( $set = null) {
		$ret = self::$_PHOTOLOG_VERSION;

		if ( (float)$set) {
			$j = Json::read( $config = self::photolog_config());

			self::$_PHOTOLOG_VERSION = $j->photolog_version = $set;

			Json::write( $config, $j);

		}

		return $ret;

	}

}
