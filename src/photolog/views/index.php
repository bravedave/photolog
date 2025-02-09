<?php
/*
 * David Bray
 * BrayWorth Pty Ltd
 * e. david@brayworth.com.au
 *
 * MIT License
 *
*/

use cms\currentUser;

$_uidCarousel = false;

extract((array)$this->data);

$bootstrap = $bootstrap ?? 4;  ?>

<ul class="nav flex-column mt-2 mb-4" id="<?= $_uidNav = strings::rand() ?>">
  <?php
  if (isset($dto) && $dto) {
    if (isset($referer) && $referer) {  ?>

      <li class="nav-item">

        <a href="<?= strings::url(sprintf('%s/view/%d?f=%d', $this->route, $dto->id, $referer->id)); ?>">

          <h6><?= $this->title ?> #<?= $dto->id ?></h6>
        </a>
      </li>

      <li class="nav-item"><a class="nav-link" href="#" id="<?= $_uidCarousel = strings::rand()  ?>">carousel</a></li>
      <li class="nav-item" id="<?= $_uid = strings::rand(); ?>">
        <a class="nav-link" href="<?= strings::url('property/view/' . $referer->id); ?>"><?= $referer->address_street ?></a>
      </li>

      <script>
        (_ => $(document).ready(() => {
          _.post({
            url: _.url('<?= $this->route ?>'),
            data: {
              action: 'get-photolog',
              property: <?= $referer->id ?>

            }

          }).then(d => {
            if ('ack' == d.response) {
              //~ console.table( d.data);
              if (d.data.length > 0) {
                let ul = $('<ul class="list-unstyled ml-4"></ul>').appendTo(
                  '#<?= $_uid ?>');
                $.each(d.data, function(i, entry) {
                  let li = $('<li></li>').appendTo(ul);
                  let a = $('<a />').html(entry.subject + ' (' + entry.files
                    .total + ')').appendTo(li);

                  let m = _.dayjs(entry.date);
                  li.attr('title', m.format('l'));

                  a.attr('href', _.url('<?= $this->route ?>/view/' + entry.id +
                    '?f=<?= $referer->id ?>'));

                });

                $('#<?= $_uid ?>').removeClass('d-none');

              }

            } else {
              _.growl(d);
            }

          });

        }))(_brayworth_);
      </script>

    <?php
    } else {  ?>
      <li class="nav-item">

        <a href="<?= strings::url($this->route . '/view/' . $dto->id); ?>">
          <h6><?= $this->title ?> #<?= $dto->id ?></h6>
        </a>
      </li>

      <li class="nav-item">

        <a class="nav-link" href="<?= strings::url(sprintf('%s/?property=%d', $this->route, $dto->property_id)); ?>">
          <i class="bi bi-arrow-left-short"></i>
          <?= $dto->address_street ?>
        </a>
      </li>

      <li class="nav-item"><a class="nav-link" href="#" id="<?= $_uidCarousel = strings::rand() ?>">carousel</a></li>

      <li class="nav-item">
        <a class="nav-link" href="#" id="<?= $_uid = strings::rand() ?>">
          add entry on <?= $dto->address_street ?></a>
      </li>
      <script>
        (_ => $(document).ready(() => {
          $('#<?= $_uid ?>').on('click', e => {
            e.preventDefault();

            _.get.modal(_.url('<?= $this->route ?>/entry?property=<?= (int)$dto->property_id ?>'))
              .then(d => d.on('success', (e, href) => window.location.href =
                href));

          });
        }))(_brayworth_);
      </script>

    <?php
    }  // if ( isset( $referer) && $referer)
    ?>

    <li class="nav-item"><a class="nav-link" href="<?= strings::url($this->route); ?>">list all</a></li>

    <?php
  } else {
    if (isset($referer) && $referer) {  ?>

      <li class="nav-item">
        <a href="<?= strings::url(sprintf('%s/?property=%d', $this->route, $referer->id)); ?>">
          <h6><?= $referer->address_street ?></h6>

        </a>

      </li>

      <li class="nav-item">
        <a class="nav-link" href="<?= strings::url($this->route); ?>"><i class="bi bi-arrow-left-short"></i> list
          all</a>

      </li>

      <li class="nav-item">
        <a class="nav-link" href="#" id="<?= $_uid = strings::rand() ?>">
          <i class="bi bi-plus"></i> add entry on <?= $referer->address_street ?>
        </a>
      </li>
      <script>
        (_ => $(document).ready(() => {
          $('#<?= $_uid ?>').on('click', e => {
            e.preventDefault();

            _.get.modal(_.url('<?= $this->route ?>/entry?property=<?= (int)$referer->id ?>'))
              .then(d => d.on('success', (e, href) => window.location.href =
                href));

          });

        }))(_brayworth_);
      </script>

    <?php
    } else {  ?>
      <li class="nav-item">
        <a href="<?= strings::url($this->route); ?>">
          <h6><?= $this->title ?></h6>

        </a>

      </li>

  <?php
    }  // if ( isset( $referer) && $referer)

  }  // if ( isset( $dto) && $dto->id)
  ?>

  <?php if (isset($dto) && $dto) {  ?>

    <li class="nav-item"><a class="nav-link js-generate-public-link" href="#">generate
        public link</a></li>
    <li class="nav-item d-none">

      <div class="form-row">
        <div class="col">

          <input type="text" class="form-control js-public-link" readonly>
          <div class="form-text text-right js-public-link-expires"></div>
        </div>
      </div>

      <div class="form-row">
        <div class="col">
          <div class="btn-group btn-group-sm d-flex" aria-label="Public link toolbar">

            <button class="btn btn-light flex-fill js-copy-to-clipboard" type="button"
              title="copy to clipboard"><i class="bi bi-clipboard"></i></button>
            <button class="btn btn-light flex-fill js-public-link-clear" type="button"
              title="clear link"><i class="bi bi-trash"></i></button>
            <button class="btn btn-light flex-fill js-public-link-email" type="button"
              title="email link"><i class="bi bi-cursor"></i></button>
            <button class="btn btn-light flex-fill js-public-link-regenerate"
              type="button" title="regenerate link"><i
                class="bi bi-arrow-repeat"></i></button>
            <button class="btn btn-light flex-fill js-public-link-view" type="button"
              title="view on portal"><i class="bi bi-box-arrow-up-right"></i></button>

          </div>
        </div>
      </div>
    </li>

  <?php  }  ?>

  <?= 5 == $bootstrap ? '<div class="d-grid gap-2">' : '' ?>
  <button type="button" class="btn btn-outline-primary <?= $bootstrap < 5 ? 'btn-block' : '' ?>"
    id="<?= $_uidAdd = strings::rand() ?>">
    add entry
  </button>

  <?php if (currentUser::isDavid()) {  ?>
    <button class="btn btn-light js-run-cron">run cron</button>
  <?php  }  ?>
  <?= 5 == $bootstrap ? '</div>' : '' ?>
  <script>
    (_ => {

      const nav = $('#<?= $_uidNav ?>');

      nav.find('.js-copy-to-clipboard').on('click', e => {
        let el = nav.find('.js-public-link')[0];

        /* Select the text field */
        el.select();
        el.setSelectionRange(0, 99999); /*For mobile devices*/

        // document.execCommand("copy"); /* Copy the text inside the text field */
        navigator.clipboard.writeText(el.value);

        _.growl('Copied');
      });

      <?php if (isset($dto) && $dto) {  ?>

        nav.find('.js-generate-public-link')
          .on('refresh', function(e) {

            let _me = $(this);

            _.post({
              url: _.url('<?= $this->route ?>'),
              data: {
                action: 'public-link-get',
                id: <?= $dto->id ?>
              },
            }).then(d => {

              if ('ack' == d.response) {

                _me.closest('.nav-item')
                  .addClass('d-none');

                nav.find('.js-public-link').closest('.nav-item')
                  .removeClass('d-none');

                nav.find('.js-public-link').val(d.url);
                nav.find('.js-public-link-expires')
                  .html('expires : ' + _.dayjs(d.expires).format('l'));
              } else {

                _me.closest('.nav-item')
                  .removeClass('d-none');

                nav.find('.js-public-link').closest('.nav-item')
                  .addClass('d-none');
              }
            });
          })
          .on('clear-link', function(e) {

            let _me = $(this);

            _.post({
              url: _.url('<?= $this->route ?>'),
              data: {
                action: 'public-link-clear',
                id: <?= $dto->id ?>
              },

            }).then(d => {

              _.growl(d);
              if ('ack' == d.response) {

                _me.trigger('refresh');
              }
            });

          })
          .on('create-link', function(e) {

            let _me = $(this);

            _.get(_.url('<?= $this->route ?>/publicLink/<?= $dto->id ?>'))
              .then(html => {

                let _html = $(html)
                _html.appendTo('body');

                $('.modal', _html).on('success', d => _me.trigger('refresh'))
              });

          })
          .on('click', function(e) {
            e.stopPropagation();
            e.preventDefault();

            $(this).trigger('create-link');

          })
          .on('email-link', function(e) {

            if (!!_.email.activate) {

              _.email.activate({
                subject: <?= json_encode(sprintf('%s - %s', $dto->address_street, $dto->subject)) ?>,
                message: `<br><br>View the images on our portal <a href="${nav.find('.js-public-link').val()}">here</a><br><br>${!!window._cms_ ? _cms_.currentUser.signoff : ''}`
              })
            } else {

              _.ask.alert('no email program');
            }
          });

        $(document).ready(() => {
          if (!_.email.activate) nav.find('.js-public-link-email').addClass(
            'd-none');
        });

        nav.find('.js-public-link-clear')
          .on('click', e => nav.find('.js-generate-public-link').trigger('clear-link'));
        nav.find('.js-public-link-regenerate')
          .on('click', e => nav.find('.js-generate-public-link').trigger(
            'create-link'));
        nav.find('.js-public-link-email')
          .on('click', e => nav.find('.js-generate-public-link').trigger('email-link'));
        nav.find('.js-public-link-view')
          .on('click', e => window.open(nav.find('.js-public-link').val()));
        nav.find('.js-generate-public-link').trigger('refresh');
      <?php  }  ?>

      $('#<?= $_uidAdd ?>').on('click', e => {
        e.preventDefault();

        _.get.modal(_.url('<?= $this->route ?>/entry'))
          .then(d => d.on('success', (e, href) => window.location.href = href));

      });

      <?php if ($_uidCarousel) {  ?>

        $('#<?= $_uidCarousel ?>').on('click', function(e) {
          e.stopPropagation();
          e.preventDefault();
          $(document).trigger('photolog-carousel');
        });
      <?php  }  ?>

      _.photolog = {
        cron: () => {

          _.fetch.post(_.url('photolog'), {
            'action': 'cron',
          }).then(_.growl);
        }
      };

      console.log(nav.find('.js-run-cron'));
      nav.find('.js-run-cron').on('click', function(e) {

        $(this).empty()
          .html('<i class="spinner-border spinner-border-sm"></i> running ..');

        _.fetch.post(_.url('photolog'), {
          'action': 'cron',
        }).then(d => {

          $(this).text('run cron ..');
          _.growl(d);
        });

      });
      // console.log('run the cron job manually with : _brayworth_.photolog.cron()');
    })(_brayworth_);
  </script>
</ul>