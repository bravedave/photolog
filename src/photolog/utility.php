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

use Intervention\Image\ImageManagerStatic;
use bravedave\dvc\{dtoSet, logger};
use cms\{currentUser, strings};
use DateTime;
use DateTimeZone;
use DirectoryIterator;
use FilesystemIterator;

class utility {

	protected static function _getcachestampPath(): string {
		/* the stampe has to be right size ... */
		$cacheStamp = sprintf('%scache-photolog-stamp.png', config::tempdir());

		if (!file_exists($cacheStamp) || filemtime($cacheStamp) <= filemtime(config::$PHOTOLOG_STAMP)) {

			list($stampwidth, $stampheight) = getimagesize(config::$PHOTOLOG_STAMP);
			$r = $stampwidth / $stampheight;
			$w = 240;
			$h = 52;
			$newwidth = ($w / $h > $r) ? $h * $r : $w;

			$img = ImageManagerStatic::make(config::$PHOTOLOG_STAMP);
			$img
				->orientate()
				->resize($newwidth, null, function ($constraint) {
					$constraint->aspectRatio();
				});

			$img->save($cacheStamp);
		}
		/* end the stamp has to be right size ... */

		return $cacheStamp;
	}

	protected static function _stamp_intervention($src, $target, $date = 0) {
		$debug = false;
		//~ $debug = TRUE;
		//~ $debug = currentUser::isDavid();

		$parts = pathinfo($src);
		$errfile = sprintf(
			'%s/%s.err',
			$parts['dirname'],
			$parts['filename']
		);
		if (file_exists($errfile)) return false;

		$cacheStamp = self::_getcachestampPath();

		$timestamp = filemtime($src);

		$exif = @exif_read_data($src);
		if (isset($exif['DateTime'])) {
			/**
			 * https://cmss.darcy.com.au/forum/view/9432 - photolog metadata
			 *
			 * we are going to assume the timezone is local
			 * the timezone is in the exif, but it is read as
			 * UndefinedTag:0x9010
			 * in Exif Version 0232
			 */
			$dt = new DateTime($exif['DateTime'], new DateTimeZone(config::$TIMEZONE));
			$timestamp = $dt->format('U');
		}

		$img = ImageManagerStatic::make($src);	// open an image file
		$h = $img->height();
		$w = $img->width();

		$stampSize = 800;
		// $stampSize = 1024;

		/**
		 * now you are able to resize the instance
		 * if it's a landscape picture, resize with height constraint
		 */
		if ($w > $h) {

			$img
				->orientate()
				->resize(null, $stampSize, function ($constraint) {
					$constraint->aspectRatio();
				});
		} else {

			$img
				->orientate()
				->resize($stampSize, null, function ($constraint) {
					$constraint->aspectRatio();
				});
		}

		$prestamp = $target . config::photolog_prestamp;
		if (file_exists($prestamp)) {
			unlink($prestamp);	// break any hard links
			clearstatcache();
		}
		$img->save($prestamp, null, 'jpg');
		touch($prestamp, $timestamp);
		chmod($prestamp, 0666);

		$img->insert($cacheStamp, 'bottom-right', 10, 40);
		$dt = new DateTime('now', new DateTimeZone(config::$TIMEZONE));
		$dt->setTimestamp($timestamp);
		$img->text($dt->format(config::$DATETIME_FORMAT), $img->width() - 200, $img->height() - 16, function ($font) {
			$font->file(config::$TAHOMA_TTF);
			$font->size(20);
			$font->color('#fff');
			// $font->align('center');
			// $font->valign('top');
			// $font->angle(45);
		});

		if (file_exists($target)) {
			unlink($target);	// break any hard links
			clearstatcache();
		}

		$img->save($target);
		chmod($target, 0666);
		return true;
	}

	static public function garbageCollection() {
		$debug = false;
		// $debug = true;

		// return;

		$it = new DirectoryIterator(config::photologStore());
		foreach ($it as $obj) {

			// exclude . and .. directories
			if ($obj->isDot()) continue;
			if ($obj->isDir()) {

				if ($debug) logger::debug(sprintf('<%s> %s', $obj->getFilename(), __METHOD__));
				$sit = new FilesystemIterator($obj->getPathname());
				foreach ($sit as $sobj) {

					if (str_ends_with($sobj->getFilename(), '-prestamp')) {

						// delete these if they are older than 14 days
						if ($sobj->getMTime() < strtotime('-30 days')) {

							if ($debug) logger::debug(sprintf(
								'<cleanup %s - %s is older than %s...> %s',
								$sobj->getFilename(),
								date('Y-m-d H:i:s', $sobj->getMTime()),
								date('Y-m-d H:i:s', strtotime('-30 days')),
								__METHOD__
							));
							// unlink($sobj->getPathname());
						}
					}
				}
			}
		}
	}

	static public function heicConvert(string $path) {

		$debug = false;
		// $debug = true;
		// $debug = currentUser::isDavid();

		// process all heic files
		$files = new FilesystemIterator($path);
		foreach ($files as $file) {
			// logger::info( sprintf('<%s> %s', $file->getExtension(), __METHOD__));

			/**
			 * Process all heic files
			 */
			if ('heic' == strtolower($file->getExtension())) {

				// logger::info(sprintf('<%s> %s', 'heic file', __METHOD__));
				try {

					if ($debug) logger::debug(sprintf('<convert %s> %s', $file->getPathname(), logger::caller()));

					$imagick = new \Imagick;
					$jpg = \preg_replace('@\.heic$@i', '.jpg', $file->getPathname());
					$imagick->readImage($file->getPathname());
					$imagick->writeImage($jpg);

					unlink($file->getPathname());

					if ($debug) logger::debug(sprintf('<converted %s> %s', $file->getPathname(), logger::caller()));
				} catch (\Throwable $th) {

					logger::info(sprintf('<%s> %s', $file->getPathname(), logger::caller()));
					throw $th;
				}
			}
		}

		if ($debug) logger::debug(sprintf('<completed %s> %s', $path, logger::caller()));
	}

	public static function rotate(string $src, int $direction): bool {

		$prestamp = $src . config::photolog_prestamp;
		if (file_exists($prestamp)) {

			$cacheStamp = self::_getcachestampPath();

			$img = ImageManagerStatic::make(file_get_contents($prestamp));	// open an image file

			if (config::photolog_rotate_left == $direction) {
				$img->rotate(90);
			} elseif (config::photolog_rotate_right == $direction) {
				$img->rotate(270);
			} elseif (config::photolog_rotate_180 == $direction) {
				$img->rotate(180);
			} else {
				return false;
			}

			$timestamp = filemtime($prestamp);
			if (file_exists($prestamp)) {
				unlink($prestamp);	// break any hard links
				clearstatcache();
			}
			$img->save($prestamp, null, 'jpg');
			touch($prestamp, $timestamp);
			chmod($prestamp, 0666);

			$img->insert($cacheStamp, 'bottom-right', 10, 40);
			$dt = new DateTime('now', new DateTimeZone(config::$TIMEZONE));
			$dt->setTimestamp($timestamp);
			$img->text($dt->format(config::$DATETIME_FORMAT), $img->width() - 200, $img->height() - 16, function ($font) {
				$font->file(config::$TAHOMA_TTF);
				$font->size(20);
				$font->color('#fff');
				// $font->align('center');
				// $font->valign('top');
				// $font->angle(45);
			});

			if (file_exists($src)) {
				unlink($src);	// break any hard links
				clearstatcache();
			}

			$img->save($src);
			chmod($src, 0666);

			return true;
		}

		return false;
	}

	public static function stamp() {
		$bypass = false;
		// $bypass = true;

		if ($bypass) {
			logger::info(sprintf('<%s> %s', 'on bypass', __METHOD__));
			return;
		}

		$debug = false;
		//~ $debug = true;
		/**
		 * finds queued photologs, resizes and watermarks them deleting the original
		 *
		 *	we process 10 files every minute, 600 / hour, 1440 / day
		 */

		$second = (date('G') * 3600) + (date('i') * 60);
		$afterHours = ($second) >= 63000;	// 5.30
		$icount = 0;	// count won't raise above $limit

		(new dtoSet)(
			'SELECT id, date FROM property_photolog',
			function ($dto) use (&$icount, $afterHours, $debug) {

				$limit = $afterHours ? 24 : 16;
				// $limit = $afterHours ? 20 : 12;
				// $limit = $afterHours ? 20 : 10;
				// $limit = $afterHours ? 16 : 8;

				if ($icount >= $limit) return;

				$path = (new dao\property_photolog)->store($dto->id);

				if (is_dir($path)) {

					$i_will_wait = 3;
					if (file_exists($path . '/queue/.upload-in-progress')) {

						while ($i_will_wait > 0) {

							if (file_exists($path . '/queue/.upload-in-progress')) {
								$i_will_wait--;
								unlink($path . '/queue/.upload-in-progress');
								clearstatcache();

								// logger::info(sprintf('<defer - upload in progress> %s', __METHOD__));
								sleep(3);
							} else {

								$i_will_wait = 0;
							}
						}

						if (file_exists($path . '/queue/.upload-in-progress')) {

							logger::info(sprintf('<break - upload in progress> %s', __METHOD__));
							return;
						}
					}


					self::heicConvert($path . '/queue');

					$files = new FilesystemIterator($path . '/queue');
					foreach ($files as $file) {

						if (preg_match('@(jp[e]?g|jfif|png)$@i', $file->getExtension())) {

							if (10 < $file->getSize()) {

								$stamped = sprintf('%s/%s', $path, $file->getFilename());
								if (preg_match('@\.jfif$@', $stamped)) {
									$stamped = preg_replace('@\.jfif$@', '.jpg', $stamped);
								}

								$src = $file->getPathname();

								if (file_exists($stamped)) {

									unlink($stamped);
									clearstatcache();
								}

								if (self::_stamp_intervention($src, $stamped, $dto->date)) {

									if (file_exists($stamped)) {

										unlink($src);
										clearstatcache();
									}

									if (++$icount >= $limit) break;
									sleep($afterHours ? 1 : 2);
								} else {

									if ($debug) logger::debug(sprintf('<did not stamp %d : %s => %s> %s', $dto->id, $file->getFilename(), $stamped, __METHOD__));
								}
							}
						}
					}
				} else {

					if ($debug) logger::debug(sprintf('path %s is not dir : %s', $path, __METHOD__));
				}
			}
		);

		if ($icount) logger::info(sprintf('<processed %s> %s', $icount, __METHOD__));
	}

	public static function stampone($src, $stamped, $dto) {
		if (self::_stamp_intervention($src, $stamped, $dto->date)) {
			if (file_exists($stamped)) unlink($src);
		}
	}
}
