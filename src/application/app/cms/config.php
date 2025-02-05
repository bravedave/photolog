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

  static public function route(string $path): string
  {

    $map = (object)[
      routes::photolog => 'photolog\controller'
    ];
    return (isset($map->{$path}) ? $map->{$path} : parent::route($path));
  }
}

config::$PORTAL = strings::url('', $protocol = true);
