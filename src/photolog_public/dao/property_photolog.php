<?php
/*
 * David Bray
 * BrayWorth Pty Ltd
 * e. david@brayworth.com.au
 *
 * MIT License
 *
*/

namespace photolog_public\dao;

use dvc\dao\_dao;

class property_photolog extends _dao {
  protected $_db_name = 'property_photolog';

  function getByLink(string $k) {
    if ($k) {
      $sql = sprintf('SELECT * FROM `%s` WHERE `public_link` = "%s"', $this->_db_name, $this->escape($k));
      if ($res = $this->Result($sql)) {
        if ($dto = $res->dto()) {
          if (strtotime($dto->public_link_expires) > time()) {
            return $dto;
          }
        }
      }
    }

    return false;
  }

  public function store($id) {
    if (\config::photologStore()) {
      $path = implode(DIRECTORY_SEPARATOR, [
        \config::photologStore(),
        (int)$id

      ]);
      // \sys::logger( sprintf('<%s> %s', $path, __METHOD__));
      return realpath($path);
    }

    return false;
  }
}
