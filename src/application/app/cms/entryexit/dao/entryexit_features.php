<?php
/*
 * David Bray
 * BrayWorth Pty Ltd
 * e. david@brayworth.com.au
 * 
 * MIT License
 *
*/

namespace cms\entryexit\dao;

use bravedave\dvc\{dao, dtoSet, logger};

class entryexit_features extends dao {
  protected $_db_name = 'entryexit_features';
  protected $template = dto\entryexit_features::class;

  public function getMatrix(bool $archived = false): array {

    $where = '';
    if (!$archived) $where = 'WHERE f.`archived` = 0';

    $sql = sprintf('SELECT 
        f.*,
        pr.`name` room_name
      FROM `entryexit_features` f
        LEFT JOIN `property_rooms` pr ON pr.`id` = f.`property_rooms_id`
      %s
      ORDER BY f.`order` ASC, f.`id` ASC', $where);
    return (new dtoSet)($sql, null, $this->template);
  }
}
