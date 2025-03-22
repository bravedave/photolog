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

use bravedave\dvc\{dao, dtoSet, logger};
use cms\authority\dao\cms_authority as daoAuthority;
use cms\entryexit\entryconditionreports\config;
use cms\tenancy\dao\tenancies as daoTenancies;
use cms\noleggio\dao\noleggio as daoNoleggio;
use cms\properties\dao\{
  properties as daoProperties,
  dto\property as dtoProperty
};
use cms\users\dao\users as daoUsers;
use cms\{strings};
use SplFileInfo;

class entryexit_entry_conditions_reports extends dao {
  protected $_db_name = 'entryexit_entry_conditions_reports';
  protected $template = dto\entryexit_entry_conditions_reports::class;

  public function getDocFromStore(dto\entryexit_entry_conditions_reports|int $x, string $document): ?SplFileInfo {

    if ($store = $this->store($x, $create = false)) {

      /**
       * use an iterator to get splFileInfo objects
       */
      $files = new \DirectoryIterator($store);
      foreach ($files as $file) {

        if ($file->isFile()) {

          if ($file->getFilename() == $document) return $file;
        }
      }
    }

    return null;
  }

  public function getDocumentsFromStore(dto\entryexit_entry_conditions_reports|int $x): array {

    $docs = [];
    if ($store = $this->store($x, $create = false)) {

      /**
       * use an iterator to get splFileInfo objects
       */
      $files = new \DirectoryIterator($store);

      foreach ($files as $file) {

        if ($file->isFile()) $docs[] = $file->getFileInfo();
      }
    }

    return $docs;
  }

  public function getFeaturesForRoom(dto\entryexit_entry_conditions_reports $dto, int $room_id): array {

    return array_values(array_filter($dto->features, fn($f) => $f->property_rooms_id == $room_id));
  }

  public function getMatrix(
    string $start = '',
    string $end = '',
    int $properties_id = 0,
    int $offer_to_lease_id = 0,
    int $noleggio_id = 0
  ): array {

    $where = [];

    if (strtotime($start) > 0 && strtotime($end) > 0) {

      $where[] = sprintf('ecr.`date` BETWEEN %s AND %s', $this->quote($start), $this->quote($end));
    } else {

      if (strtotime($start) > 0) $where[] = sprintf('ecr.`date` >= %s', $this->quote($start));
      if (strtotime($end) > 0) $where[] = sprintf('ecr.`date` <= %s', $this->quote($end));
    }

    if ($properties_id > 0) $where[] = sprintf('ecr.`properties_id` = %d', $properties_id);
    if ($offer_to_lease_id > 0) $where[] = sprintf('ecr.`offer_to_lease_id` = %d', $offer_to_lease_id);
    if ($noleggio_id > 0) $where[] = sprintf('ecr.`noleggio_id` = %d', $noleggio_id);

    if ($where) {
      $where = 'WHERE ' . implode(' AND ', $where);
    } else {
      $where = '';
    }

    $sql = sprintf('SELECT 
        ecr.*,
        CASE 
        WHEN ecr.`offer_to_lease_id` > 0 THEN otl.`lease_start_inaugural`
        WHEN ecr.`noleggio_id` > 0 THEN no.`lease_start_inaugural` 
        ELSE NULL 
        END AS lease_start_inaugural,
        CASE 
        WHEN ecr.`offer_to_lease_id` > 0 THEN otl.`lease_start`
        WHEN ecr.`noleggio_id` > 0 THEN no.`lease_start` 
        ELSE NULL 
        END AS lease_start,
        p.`address_street`,
        p.`address_suburb`
      FROM `entryexit_entry_conditions_reports` ecr
        LEFT JOIN `properties` p ON p.`id` = ecr.`properties_id`
        LEFT JOIN `offer_to_lease` otl ON otl.`id` = ecr.`offer_to_lease_id`
        LEFT JOIN `noleggio` no ON no.`id` = ecr.`noleggio_id`
        %s
      ORDER BY ecr.`date` DESC, ecr.`id` DESC', $where);

    // logger::sql($sql, logger::caller());
    return (new dtoSet)($sql, function ($dto) {

      $dto->address_street = strings::GoodStreetString($dto->address_street);
      $dto->tenant_signed = false;

      if ($doc = $this->getDocFromStore($dto, config::tenant_signed_copy_file . '.pdf')) {

        $dto->tenant_signed = true;
      }

      if (is_null($dto->lease_start_inaugural)) $dto->lease_start_inaugural = '';
      if (is_null($dto->lease_start)) $dto->lease_start = '';

      return $dto;
    }, $this->template);
  }

  public function getRichdata(dto\entryexit_entry_conditions_reports $dto): dto\entryexit_entry_conditions_reports {

    if ($dto->properties_id) {

      if ($prop = (new daoProperties)->getByID($dto->properties_id)) {

        /** @var dtoProperty $prop */
        $dto->address_street = strings::GoodStreetString($prop->address_street);
        $dto->address_suburb = $prop->address_suburb;
        $dto->address_state = $prop->address_state;
        $dto->address_postcode = $prop->address_postcode;
        if ($prop->description_rooms) {

          $dto->property_rooms = explode(',', $prop->description_rooms);
        }

        if (empty($dto->address_state)) {

          $dto->address_state = strings::getStateFromPostcode($dto->address_postcode);
        }
      }
    }

    if ($dto->cms_authority_id) {

      if ($cms_authority = (new daoAuthority)->getByID($dto->cms_authority_id)) {
        $dto->cms_authority_name = $cms_authority->name;
      }
    }

    if ($dto->offer_to_lease_id) {

      if ($offer_to_lease = (new daoTenancies)->getOTLByID($dto->offer_to_lease_id)) {

        $tenants = [];
        if ($offer_to_lease->tenants) {

          $_tenants = json_decode($offer_to_lease->tenants);
          array_walk($_tenants, function ($t) use (&$tenants) {

            $tenants[] = (object)[
              'id' => $t->id,
              'name' => $t->name,
              'phone' => $t->phone,
              'email' => $t->email,
              'type' => 'tenant'
            ];
          });
        }

        if ($offer_to_lease->tenants_guarantors) {

          $_guarantors = json_decode($offer_to_lease->tenants_guarantors);
          array_walk($_guarantors, function ($t) use (&$tenants) {

            $tenants[] = (object)[
              'id' => $t->id,
              'name' => $t->name,
              'phone' => $t->phone,
              'email' => $t->email,
              'type' => 'guarantor'
            ];
          });
        }

        $dto->tenants = $tenants;

        $dto->lease_end = $offer_to_lease->lease_end;
        $dto->lease_start = $offer_to_lease->lease_start;
        $dto->lease_start_inaugural = $offer_to_lease->lease_start_inaugural;

        if (strtotime($dto->lease_start_inaugural) < 1) {

          $dto->lease_start_inaugural = $dto->lease_start;
        }
      }
    }

    if ($dto->noleggio_id) {

      if ($noleggio = (new daoNoleggio)($dto->noleggio_id)) {

        $tenants = [];
        if ($noleggio->tenants) {

          // $_tenants = json_decode($noleggio->tenants);
          $_tenants = $noleggio->tenants;
          array_walk($_tenants, function ($t) use (&$tenants) {

            // logger::dump($t, logger::caller());

            $tenants[] = (object)[
              'id' => $t->id,
              'name' => $t->name,
              'phone' => $t->mobile,
              'email' => $t->email,
              'type' => 'tenant'
            ];
          });
        }

        $dto->tenants = $tenants;

        $dto->lease_end = $noleggio->lease_end;
        $dto->lease_start = $noleggio->lease_start;
        $dto->lease_start_inaugural = $noleggio->lease_start_inaugural;
      }
    }

    if ($dto->issued_by > 0) {

      if ($user = (new daoUsers)->getByID($dto->issued_by)) {

        $dto->issued_by_name = $user->name;
        // logger::info(sprintf('<#%d %s> %s', $dto->issued_by, $dto->issued_by_name, logger::caller()));
      } else {

        // logger::info(sprintf('<#%d not found> %s', $dto->issued_by, logger::caller()));
      }
    }

    $myFeaturesSet = (new entryexit_entry_conditions_report_features)->getMatrixOfReport($dto->id);
    $featureSet = (new entryexit_features)->getMatrix();

    /**
     * the $featureSet must be filtered to only
     * include $rooms which are rooms of the property
     */
    if ($dto->property_rooms) {

      // $dto->property_rooms is an array of ids
      $featureSet = array_filter($featureSet, fn($f) => in_array($f->property_rooms_id, $dto->property_rooms));
      $featureSet = array_values($featureSet);  // convert to values
    }

    $dto->features = array_map(function ($in) use ($myFeaturesSet) {

      $f = [
        'id' => $in->id,
        'property_rooms_id' => $in->property_rooms_id,
        'description' => $in->description,
        'order' => $in->order,
        'room_name' => $in->room_name,
        'clean' => 0,
        'working' => 0,
        'undamaged' => 0,
        'lessor_comment' => '',
        'tenant_comment' => '',
        'not_applicable' => false,
      ];

      /**
       * scan my features to see if I have recorded somthing for this feature
       * $myFeatureSet already matches this
       *  $dto->id == <x>->entryexit_entry_conditions_reports_id
       * you are looking for a match on 
       *  $in->id == <x>->entryexit_features_id
       */
      $matches = array_filter($myFeaturesSet, fn($f) => $f->entryexit_features_id == $in->id);
      if ($matches) {
        $my = array_shift($matches);

        $f['clean'] = $my->clean;
        $f['working'] = $my->working;
        $f['undamaged'] = $my->undamaged;
        $f['lessor_comment'] = $my->lessor_comment;
        $f['tenant_comment'] = $my->tenant_comment;
        $f['not_applicable'] = $my->not_applicable;
      }

      return (object)$f;
    }, $featureSet);

    array_walk($dto->features, function ($f) use ($dto) {

      $f->properties_id = $dto->properties_id;
    });

    if ($doc = $this->getDocFromStore($dto, config::tenant_signed_copy_file . '.pdf')) {

      $dto->tenant_signed = true;
    }

    return $dto;
  }

  public function Insert($a) {
    $a['created'] = $a['updated'] = self::dbTimeStamp();
    return parent::Insert($a);
  }

  public function store(dto\entryexit_entry_conditions_reports|int $x, bool $create = false): string {

    $path = implode(DIRECTORY_SEPARATOR, [
      config::cms_entryexit_entryconditionreports_store(),
      is_int($x) ? $x : $x->id
    ]);

    if (!is_dir($path)) {

      if ($create) {

        mkdir($path, 0777);
      } else {

        return '';
      }
    }

    if (is_dir($path)) return $path;
    return '';
  }

  public function UpdateByID($a, $id) {
    $a['updated'] = self::dbTimeStamp();
    return parent::UpdateByID($a, $id);
  }
}
