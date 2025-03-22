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

class dbinfo extends \dao\_dbinfo {
	/*
	 * it is probably sufficient to copy this file into the <application>/app/dao folder
	 *
	 * from there store you structure files in <application>/dao/db folder
	 */
	protected function check() {
		parent::check();

		logger::info(sprintf('<checking %s/db/*.php> %s', dirname(__FILE__), logger::caller()));

		if (glob(dirname(__FILE__) . '/db/*.php')) {
			foreach (glob(dirname(__FILE__) . '/db/*.php') as $f) {
				logger::info('checking => ' . $f);
				include_once $f;
			}
		}
	}
}
