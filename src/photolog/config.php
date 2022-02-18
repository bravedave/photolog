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
	const photolog_default_image800x600 = __DIR__ . '/resources/images/default.png';
	const photolog_default_image800x600_inqueue = __DIR__ . '/resources/images/default-in-queue.png';

	const photolog_db_version = 0.01;

	const photolog_enable_heic = true;

	const photolog_prestamp = '-prestamp';

	const photolog_rotate_left = 1;
	const photolog_rotate_right = 2;
	const photolog_rotate_180 = 3;

	static $PHOTOLOG_STAMP = __DIR__ . '/resources/images/stamp.png';
	static $TAHOMA_TTF = __DIR__ . '/resources/tahoma.ttf';

	static function photolog_checkdatabase() {
		$dao = new dao\dbinfo(null, method_exists(__CLASS__, 'cmsStore') ? self::cmsStore() : self::dataPath());
		// // $dao->debug = true;
		$dao->checkVersion('photolog', self::photolog_db_version);
	}

	public static function photologStore() {
		$_path = method_exists(__CLASS__, 'cmsStore') ? self::cmsStore() : self::dataPath();
		$path = implode(DIRECTORY_SEPARATOR, [
			rtrim($_path, '/ '),
			'photolog/'
		]);

		if (!is_dir($path)) {
			mkdir($path, 0777);
			chmod($path, 0777);
		}

		return ($path);
	}
}
