<?php
/*
 * David Bray
 * BrayWorth Pty Ltd
 * e. david@brayworth.com.au
 * 
 * MIT License
 *
*/

namespace cms\entryexit;

use cms;

class config extends cms\config {
  
  static function cms_entryexit_store(): string {

    $_path = method_exists(__CLASS__, 'cmsStore') ? self::cmsStore() : self::dataPath();
    $store = implode(DIRECTORY_SEPARATOR, [
      rtrim($_path, '/'),
      'cms_entryexit_entry'
    ]);

    if (!is_dir($store)) mkdir($store);
    return $store;
  }
}
