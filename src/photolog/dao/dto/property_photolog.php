<?php
/*
 * David Bray
 * BrayWorth Pty Ltd
 * e. david@brayworth.com.au
 *
 * MIT License
 *
*/

namespace photolog\dao\dto;

use bravedave\dvc\{dto};

class property_photolog extends dto {

  public $id = 0;

  public $created = '';
  public $updated = '';
  public $dirModTime = '';
  public $dirStats = '';
  public $property_id = 0;
  public $subject = '';
  public $public_link = '';
  public $public_link_expires = '';
  public $date = '';
  public $entry_condition_report = 0;
  public $entryexit_entry_conditions_reports_id = 0;
  public $notes = '';

  // richdata
  public $files = null;
  public $storage = null;
  public $address_street = '';
  public $property_photolog_rooms_tags = [];
}
