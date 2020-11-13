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
  const default_image800x600 =  __DIR__ . '/default.png';
  const default_image800x600_inqueue =  __DIR__ . '/default-in-queue.png';

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

	public static function photologStore() {
		$path = self::cmsStore() . 'photolog/';

		if ( ! is_dir( $path)) {
			mkdir( $path, 0777);
			chmod( $path, 0777);

		}

		return ( $path);

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

  static function photolog_init() {
    $_a = [
      'photolog_version' => self::$_PHOTOLOG_VERSION,

    ];

		if ( file_exists( $config = self::photolog_config())) {

      $j = (object)array_merge( $_a, (array)Json::read( $config));

      self::$_PHOTOLOG_VERSION = (float)$j->photolog_version;

		}

  }

}

config::photolog_init();
