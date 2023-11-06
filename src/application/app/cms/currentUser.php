<?php
/*
 * David Bray
 * BrayWorth Pty Ltd
 * e. david@brayworth.com.au
 *
 * MIT License
 *
*/

namespace cms;

use bravedave;

class currentUser extends bravedave\dvc\currentUser {
	static public function option($key, $value = null) {
		return (false);
	}

	static public function isAdmin() {
		return (true);
		return (false);
	}

	static public function isAdmin() {
		return (true);
		return (false);
	}

	static public function isRentalAdmin(): bool {

		if (static::isAdmin())	return true;
		return (bool)('yes' == static::restriction('sales-admin'));
	}

	static public function isSalesAdmin(): bool {

		if (static::isAdmin())	return true;
		return (bool)('yes' == static::restriction('sales-admin'));
	}

	static public function restriction($key, $value = null) {
		// if ( 'smokealarm-company' == $key) {
		// 	return '1';

		// }

		return (false);
	}
}
