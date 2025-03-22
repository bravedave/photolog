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

use bravedave\dvc\{dao, dtoSet};

class property_rooms extends dao {
	protected $_db_name = 'property_rooms';
	protected $template = dto\property_rooms::class;

	public function getMatrix(bool $archived = false): array {

		$sql = 'SELECT * FROM `property_rooms`';
		if (!$archived) $sql .= ' WHERE `archived` = 0';
		return (new dtoSet)($sql, null, $this->template);
	}
}
