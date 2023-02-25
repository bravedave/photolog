<?php
/*
 * David Bray
 * BrayWorth Pty Ltd
 * e. david@brayworth.com.au
 *
 * MIT License
 *
*/

namespace cms;

use photolog, bravedave, strings;

class config extends bravedave\dvc\config {

  public static function cmsStore() {

    return static::dataPath();
  }

  public static function photologStore() {

    return photolog\config::photologStore();
  }
}

config::$PORTAL = strings::url('', $protocol = true);
