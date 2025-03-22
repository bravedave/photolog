<?php
/*
 * David Bray
 * BrayWorth Pty Ltd
 * e. david@brayworth.com.au
 * 
 * MIT License
 *
*/

namespace cms\entryexit\dao\dto;

use bravedave\dvc\dto;

class entryexit_entry_conditions_reports extends dto {

  public $id = 0;

  public $created = '';
  public $updated = '';

  public $date = '';
  public $properties_id = 0;
  public $offer_to_lease_id = 0;
  public $noleggio_id = 0;
  public $cms_authority_id = 0;
  public $water_individual_meter = 0;
  public $water_wise = 0;
  public $water_meter_start = '';
  public $pool = 0;
  public $pool_certificate_expiry_date = '';
  public $additional_comments = '';
  public $date_of_receipt_by_tenant = '';
  public $issued_by = 0;
  public $issued_date = '';
  public $due_date_from_tenant = '';
  
  // rich data
  public $address_street = '';
  public $address_suburb = '';
  public $address_state = '';
  public $address_postcode = '';
  public $property_rooms = [];
  public $cms_authority_name = '';
  public $lease_end = '';
  public $lease_start = '';
  public $lease_start_inaugural = '';
  public $tenant_signed = false;
  public $tenants = [];
  public $features = [];
  public $issued_by_name = '';
}
