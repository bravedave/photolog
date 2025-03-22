<?php
/*
 * David Bray
 * BrayWorth Pty Ltd
 * e. david@brayworth.com.au
 *
 * MIT License
 *
*/

use bravedave\dvc\icon;
use cms\strings;
?>

<nav class="navbar navbar-expand-md navbar-dark bg-dark">

  <div class="container-fluid">

    <div class="navbar-brand" href="#"><?= $this->data->title ?></div>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse"
      data-target="#navbarSupportedContent" aria-controls="navbarSupportedContent"
      aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="navbarSupportedContent">

      <ul class="navbar-nav me-auto">

        <li class="nav-item">
          <a class="nav-link" href="<?= strings::url() ?>"><?= icon::get(icon::house) ?></a>
        </li>

        <li class="nav-item pt-1">
          <a class="nav-link pb-0" href="<?= strings::url('photolog') ?>">PhotoLog</a>
        </li>

        <li class="nav-item pt-1">
          <a class="nav-link pb-0" href="<?= strings::url('smokealarm') ?>">Smoke Alarm</a>
        </li>

        <li class="nav-item pt-1 dropdown">

          <a class="nav-link pb-0 dropdown-toggle" href="#" id="navbarDropdown"
            role="button" data-bs-toggle="dropdown" aria-haspopup="true"
            aria-expanded="false">Admin</a>

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
          <?= ($searchFocus ?? null) ? 'autofocus' : '' ?>
          id="<?= $_uid = strings::rand() ?>">
      </form>
      <script>
        (_ => {
          $('#<?= $_uid ?>').on('keypress', (e) => {

              if (!/[a-z0-9\s\-\_\/\+@\.&']/i.test(e.key)) return false;
            })
            .autofill({
              source: (request, response) => {
                _.fetch.post(_.url(''), {
                  action: 'search',
                  term: request.term
                }).then(d => response('ack' == d.response ? d.data : []));
              },
              select: (e, ui) => {

                const item = ui.item;
                if ('properties' == item.type) {

                  $('#<?= $_uid ?>').val('');

                  hourglass.on()
                    .then(h => location.href = _.url('properties/view/' + item.id));

                } else if ('people' == item.type) {

                  $('#<?= $_uid ?>').val('');
                  const url = _.url('people/edit/' + item.id);
                  _.get(url).then(h => $(h).appendTo('body'));
                }
              }
            });
        })(_brayworth_);
      </script>
    </div>
  </div>
</nav>