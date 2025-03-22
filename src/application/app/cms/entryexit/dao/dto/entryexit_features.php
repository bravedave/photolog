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

class entryexit_features extends dto {

  public $id = 0;
  
  public $property_rooms_id = 0;
  public $description = '';
  public $order = '';
  public $archived = 0;
  public $system = 0;

  // rich data
  public $room_name;
}
