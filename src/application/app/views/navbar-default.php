<?php
/*
 * David Bray
 * BrayWorth Pty Ltd
 * e. david@brayworth.com.au
 *
 * MIT License
 *
*/

use dvc\icon;

/**
 * replace:
 * [x] data-dismiss => data-bs-dismiss
 * [x] data-toggle => data-bs-toggle
 * [x] data-parent => data-bs-parent
 * [x] text-right => text-end
 * [x] mr-* => me-*
 * [x] ml-* => ms-*
 * [x] input-group-prepend - remove
 * [x] input-group-append - remove
 * [x] btn input-group-text => btn btn-light
 */
?>

<nav class="navbar navbar-expand-md navbar-dark bg-dark">
	<div class="container-fluid">
    <div class="navbar-brand" href="#"><?= $this->data->title ?></div>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="navbarSupportedContent">
      <ul class="navbar-nav me-auto">
        <li class="nav-item">
          <a class="nav-link" href="<?= strings::url() ?>"><?= icon::get( icon::house ) ?> <span class="sr-only">(current)</span></a>

        </li>

        <li class="nav-item pt-1">
          <a class="nav-link pb-0" href="<?= strings::url('photolog') ?>">PhotoLog</a>

        </li>

        <li class="nav-item pt-1">
          <a class="nav-link pb-0" href="<?= strings::url('smokealarm') ?>">Smoke Alarm</a>

        </li>

        <li class="nav-item pt-1 dropdown">
          <a class="nav-link pb-0 dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
            Admin

          </a>

          <div class="dropdown-menu" aria-labelledby="navbarDropdown">
            <a class="dropdown-item" href="<?= strings::url('properties') ?>">Properties</a>
            <div class="dropdown-divider"></div>
            <a class="dropdown-item" href="<?= strings::url('beds') ?>">Beds</a>
            <a class="dropdown-item" href="<?= strings::url('baths') ?>">Baths</a>
            <a class="dropdown-item" href="<?= strings::url('property_type') ?>">Property Type</a>
            <a class="dropdown-item" href="<?= strings::url('postcodes') ?>">Postcodes</a>
            <div class="dropdown-divider"></div>
            <a class="dropdown-item" href="<?= strings::url('info') ?>">Info</a>

          </div>

        </li>

      </ul>

      <form class="form-inline my-2 my-sm-0">
        <input class="form-control me-sm-2" type="search"
          placeholder="Search" aria-label="Search"
          <?= isset( $this->data->searchFocus) && $this->data->searchFocus ? 'autofocus' : '' ?>
          id="<?= $_uid = strings::rand() ?>" />

      </form>
      <script>
      ( _ => {
          $( '#<?= $_uid ?>').on( 'keypress', ( e) => {
              if ( !/[a-z0-9\s\-\_\/\+@\.&']/i.test(e.key)) {
              //~ console.log( e);
                  return false;

              }

          })
          .autofill({
              source : ( request, response) => {
                  _.post({
                      url : _.url(''),
                      data : {
                          action : 'search',
                          term : request.term

                      },

                  }).then( d => response( 'ack' == d.response ? d.data : []));

              },
              select : ( e, ui) => {
                  let item = ui.item;
                  // console.log( item);
                  if ( 'properties' == item.type) {
                      $( '#<?= $_uid ?>').val('');

                      // let url = _.url('properties/edit/' + item.id);
                      // _.get( url)
                      // .then( html => $(html).appendTo( 'body'));
                      hourglass.on()
                      .then( h => window.location.href = _.url('properties/view/' + item.id));

                  }
                  else if ( 'people' == item.type) {
                      $( '#<?= $_uid ?>').val('');

                      let url = _.url('people/edit/' + item.id);
                      _.get( url)
                      .then( html => $(html).appendTo( 'body'));

                  }

              }

          });

      }) (_brayworth_);
      </script>

    </div>

  </div>

</nav>
