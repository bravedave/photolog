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
  const cms_db_version = 1;

  const property_rooms_unknown = 0;
  const property_rooms_entry = 1;
  const property_rooms_lounge = 2;
  const property_rooms_family = 3;
  const property_rooms_kitchen = 4;
  const property_rooms_dining = 5;
  const property_rooms_bedroom_1 = 6;
  const property_rooms_bedroom_2 = 7;
  const property_rooms_bedroom_3 = 8;
  const property_rooms_bedroom_4 = 9;
  const property_rooms_ensuite = 10;
  const property_rooms_bathroom = 11;
  const property_rooms_toilet = 12;
  const property_rooms_laundry = 13;
  const property_rooms_general = 14;
  const property_rooms_outdoor = 15;
  const property_rooms_reserved_1 = 16;
  const property_rooms_reserved_2 = 17;
  const property_rooms_reserved_3 = 18;
  const property_rooms_reserved_4 = 19;
  const property_rooms_bedroom_5 = 20;
  const property_rooms_bedroom_6 = 21;
  const property_rooms_bedroom_7 = 22;
  const property_rooms_bedroom_8 = 23;
  const property_rooms_bathroom_2 = 24;
  const property_rooms_bathroom_3 = 25;
  const property_rooms_bathroom_4 = 26;
  const property_rooms_media_room = 27;
  const property_rooms_study = 28;
  const property_rooms_hallway_1 = 29;
  const property_rooms_hallway_2 = 30;
  const property_rooms_rumpus_room = 31;
  const property_rooms_internal_stairs = 32;
  const property_rooms_store_room = 33;
  const property_rooms_garage_carport = 34;
  const property_rooms_granny_flat = 35;

  // static $FREE_DISKSPACE_THRESHHOLD = 10485760000; // 10G
  static $FREE_DISKSPACE_THRESHHOLD = 2097152000; // 2G

  public static function checkdatabase() {

    $dao = new dao\dbinfo(null, method_exists(__CLASS__, 'cmsStore') ? self::cmsStore() : self::dataPath());
    // // $dao->debug = true;
    $dao->checkVersion('cms', self::cms_db_version);
  }

  public static function cmsStore() {

    return static::dataPath();
  }

  public static function photologStore() {

    return photolog\config::photologStore();
  }

  static public function route(string $path): string {

    $map = (object)[
      routes::photolog => 'photolog\controller'
    ];
    return (isset($map->{$path}) ? $map->{$path} : parent::route($path));
  }
}

config::$PORTAL = strings::url('', $protocol = true);
