<?php
/*
 * David Bray
 * BrayWorth Pty Ltd
 * e. david@brayworth.com.au
 *
 * MIT License
 *
*/

class config extends dvc\config {
  public static function cmsStore() {
		return self::dataPath() . '/';

	}

}
