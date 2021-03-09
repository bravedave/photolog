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

use strings;
use sys;

class utility {
	protected static function _getstamp() {
    $src = config::$PHOTOLOG_STAMP;
		$cacheStamp = sprintf( '%scache-photolog-stamp.png', config::tempdir());

		if ( file_exists( $cacheStamp) && filemtime( $cacheStamp) > filemtime( $src)) {
			return ( imagecreatefrompng( $cacheStamp));

		}

		list($width, $height) = getimagesize($src);
		$r = $width / $height;
		$w = 240;
		$h = 52;
		if ($w/$h > $r) {
			$newwidth = $h*$r;
			$newheight = $h;
		}
		else {
			$newheight = $w/$r;
			$newwidth = $w;
		}

		$srcImg = imagecreatefrompng( $src);
		$dstImg = imagecreatetruecolor($newwidth, $newheight);

		// enable alpha blending on the destination image.
		imagealphablending( $dstImg, true);

		// Allocate a transparent color and fill the new image with it.
		// Without this the image will have a black background instead of being transparent.
		$transparent = imagecolorallocatealpha( $dstImg, 0, 0, 0, 127 );
		imagefill( $dstImg, 0, 0, $transparent );

		imagecopyresampled($dstImg, $srcImg, 0, 0, 0, 0, $newwidth, $newheight, $width, $height);

		imagealphablending( $dstImg, false);

		// save the alpha
		imagesavealpha( $dstImg, true);

		//~ $bg = imagecolorat($dstImg, 5, 5);
		//~ imagecolorset( $dstImg, $bg, 255, 255, 255, 192 );

		if ( file_exists( $cacheStamp ))
			unlink( $cacheStamp );

		imagepng( $dstImg, $cacheStamp, 0);

		//~ return ( $srcImg);
		return ( $dstImg);

	}

	protected static function _imagecopymerge_alpha($dst_im, $src_im, $dst_x, $dst_y, $src_x, $src_y, $src_w, $src_h, $pct){
		$debug = false;
		//~ $debug = true;
		//~ $debug = \currentUser::isDavid();

		/**
		* PNG ALPHA CHANNEL SUPPORT for imagecopymerge();
		* by Sina Salek
		*
		* Bugfix by Ralph Voigt (bug which causes it
		* to work only for $src_x = $src_y = 0.
		* Also, inverting opacity is not necessary.)
		* 08-JAN-2011
		*
		* http://php.net/manual/en/function.imagecopymerge.php#92787
		**/

		// creating a cut resource
		$cut = imagecreatetruecolor($src_w, $src_h);

		// copying relevant section from background to the cut resource
		imagecopy($cut, $dst_im, 0, 0, $dst_x, $dst_y, $src_w, $src_h);

		// copying relevant section from watermark to the cut resource
		if ( !imagecopy($cut, $src_im, 0, 0, $src_x, $src_y, $src_w, $src_h)) {
			if ( $debug) sys::logger( sprintf( '<imagecopy : failed> %s', __METHOD__));

		} else { if ( $debug) sys::logger( sprintf( '<imagecopy : success> %s', __METHOD__)); }

		// insert cut resource to destination image
		if ( !imagecopymerge($dst_im, $cut, $dst_x, $dst_y, 0, 0, $src_w, $src_h, $pct)) {
			sys::logger( sprintf( '<imagecopymerge : failed> %s', __METHOD__));

		} else { if ( $debug) sys::logger( sprintf( '<imagecopymerge : success> %s', __METHOD__)); }

	}

	protected static function _stamp( $src, $target, $date = 0) {
		$debug = false;
		//~ $debug = TRUE;
		//~ $debug = currentUser::isDavid();

		$parts = pathinfo( $src);
		$errfile = sprintf( '%s/%s.err',
			$parts['dirname'],
			$parts['filename']
			);
		if ( file_exists( $errfile)) return false;

		try {
			list( $width, $height) = getimagesize( $src);
			if ( 'png' == strtolower( $parts['extension'])) {
				$srcImg = imagecreatefrompng( $src);

			}
			else {
				$exif = @exif_read_data( $src);
				$srcImg = imagecreatefromjpeg( $src);

			}

		}
		catch ( \Exception $e) {
			sys::logger( sprintf( '<error %s> %s', $src, __METHOD__));
			file_put_contents( $errfile, $e->getMessage());

			//~ throw $e;
			return;

		}

		if ( isset($exif['Orientation'])) {
			if (!empty($exif['Orientation'])) {
				//~ sys::logger( $exif['Orientation']);
				switch ($exif['Orientation']) {
					case 3:
						$srcImg = imagerotate( $srcImg, 180, 0);
						break;

					case 6:
						$srcImg = imagerotate( $srcImg, -90, 0);
						$_width = $width;
						$width = $height;
						$height = $_width;
						break;

					case 8:
						$srcImg = imagerotate( $srcImg, 90, 0);
						$_width = $width;
						$width = $height;
						$height = $_width;
						break;

				}

			}

		}
		else {
			if ( $debug) sys::logger( sprintf( '<exif %s> %s', json_encode( $exif), __METHOD__));

		}

		if ( $debug) sys::logger( sprintf( '<%s x %s> %s', $width, $height, __METHOD__));

		//~ $w = 2000;
		//~ $h = 1500;
		if ( $width < $height) {
			$h = 1024;
			$w = 768;

		}
		else {
			$w = 1024;
			$h = 768;

		}

		if ( $width < $w && $height < $h) {
			// you will need to grow the image and recalculate
			$percent = max( [ $w / $width, $h / $height]);
			if ( $debug) sys::logger( sprintf( '<grow %s> %s', $percent, __METHOD__));

			$newwidth = $width * $percent;
			$newheight = $height * $percent;

			$newSrc = imagecreatetruecolor( $newwidth, $newheight);
			imagecopyresized( $newSrc, $srcImg, 0, 0, 0, 0, $newwidth, $newheight, $width, $height);
			if ( $debug) sys::logger( sprintf( '<grow image to width x height : %s x %s> %s', $newwidth, $newheight, __METHOD__));

			imagedestroy( $srcImg);

			$srcImg = $newSrc;
			$width = $newwidth;
			$height = $newheight;

		}

		if ( $debug) sys::logger( sprintf( '<old  image width x height : %s x %s> %s', $width, $height, __METHOD__));

		$r = $width / $height;
		if ($w/$h > $r) {
			$newwidth = $h*$r;
			$newheight = $h;

		}
		else {
			$newheight = $w/$r;
			$newwidth = $w;

		}

		if ( $debug) sys::logger( sprintf( '<new image width x height : %s x %s (%s)> %s', $newwidth, $newheight, $r, __METHOD__));

		$srcX = 0;
		$srcY = 0;

		if ( $newwidth < $w) {
			$newheight = $w / $newwidth * $newheight;
			$newwidth = $w;
			$srcY = (int)( $newheight - $h) / 2;	// crop vertically

		}
		elseif ( $newheight < $h) {
			$newwidth = $h / $newheight * $newwidth;
			$newheight = $h;
			$srcX = (int)( $newwidth - $w) / 2;	// crop horizontally
		}

		if ( $debug) sys::logger( sprintf( '<new image width x height : %s x %s> %s', $newwidth, $newheight, __METHOD__));
		if ( $debug) sys::logger( sprintf( '<old image srcX x srcY : %s x %s> %s', $srcX, $srcY, __METHOD__));

		$stamp = self::_getstamp();	// Load the stamp and the photo to apply the watermark to

		//~ $dstImg = imagecreatetruecolor($newwidth, $newheight);	// scaling
		$dstImg = imagecreatetruecolor($w, $h);	// cropping
		if ( $debug) sys::logger( sprintf( '<dst image width x height : %s x %s> %s', $w, $h, __METHOD__));

		imagealphablending( $dstImg, true);	// enable alpha blending on the destination image.

		//~ imagecopyresampled($dstImg, $srcImg, 0, 0, 0, 0, $newwidth, $newheight, $width, $height);	// scaling
		imagecopyresampled($dstImg, $srcImg, 0, 0, (int)$srcX, (int)$srcY, (int)$newwidth, (int)$newheight, (int)$width, (int)$height);	// cropping

		// Set the margins for the stamp and get the height/width of the stamp image
		$marge_right = 20;
		$marge_bottom = 40;
		$sx = imagesx($stamp);
		$sy = imagesy($stamp);
		$dst_x =  imagesx($dstImg) - $sx - $marge_right;
		$dst_y = imagesy($dstImg) - $sy - $marge_bottom;

		// Copy the stamp image onto our photo using the margin offsets and the photo width to calculate positioning of the stamp.
		//~ imagecopyresampled($dstImg, $stamp, $dst_x, $dst_y, 0, 0, $sx, $sy, $sx, $sy);
		self::_imagecopymerge_alpha($dstImg, $stamp, imagesx($dstImg) - $sx - $marge_right, imagesy($dstImg) - $sy - $marge_bottom, 0, 0, imagesx($stamp), imagesy($stamp), 75);

		imagealphablending( $dstImg, false);
		imagesavealpha( $dstImg, true);	// save the alpha

		$textcolor = imagecolorallocate($dstImg, 255, 255, 255);							// allocate white for the text
		$font_path = __DIR__ . '/resources/tahoma.ttf';	// set path to font file
		//~ imagettftext( $dstImg, 14, 0, $newwidth-360, $newheight-10, $textcolor, $font_path, date( \config::$DATETIME_FORMAT));	// print text on image
		$timestamp = date( config::$DATETIME_FORMAT);
		if ( strtotime( $date) > 0) {
			$timestamp = strings::asLocalDate( $date);

		}
		if ( $debug) sys::logger( sprintf( '<timestamp: %s> %s', $timestamp, __METHOD__));

		imagettftext( $dstImg, 14, 0, $dst_x, $newheight-10, $textcolor, $font_path, $timestamp);	// print text on image

		imagejpeg( $dstImg, $target, 90);
		// chmod( $target, 0666);
		imagedestroy( $srcImg);
		imagedestroy( $dstImg);
		imagedestroy( $stamp);

		return true;

	}

	public static function stamp() {
		$bypass = false;
		//~ $bypass = true;

		if ( $bypass) {
      sys::logger( sprintf('<%s> %s', 'on bypass', __METHOD__));
			return;

		}

		$debug = false;
		//~ $debug = true;
		/**
		 * finds queued photologs, resizes and watermarks them deleting the original
		 *
		 *	we process 10 files every minute, 600 / hour, 1440 / day
		 */

		$second = ( date('G') * 3600) + ( date('i') * 60);
		$afterHours = ( $second) >= 63000;	// 5.30

		//~ $limit = $afterHours ? 20 : 10;
		$limit = $afterHours ? 16 : 8;
		//~ $limit = $afterHours ? 20 : 5;
		$icount = 0;	// count won't raise above $limit

		$dao = new dao\property_photolog;
		if ( $res = $dao->Result( 'SELECT id, date FROM property_photolog')) {
			$res->dtoSet( function( $dto) use ($dao, &$icount, $limit, $afterHours, $debug) {

				if ( $icount >= $limit) return;

				$path = $dao->store( $dto->id);

				if ( is_dir( $path)) {
					$files = new \FilesystemIterator( $path . '/queue');
					foreach($files as $file) {
						if ( 'heic' == $file->getExtension()) {
							\sys::logger( sprintf('<%s> %s', 'heic file', __METHOD__));
							$imagick = new \Imagick;
							$jpg = \preg_replace( '@\.heic$@i', '.jpg', $file->getPathname());
							$imagick->readImage($file->getPathname());
							$imagick->writeImage($jpg);

							unlink( $file->getPathname());

						}

					}

					$files = new \FilesystemIterator( $path . '/queue');
					foreach($files as $file) {
						if ( preg_match( '@(jp[e]?g|jfif|png)$@i', $file->getExtension())) {
							if ( 10 < $file->getSize()) {
								$stamped = sprintf( '%s/%s', $path, $file->getFilename());
								if ( preg_match( '@\.jfif$@', $stamped)) {
									$stamped = preg_replace( '@\.jfif$@', '.jpg', $stamped);

								}

								$src = $file->getPathname();

								if ( file_exists( $stamped )) unlink( $stamped );
								if ( self::_stamp( $src, $stamped, $dto->date)) {
									if ( file_exists( $stamped )) unlink( $src );
									if ( ++$icount >= $limit) break;
									sleep( $afterHours ? 1 : 3);

								}
								else {
									if ( $debug) sys::logger( sprintf( '<did not stamp %d : %s => %s> %s', $dto->id, $file->getFilename(), $stamped, __METHOD__));

								}

							}

						}

					}

				}
				else {
					if ( $debug) sys::logger( sprintf( 'path %s is not dir : %s', $path, __METHOD__));

				}

			});

		}

		if ( $debug || $icount) sys::logger( sprintf( '<processed %s> %s', $icount, __METHOD__));

	}

	public static function stampone( $src, $stamped, $dto) {
		if ( self::_stamp( $src, $stamped, $dto->date)) {
			if ( file_exists( $stamped )) unlink( $src );

		}

	}

}
