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

use bravedave\dvc\{dao, dtoSet};

class entryexit_entry_conditions_report_features extends dao {
  protected $_db_name = 'entryexit_entry_conditions_report_features';
  protected $template = dto\entryexit_entry_conditions_report_features::class;

  public function getFeatureOfReport(int $ecr_id, int $feature_id): ?dto\entryexit_entry_conditions_report_features {

    $where = [
      sprintf('entryexit_entry_conditions_reports_id = %d', $ecr_id),
      sprintf('entryexit_features_id = %d', $feature_id)
    ];

    $sql = sprintf(
      'SELECT * FROM `%s` WHERE %s ORDER BY `created` DESC',
      $this->_db_name,
      implode(' AND ', $where)
    );

    if ($dtoSet = (new dtoSet)($sql, null, $this->template)) {

      return array_shift($dtoSet);
    }

    return null;
  }

  public function getMatrixOfReport(int $entryexit_entry_conditions_reports_id): array {

    $where = [sprintf('entryexit_entry_conditions_reports_id = %d', $entryexit_entry_conditions_reports_id)];

    $sql = sprintf(
      'SELECT * FROM `%s` WHERE %s ORDER BY `created` DESC',
      $this->_db_name,
      implode(' AND ', $where)
    );

    return (new dtoSet)($sql, null, $this->template);
  }

  public function Insert($a) {
    $a['created'] = $a['updated'] = self::dbTimeStamp();
    return parent::Insert($a);
  }

  public function UpdateByID($a, $id) {
    $a['updated'] = self::dbTimeStamp();
    return parent::UpdateByID($a, $id);
  }
}
