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

use bravedave;

class dbinfo extends bravedave\dvc\dbinfo {
	/*
	 * it is probably sufficient to copy this file into the
	 * 	<application>/app/dao folder
	 *
	 * from there store you structure files in
	 * 	<application>/dao/db folder
	 */
	protected function check() {
		parent::check();
		parent::checkDIR(__DIR__);
	}
}
