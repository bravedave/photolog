<?php
/*
 * David Bray
 * BrayWorth Pty Ltd
 * e. david@brayworth.com.au
 *
 * MIT License
 *
*/

namespace dao;

use cms\sys;

$dbc = sys::dbCheck('property_photolog_rooms_tag');

$dbc->defineField('created', 'datetime');
$dbc->defineField('updated', 'datetime');
$dbc->defineField('property_photolog_id', 'bigint');
$dbc->defineField('property_rooms_id', 'bigint');
$dbc->defineField('file', 'text');

$dbc->defineIndex('idx_property_photolog_rooms_tag_photolog_id', 'property_photolog_id');

$dbc->check();
