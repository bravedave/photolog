<?php
/*
 * David Bray
 * BrayWorth Pty Ltd
 * e. david@brayworth.com.au
 *
 * MIT License
 *
*/

namespace photolog\dao;

use bravedave\dvc\{dao, dtoSet, logger};

class property_photolog_rooms_tag extends dao {
	protected $_db_name = 'property_photolog_rooms_tag';

	public function getByPropertyPhotologID(int $property_photolog_id): array {

		if ($property_photolog_id) {

			$sql = sprintf(
				'SELECT 
						prt.*,
						pr.name
					FROM `%s` prt
						LEFT JOIN property_rooms pr ON pr.id = prt.`property_rooms_id`
					WHERE prt.`property_photolog_id` = %d',
				$this->_db_name,
				$property_photolog_id
			);
			return (new dtoSet)($sql);
		}

		return [];
	}

	public function tagFileClear(int $property_photolog_id, string $file): bool {

		$sql = sprintf(
			'SELECT 
				`id`, `property_rooms_id`, `file` 
			FROM property_photolog_rooms_tag 
			WHERE property_photolog_id = %d AND file = %s',
			$property_photolog_id,
			$this->quote($file)
		);
		(new dtoSet)($sql, function ($dto) {

			$this->delete($dto->id);
		});
		/*
		SELECT * FROM property_photolog_rooms_tag;
		 */

		// if ($tag) {

		// 	$this->UpdateByID(
		// 		['property_rooms_id' => $room_id],
		// 		$tag->id
		// 	);
		// } else {

		// 	$a = [
		// 		'property_photolog_id' => $property_photolog_id,
		// 		'file' => $file,
		// 		'property_rooms_id' => $room_id
		// 	];

		// 	$this->Insert($a);
		// }

		return true;
	}

	public function tagFileToRoom(int $property_photolog_id, int $room_id, string $file): bool {

		$currentTags = $this->getByPropertyPhotologID($property_photolog_id);

		// is this file in the list ?
		$filterTags = array_filter($currentTags, fn($tag) => $tag->file == $file);
		$tag = array_shift($filterTags);

		/*
		SELECT * FROM property_photolog_rooms_tag;
		 */

		if ($tag) {

			$this->UpdateByID(
				['property_rooms_id' => $room_id],
				$tag->id
			);
		} else {

			$a = [
				'property_photolog_id' => $property_photolog_id,
				'file' => $file,
				'property_rooms_id' => $room_id
			];

			$this->Insert($a);
		}

		return true;
	}

	public function Insert($a) {

		$a['created'] = $a['updated'] = self::dbTimeStamp();
		return parent::Insert($a);
	}

	public function UpdateByID($a, $id) {

		$a['updated'] = self::dbTimeStamp();
		return parent::UpdateByID($a, $id);
	}
}
