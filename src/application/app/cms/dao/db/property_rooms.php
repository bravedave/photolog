<?php
/*
 * David Bray
 * BrayWorth Pty Ltd
 * e. david@brayworth.com.au
 *
 * MIT License
 *
*/

namespace cms\dao;

use bravedave\dvc\logger;
use cms\{config, sys};

$dbc = sys::dbCheck('property_rooms');

$dbc->defineField('name', 'varchar', 20);
$dbc->defineField('description', 'varchar');
$dbc->defineField('system', 'tinyint');
$dbc->defineField('archived', 'tinyint');

$dbc->check();

$dao = new property_rooms;

// DROP TABLE IF EXISTS cmsx.`property_rooms`;

// config::property_rooms_unknown =>
$a = [
  config::property_rooms_entry => (object)['name' => 'Entry', 'archived' => 0],
  config::property_rooms_lounge => (object)['name' => 'Lounge Room', 'archived' => 0],
  config::property_rooms_family => (object)['name' => 'Family Room', 'archived' => 0],
  config::property_rooms_kitchen => (object)['name' => 'Kitchen/Meals', 'archived' => 0],
  config::property_rooms_dining => (object)['name' => 'Dining Room', 'archived' => 0],
  config::property_rooms_bedroom_1 => (object)['name' => 'Bedroom 1', 'archived' => 0],
  config::property_rooms_bedroom_2 => (object)['name' => 'Bedroom 2', 'archived' => 0],
  config::property_rooms_bedroom_3 => (object)['name' => 'Bedroom 3', 'archived' => 0],
  config::property_rooms_bedroom_4 => (object)['name' => 'Bedroom 4', 'archived' => 0],
  config::property_rooms_ensuite => (object)['name' => 'Ensuite', 'archived' => 0],
  config::property_rooms_bathroom => (object)['name' => 'Bathroom', 'archived' => 0],
  config::property_rooms_toilet => (object)['name' => 'Toilet', 'archived' => 0],
  config::property_rooms_laundry => (object)['name' => 'Laundry', 'archived' => 0],
  config::property_rooms_general => (object)['name' => 'General', 'archived' => 0],
  config::property_rooms_outdoor => (object)['name' => 'Outdoor Areas', 'archived' => 0],
  config::property_rooms_reserved_1 => (object)['name' => 'Reserved 1', 'archived' => 1],
  config::property_rooms_reserved_2 => (object)['name' => 'Reserved 2', 'archived' => 1],
  config::property_rooms_reserved_3 => (object)['name' => 'Reserved 3', 'archived' => 1],
  config::property_rooms_reserved_4 => (object)['name' => 'Reserved 4', 'archived' => 1],
  config::property_rooms_bedroom_5 => (object)['name' => 'Bedroom 5', 'archived' => 0],
  config::property_rooms_bedroom_6 => (object)['name' => 'Bedroom 6', 'archived' => 0],
  config::property_rooms_bedroom_7 => (object)['name' => 'Bedroom 7', 'archived' => 0],
  config::property_rooms_bedroom_8 => (object)['name' => 'Bedroom 8', 'archived' => 0],
  config::property_rooms_bathroom_2 => (object)['name' => 'Bathroom 2', 'archived' => 0],
  config::property_rooms_bathroom_3 => (object)['name' => 'Bathroom 3', 'archived' => 0],
  config::property_rooms_bathroom_4 => (object)['name' => 'Bathroom 4', 'archived' => 0],
  config::property_rooms_media_room => (object)['name' => 'Media Room', 'archived' => 0],
  config::property_rooms_study => (object)['name' => 'Study', 'archived' => 0],
  config::property_rooms_hallway_1 => (object)['name' => 'Hallway 1', 'archived' => 0],
  config::property_rooms_hallway_2 => (object)['name' => 'Hallway 2', 'archived' => 0],
  config::property_rooms_rumpus_room => (object)['name' => 'Rumpus Room', 'archived' => 0],
  config::property_rooms_internal_stairs => (object)['name' => 'Internal Stairs', 'archived' => 0],
  config::property_rooms_store_room => (object)['name' => 'Store Room', 'archived' => 0],
  config::property_rooms_garage_carport => (object)['name' => 'Garage/Carport', 'archived' => 0],
  config::property_rooms_granny_flat => (object)['name' => 'Granny Flat', 'archived' => 0],
];

/*
SELECT * FROM property_rooms
"id","name","description","system","archived"
"1","Entry","Entry",1,0
"2","Lounge Room","Lounge Room",1,0
"3","Family Room","Family Room",1,0
"4","Kitchen/Meals","Kitchen/Meals",1,0
"5","Dining Room","Dining Room",1,0
"6","Bedroom 1","Bedroom 1",1,0
"7","Bedroom 2","Bedroom 2",1,0
"8","Bedroom 3","Bedroom 3",1,0
"9","Bedroom 4","Bedroom 4",1,0
"10","Ensuite","Ensuite",1,0
"11","Bathroom","Bathroom",1,0
"12","Toilet","Toilet",1,0
"13","Laundry","Laundry",1,0
"14","General","General",1,0
"15","Outdoor Areas","Outdoor Areas",1,0
"16","Reserved 1","Reserved 1",1,1
"17","Reserved 2","Reserved 2",1,1
"18","Reserved 3","Reserved 3",1,1
"19","Reserved 4","Reserved 4",1,1
"20","Bedroom 5","",0,0
"21","Bedroom 6","",0,0
"22","Bedroom 7","",0,0
"23","Bedroom 8","",0,0
"24","Bathroom 2","",0,0
"25","Bathroom 3","",0,0
"26","Bathroom 4","",0,0
"27","Media Room","",0,0
"28","Study","",0,0
"29","Hallway 1","",0,0
"30","Hallway 2","",0,0
"31","Rumpus Room","",0,0
"32","Internal Stairs","",0,0
"33","Store Room","",0,0
"34","Garage/Carport","",0,0
"35","Granny Flat","",0,0
*/

array_walk($a, function ($obj, $id) use ($dao) {

  // logger::dump($obj, logger::caller());
  $a = [
    'name' => $obj->name,
    'description' => $obj->name,
    'archived' => $obj->archived,
    'system' => 1
  ];

  if ($dto = $dao->getByID($id)) {

    $dao->UpdateByID($a, $id);
  } else {

    $a['id'] = $id;
    $dao->Insert($a);
  }
});

  // foreach ($a as $b) (new property_rooms)->Insert(['name' => $b, 'description' => $b]);
