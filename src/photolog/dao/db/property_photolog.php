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

$dbc = sys::dbCheck('property_photolog');

$dbc->defineField('created', 'datetime');
$dbc->defineField('updated', 'datetime');
$dbc->defineField('dirModTime', 'datetime');
$dbc->defineField('dirStats', 'varbinary', 128);
$dbc->defineField('property_id', 'bigint');
$dbc->defineField('subject', 'varchar', 100);
$dbc->defineField('public_link', 'varchar');
$dbc->defineField('public_link_expires', 'date');
$dbc->defineField('date', 'date');
$dbc->defineField('entry_condition_report', 'tinyint');
$dbc->defineField('entryexit_entry_conditions_reports_id', 'bigint');
$dbc->defineField('notes', 'text');

$dbc->defineIndex('idx_property_photolog_property_id', 'property_id');

$dbc->check();
