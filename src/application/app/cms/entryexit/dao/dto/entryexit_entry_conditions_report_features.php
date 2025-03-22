<?php
/*
 * David Bray
 * BrayWorth Pty Ltd
 * e. david@brayworth.com.au
 * 
 * MIT License
 *
*/

namespace cms\entryexit\dao\dto;

use bravedave\dvc\dto;

class entryexit_entry_conditions_report_features extends dto {

  public $id = 0;

  public $created = '';
  public $updated = '';

  public $entryexit_features_id = 0;
  public $entryexit_entry_conditions_reports_id = 0;
  public $clean = 0;
  public $working = 0;
  public $undamaged = 0;
  public $lessor_comment = '';
  public $tenant_comment = '';

  // rich data
  
}
