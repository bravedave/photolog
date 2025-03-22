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

use bravedave\dvc\logger;

class userrestrictions {
	protected $user_id = 0;
	protected $restrictions = null;

	const photolog = 'photolog';
	const smokeAlarm = 'smoke-alarm';

	function __construct($user_id, $restrictions = null) {

		$this->user_id = $user_id;

		gettype($restrictions) == 'string' ?
			$this->restrictions = (array)json_decode($restrictions) :
			$this->restrictions = $restrictions;
	}

	function get($key) {

		$debug = false;
		//~ $debug = true;
		//~ $debug = \currentUser::isDavid();

		$ret = '';
		if (!is_null($this->restrictions)) {

			if (is_array($this->restrictions)) {

				/* return the existing value */
				if (isset($this->restrictions[$key])) {

					$ret = (string)$this->restrictions[$key];
					if ($debug) {

						logger::debug(sprintf(
							'<retrieve option value : %s = %s> %s',
							$key,
							$ret,
							logger::caller()
						));
					}
				} elseif ($debug) {

					logger::debug(sprintf(
						'<retrieve option value (default - not set) : %s = %s> %s',
						$key,
						$ret,
						logger::caller()
					));
				}
			} elseif ($debug) {

				logger::debug(sprintf(
					'<retrieve option value (not array): %s = %s> %s',
					$key,
					$ret,
					print_r($this->restrictions, TRUE),
					logger::caller()
				));
			}
		} elseif ($debug) {

			logger::debug(sprintf(
				'<retrieve option value (null): %s = %s> %s',
				$key,
				$ret,
				logger::caller()
			));
		}

		return ($ret);
	}

	function set($key, $val) {

		$debug = false;
		//~ $debug = true;
		//~ $debug = \currentUser::isDavid();

		if (is_null($this->restrictions)) $this->restrictions = [];

		if ((string)$val == '') {

			/* writer */
			if (isset($this->restrictions[$key])) unset($this->restrictions[$key]);
		} else {

			/* reader */
			$this->restrictions[$key] = (string)$val;
		}

		$a = ['restrictions' => json_encode($this->restrictions)];

		$dao = new \dao\users;
		$dao->UpdateByID($a, (int)$this->user_id);
		if ($debug) {

			logger::debug(sprintf(
				'<saved option value : %s = %s for %d> %s',
				$key,
				$val,
				$this->user_id,
				logger::caller()
			));
		}
	}
}
