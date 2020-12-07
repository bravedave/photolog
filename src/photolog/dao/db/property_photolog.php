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

$dbc = \sys::dbCheck( 'property_photolog' );

$dbc->defineField('created', 'datetime');
$dbc->defineField('updated', 'datetime');
$dbc->defineField('dirModTime', 'datetime');
$dbc->defineField('dirStats', 'varbinary', 128);
$dbc->defineField('property_id', 'bigint');
$dbc->defineField('subject', 'varchar', 100 );
$dbc->defineField('public_link', 'varchar');
$dbc->defineField('public_link_expires', 'date');
$dbc->defineField('date', 'date');
$dbc->defineField('notes', 'text');

$dbc->check();
