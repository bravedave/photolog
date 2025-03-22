<?php
/*
 * David Bray
 * BrayWorth Pty Ltd
 * e. david@brayworth.com.au
 *
 * MIT License
 *
*/

namespace photolog;

use cms\{currentUser, routes, strings, sys};
use cms;

$uid = strings::rand();
$diskSpace = sys::diskspace();


/** @var array $rooms */

?>
<style>
  #<?= $uid; ?>row .has-advanced-upload {
    padding: 22% .5rem .5rem .5rem;
    margin-bottom: 1rem;
  }
</style>
<style type="text/css" media="print">
  @page {
    size: portrait;
    margin: 10mm;
  }

  body {
    font-size: 10pt;
  }
</style>

<!-- bootstrap tabset -->
<ul class="nav nav-tabs" id="<?= $_uidTabset = strings::rand() ?>" role="tablist">

  <li class="nav-item me-auto" role="presentation">
    <button class="nav-link js-photolog-tab active" id="<?= $_uidTabset ?>-photolog" data-bs-toggle="tab"
      data-bs-target="#<?= $_uidTabset ?>-photolog-pane" type="button" role="tab"
      aria-controls="<?= $_uidTabset ?>-photolog-pane" aria-selected="true"><?= $dto->subject ?></button>
  </li>

  <li class="nav-item d-none" role="presentation">
    <button class="nav-link" id="<?= $_uidTabset ?>-room" data-bs-toggle="tab"
      data-bs-target="#<?= $_uidTabset ?>-room-pane" type="button" role="tab"
      aria-controls="<?= $_uidTabset ?>-room-pane" aria-selected="true"></button>
  </li>

  <li class="nav-item" role="presentation">
    <button type="button" role="tab" class="nav-link d-none js-entry-cr-tab"
      id="<?= $_uidTabset ?>-entry-cr"
      data-entryexit-entry-conditions-reports-id="<?= $dto->entryexit_entry_conditions_reports_id ?>"
      data-bs-toggle="tab"
      data-bs-target="#<?= $_uidTabset ?>-entry-cr-pane"
      aria-controls="<?= $_uidTabset ?>-entry-cr-pane"
      aria-selected="true"><?= cms\entryexit\entryconditionreports\config::label_short ?></button>
  </li>

  <li class="nav-item" role="presentation">
    <button type="button" role="tab" class="nav-link d-none js-entry-cr-tab-pdf"
      id="<?= $_uidTabset ?>-entry-cr-pdf"
      data-entryexit-entry-conditions-reports-id="<?= $dto->entryexit_entry_conditions_reports_id ?>"
      data-bs-toggle="tab"
      data-bs-target="#<?= $_uidTabset ?>-entry-cr-pdf-pane"
      aria-controls="<?= $_uidTabset ?>-entry-cr-pdf-pane"
      aria-selected="true"><?= cms\entryexit\entryconditionreports\config::label_short_pdf ?></button>
  </li>

  <li class="nav-item" role="presentation">
    <button class="nav-link" id="<?= $_uidTabset ?>-address" data-bs-toggle="tab"
      data-bs-target="#<?= $_uidTabset ?>-address-pane" type="button" role="tab"
      aria-controls="<?= $_uidTabset ?>-address-pane" aria-selected="false"><?= $dto->address_street ?></button>
  </li>
</ul>

<div class="tab-content" id="<?= $_uidTabset ?>-content">

  <div class="tab-pane active" id="<?= $_uidTabset ?>-photolog-pane" role="tabpanel"
    aria-labelledby="<?= $_uidTabset ?>-photolog">
    <!-- ----[ content goes here ]---- -->
    <div class="row">

      <div class="col-md-auto"><?= $dto->address_street ?></div>
      <div class="col"><?= $dto->subject ?></div>
      <div class="col col-md-auto"><span class="fw-light">date : </span><?= strings::asShortDate($dto->date) ?></div>
      <div class="col-auto">
        <button type="button" class="btn btn-sm pt-0" id="<?= $_btnEditHeader = strings::rand() ?>">
          <i class="bi bi-pencil"></i>
        </button>
      </div>
    </div>

    <div class="js-filter-information"></div>

    <div id="<?= $uid; ?>row" class="row g-2"></div>

    <?php if (false) { ?>

      <template id="<?= $uid ?>fileupload">

        <div class="input-group mb-3">

          <span class="input-group-text" id="<?= $uid ?>FileAddon01">Upload</span>

          <div class="custom-file">

            <input type="file" class="custom-file-input" id="<?= $uid ?>File01"
              aria-describedby="<?= $uid ?>FileAddon01" multiple>
            <label class="custom-file-label" for="<?= $uid ?>File01">Choose file</label>
          </div>
        </div>
      </template>
    <?php }  ?>
    <!-- ----[ /content goes here ]---- -->
  </div>

  <div class="tab-pane" id="<?= $_uidTabset ?>-room-pane" role="tabpanel"
    aria-labelledby="<?= $_uidTabset ?>-room">
    <p>Tab room content ...</p>
  </div>

  <div class="tab-pane" id="<?= $_uidTabset ?>-entry-cr-pane" role="tabpanel"
    aria-labelledby="<?= $_uidTabset ?>-entry-cr">
    <p>loading entryCR ...</p>
  </div>

  <div class="tab-pane" id="<?= $_uidTabset ?>-entry-cr-pdf-pane" role="tabpanel"
    aria-labelledby="<?= $_uidTabset ?>-entry-cr">
    <p>loading entryCR PDF ...</p>
  </div>

  <div class="tab-pane" id="<?= $_uidTabset ?>-address-pane" role="tabpanel"
    aria-labelledby="<?= $_uidTabset ?>-address">
    <p>Tab address content ...</p>
  </div>
</div>
<!-- /bootstrap tabset -->

<script>
  (_ => {
    const tabSet = $('#<?= $_uidTabset ?>');
    const rooms = <?= json_encode($rooms) ?>;
    const tabPhotolog = $('#<?= $_uidTabset ?>-photolog');
    const tabPhotologPane = $('#<?= $_uidTabset ?>-photolog-pane');
    const tabEntryCr = $('#<?= $_uidTabset ?>-entry-cr');
    const tabEntryCrPDF = $('#<?= $_uidTabset ?>-entry-cr-pdf');

    let smokeAlarms = [];
    let allCards = [];
    let deleting = 0;

    const allDownloadVisibility = () => allDownload.toggleClass('d-none', $('img[logimage]').length < 1);

    const allDeleteVisibility = () => {

      <?php if (currentUser::isSalesAdmin() || currentUser::isRentalAdmin()) {  ?>

        allDelete.toggleClass('d-none', $('.photolog-card').length < 1);
      <?php }  ?>
    };

    const cardClick = function(e) {

      _.hideContexts(e);

      const _me = $(this);
      const _data = _me.data();
      if (/mp4|mov/.test(String(_data.file.extension))) {

        const options = {
          size: 'lg',
          title: ('mov' == _data.file.extension ? 'quicktime' : 'mp4') +
            ' Viewer',
          text: '',
          headClass: '',
          url: _data.file.url
        };

        const m = _.ask(options);
        const id = _.randomString();
        const video = $(`<video controls autoplay id="${id}" width="100%">
                  <source src="${_data.file.url + '&v=full'}" type="video/${ 'mov' == _data.file.extension ? 'quicktime' : 'mp4' }">
                  Sorry, your browser doesn't support embedded videos.
                </video>`);

        $('.modal-body', m)
          .addClass('p-2')
          .append(video);

        console.log(_data.file);
      } else {

        $(document).trigger('photolog-carousel', _data.file.description);
      }
    };

    const cardContextMenu = function(e) {

      if (e.shiftKey) return;
      let _context = _.context(e);

      let _me = $(this);
      let _data = _me.data();

      _context.append.a({
          html: '<i class="bi bi-collection-play"></i>Start Carousel',
          click: e => $(document).trigger(
            'photolog-carousel',
            _data.file.description
          )
        })
        .attr('title', 'open in new tab');

      _context.append.a({
          html: '<i class="bi bi-box-arrow-up-right"></i>Open in new Window',
          href: _data.file.url + '&v=full',
        })
        .attr({
          'title': 'open in new tab',
          'target': '_blank'
        });

      // console.table(_data.file);

      if (_data.file.prestamp) {

        _context.append.a({
          html: '<i class="bi bi-arrow-counterclockwise"></i>Rotate Left',
          click: e => _me.trigger('rotate-left')
        });

        _context.append.a({
          html: '<i class="bi bi-arrow-clockwise"></i>Rotate Right',
          click: e => _me.trigger('rotate-right')
        });

        _context.append.a({
          html: '<i class="bi bi-emoji-smile-upside-down"></i>Rotate 180',
          click: e => _me.trigger('rotate-180')
        });
      }

      if (smokeAlarms.length > 0) {
        // console.log( smokeAlarms);

        _context.append('<hr>');
        $.each(smokeAlarms, (i, alarm) => {

          _context.append.a({
            html: `${_data.file.location == alarm.location ? '<i class="bi bi-check"></i>' : ''}${alarm.location}`,
            click: e => {

              if (_data.file.location == alarm.location) {

                card.trigger('clear-location');
              } else {

                card.trigger('set-location', alarm.location);
              }
            }
          });
        });
      }

      _context.append.a({
        html: 'clear room tag',
        click: e => _me.trigger('tag-to-room-clear')
      });

      _context.append('<hr>');
      _context.append.a({
        html: '<i class="bi bi-input-cursor-text"></i>rename',
        click: e => _me.trigger('rename-file')
      });

      _context.append.a({
        html: '<i class="bi bi-trash"></i>delete',
        click: e => _me.trigger('delete')
      });

      _context.open(e);
    };

    const cardDelete = function(e) {

      const _me = $(this);
      _.ask.alert.confirm({
          text: 'Are you sure ?',
          title: 'Confirm Delete'
        })
        .then(() => _me.trigger('delete-confirmed'))
    };

    const cardDeleteConfirmed = function(e) {

      const _me = $(this);
      const _data = _me.data();
      const file = _data.file;

      deleting++;
      const payload = {
        action: 'delete',
        id: <?= $dto->id ?>,
        file: file.description,
      };

      _.fetch.post(_.url('<?= $this->route ?>'), payload)
        .then(d => {
          _.growl(d);

          deleting--;
          if ('ack' == d.response) _me.parent().remove();

          if (0 == deleting) {

            allDeleteVisibility();
            allDownloadVisibility();
          }
        });
    };

    const cardRefresh = function(e) {
      const _me = $(this);
      const file = _me.data('file');

      this.dataset.roomId = file.room_id;
      _me.empty();

      const selector = $(``);
      const img = $('<img class="card-img-top pointer" draggable="false" logimage>');

      img
        .attr('title', file.description)
        .attr('src', file.url)
        .appendTo(this);

      const body = $(`<div class="card-body px-2 py-1 position-relative">
            <div class="card-text text-truncate small fw-light" title="${file.description}">${file.description}</div>
            <input type="checkbox" class="js-selector position-absolute" style="top: 5px; right: 5px">
          </div>`)
        .appendTo(this);

      if (!!file.room) {

        body.append(`<div class="card-text text-truncate small fw-light">${file.room}</div>`);
      }

      if (!!file.error) {

        if (10 > Number(file.size)) {

          body.append('<h6 class="text-danger">file size error</h6>');
        } else {

          body.append('<h6 class="text-danger">ERROR</h6>');
        }
      }

      body.find('.js-selector')
        .on('click', function(e) {

          _.hideContexts(e);
          if (e.shiftKey) {

            // console.log('hey shifty !');

            const selected = tabPhotologPane.find('input.js-selector:checked').first();
            if (selected.length > 0) {

              const selectors = tabPhotologPane.find('input.js-selector');

              /**
               * check all the checkboxes from the first checked one
               * up to and including this one
               */
              const start = selectors.index(selected);

              let end = selectors.index($(this));
              if (end == start) {

                const last = tabPhotologPane.find('input.js-selector:checked').last();
                end = selectors.index(last);
              }

              const range = selectors.slice(start, end + 1); // get all the checkboxes in the range
              range.prop('checked', true); // check them
            }
          }
        });
    };

    const displayCard = file => {

      const rand = _.randomString();
      const card = $(`<div class="card photolog-card" id="${rand}" data-room-id="${file.room_id}"></div>`);

      allCards.push(card);

      card
        .data('file', file)
        .on('clear-location', locationClear)
        .on('delete', cardDelete)
        .on('delete-confirmed', cardDeleteConfirmed)
        .on('get-selected', getSelected)
        .on('set-location', locationSet)
        .on('refresh', cardRefresh)
        .on('refresh-from-server', refreshFromServer)
        .on('rename-file', renameFile)
        .on('rotate-180', rotate180)
        .on('rotate-left', rotateLeft)
        .on('rotate-right', rotateRight)
        .on('tag-to-room-clear', tagClear);

      if (_.browser.isMobileDevice) {

        card.on('click', cardContextMenu);
      } else {

        card
          .on('click', cardClick)
          .on('contextmenu', cardContextMenu);
      }

      $('<div class="col-md-4 col-lg-3 col-xl-2 col-print-3 mb-2"></div>')
        .append(card)
        .appendTo('#<?= $uid ?>row');

      card.trigger('refresh');
    };

    const getSelected = function(e) {

      const cards = [];

      if ($(this).find('input.js-selector').length > 0) {

        tabPhotologPane.find('input.js-selector:checked').each((i, el) => {

          const card = $(el).closest('.photolog-card');
          if (card.length > 0) cards.push(card.attr('id'));
        });
      }

      this.dataset.selected = cards.join(',');
    };

    const locationClear = function(e) {

      const _me = $(this);
      const _data = _me.data();

      _.fetch.post(_.url('<?= $this->route ?>'), {
        action: 'set-alarm-location-clear',
        id: <?= (int)$dto->id ?>,
        file: _data.file.description
      }).then(d => {

        _.growl(d)

        if ('ack' == d.response) {
          _data.file.location = '';
          _me.data('file', _data.file);
        }
      });
    };

    const locationSet = function(e, location) {

      const _me = $(this);
      const _data = _me.data();

      _.fetch.post(_.url('<?= $this->route ?>'), {
        action: 'set-alarm-location',
        id: <?= (int)$dto->id ?>,
        file: _data.file.description,
        location: location
      }).then(d => {

        _.growl(d)
        if ('ack' == d.response) {

          /**
           * forum : 6300
           * allow multiple alamrs to share a location
           */
          // $.each( allCards, (i, card) => {
          // 	let _me = $(card);
          // 	let _data = _me.data();

          // 	console.log( 'check', _data.file);

          // 	if ( _data.file.location == location) {
          // 		_data.file.location = '';
          // 		_me.data('file', _data.file);

          // 	}

          // });

          _data.file.location = location;
          _me.data('file', _data.file);
        }
      });
    };

    const photologCarousel = (e, file) => {

      const imgs = $('img.card-img-top');

      if (imgs.length > 0) {

        const id = 'carousel_' + _.randomString();
        const ctrl = $(`<div class="carousel slide" data-bs-interval="5000" id=${id}></div>`);
        const indicators = $('<ol class="carousel-indicators"></ol>')

        if (imgs.length < 10) indicators.appendTo(ctrl);

        const inner = $('<div class="carousel-inner"></div>').appendTo(ctrl);

        ctrl
          .append(
            `<a class="carousel-control-prev" href="#${id}" role="button" data-bs-slide="prev">
            <span class="carousel-control-prev-icon" aria-hidden="true"></span>
            <span class="sr-only">Previous</span>
          </a>`
          )
          .append(
            `<a class="carousel-control-next" href="#${id}" role="button" data-bs-slide="next">
            <span class="carousel-control-next-icon" aria-hidden="true"></span>
            <span class="sr-only">Next</span>
          </a>`
          );

        // console.log( file);
        let first = true;
        imgs.each((i, el) => {

          const img = $(el);
          const src = img.attr('src');
          const _indicator = $(`<li data-bs-target="#${id}" data-bs-slide-to="${i}"></li>`);

          if (imgs.length < 10) _indicator.appendTo(indicators);

          const envelope = $(`<div class="carousel-item">
            <img class="d-block w-100" src="${src}" alt="...">
          </div>`)
            .appendTo(inner);

          const title = String(img.attr('title'));
          if ('' != title) {

            envelope.append(`<div class="carousel-caption d-none d-md-block"><h5>${title}</h5></div>`);
          }

          if (!!file) {

            if (title == file) {

              _indicator.addClass('active');
              envelope.addClass('active');
            }
          } else if (first) {

            _indicator.addClass('active');
            envelope.addClass('active');
          }
          first = false;
        });

        _.get.modal().then(modal => {
          $('.modal-dialog', modal).addClass('modal-lg');
          $('.modal-title', modal).html(<?= json_encode($dto->address_street) ?>);
          $('.modal-body', modal).append(ctrl);
          ctrl.carousel('cycle');
        });
      }
    };

    const refreshFromServer = function(e) {

      /**
       * legacy - refresh just updates the display with the current file
       * this reads it from the server, then calls refresh
       */
      const _me = $(this);
      const file = _me.data('file');

      const payLoad = {
        action: 'get-photolog-file',
        id: <?= $dto->id ?>,
        file: file.description
      };

      _.fetch.post(_.url('<?= $this->route ?>'), payLoad)
        .then(d => {

          if ('ack' == d.response) {

            _me.data('file', d.data);
            _me.trigger('refresh');
            $('#<?= $_uidTabset ?>-photolog').trigger('update-filter');
          } else {

            _.growl(d);
          }
        })
    };

    const renameFile = function(e) {

      const _me = $(this);
      const file = _me.data('file');

      _.textPrompt({
          title: 'new file name'
        })
        .then(newfile => {

          _.fetch.post(_.url('<?= $this->route ?>'), {
              action: 'rename-file',
              id: <?= $dto->id ?>,
              file: file.description,
              newfile: newfile
            })
            .then(d => 'ack' == d.response ? location.reload() : _.growl(d));
        });
    };

    const rotate = (file, direction) => new Promise((resolve, reject) => {

      _.fetch.post(_.url('<?= $this->route ?>'), {
        action: direction,
        id: <?= $dto->id ?>,
        file: file.description,
      }).then(d => 'ack' == d.response ? resolve(d.data) : reject(d));
    });

    const rotate180 = function(e) {

      const _me = $(this);
      const file = _me.data('file');

      rotate(file, 'rotate-180').then(data => {

        // console.log(d);
        _me.find('img[logimage]').attr('src', data.url);
        _me.data('file', data);
      }).catch(_.growl);
    };

    const rotateLeft = function(e) {

      const _me = $(this);
      const file = _me.data('file');

      rotate(file, 'rotate-left').then(data => {

        // console.log(d);
        _me.find('img[logimage]').attr('src', data.url);
        _me.data('file', data);
      }).catch(_.growl);
    };

    const rotateRight = function(e) {

      const _me = $(this);
      const file = _me.data('file');

      rotate(file, 'rotate-right').then(data => {

        // console.log(d);
        _me.find('img[logimage]').attr('src', data.url);
        _me.data('file', data);
      }).catch(_.growl);
    };

    const tagClear = function(e) {

      const $this = $(this);
      const srcData = $this.data();
      const payload = {
        action: 'photolog-tag-clear',
        file: srcData.file.description,
        id: <?= $dto->id ?>
      };

      // console.log(payload);
      _.fetch.post(_.url('<?= $this->route ?>'), payload)
        .then(d => {

          if ('ack' == d.response) $this.trigger('refresh-from-server');
          _.growl(d);
        });
    };

    let cContainer =
      $('<div class="col-md-8 col-lg-6 col-xl-4 mb-2 d-print-none"></div>')
      .appendTo('#<?= $uid ?>row');

    <?php if ($diskSpace->exceeded) {  ?>

      cContainer.append(
        `<div class="alert alert-warning">
        <h5 class="alert-heading">disk space low</h5>uploaded disabled
      </div>`);
    <?php } else { ?>

      let c = _.fileDragDropContainer({
        fileControl: true
      }).appendTo(cContainer);
    <?php } ?>

    const allDownload = $(
      `<a title="download zip" class="btn btn-light btn-sm d-none">
        <i class="bi bi-download" title="download as zip file"></i> zip
      </a>`).attr('href', _.url('<?= $this->route ?>/zip/<?= $dto->id ?>'));

    const allDelete = $(`<button title="delete all" class="btn btn-light btn-sm d-none">
    <i class="bi bi-trash"></i> delete all</button>`);
    const btnNotepad = $(`<button title="notepad" class="btn btn-light btn-sm">
    <i class="bi bi-pencil"></i> note</button>`);
    const bCol = $('<div class="col text-center"></div>')

    $('<div class="row g-2"></div>')
      .append(bCol)
      .appendTo(cContainer);

    $('<div class="btn-group"></div>')
      .append(allDownload)
      .append(allDelete)
      .append(btnNotepad)
      .appendTo(bCol);

    allDelete.on('click', e => {
      e.stopPropagation();

      _.ask.alert.confirm({
          text: 'Are you sure ?',
          title: 'Confirm Delete'
        })
        .then(() => $('.photolog-card').trigger('delete-confirmed'));
    });

    const notepad = {
      col: $('<div class="col-md-4 col-lg-3 col-xl-8 mb-2 d-none"></div>').appendTo('#<?= $uid ?>row'),
      text: $('<textarea class="form-control h-100" readonly></textarea>'),
      val: v => {

        const ret = notepad.text.val(v);
        notepad.col.toggleClass('d-none', ('' == v || null == v));
        return ret;
      }
    };

    notepad.text.appendTo(notepad.col);
    notepad.val(<?= json_encode($dto->notes, JSON_UNESCAPED_SLASHES) ?>);

    btnNotepad.on('click', e => {

      _.hideContexts(e);

      _.get.modal(_.url('<?= $this->route ?>/notepad/<?= $dto->id ?>'))
        .then(m => m.on('success', (e, d) => notepad.val(d.data.notes)));
    });

    <?php if (!$diskSpace->exceeded) {  ?>

      // console.log( 'assign');
      _.fileDragDropHandler.call(c, {
        url: _.url('<?= $this->route ?>'),
        queue: true,
        postData: {
          action: 'upload',
          id: <?= $dto->id ?>
        },
        accept: [
          'application/pdf',
          'image/jpeg',
          'image/pjpeg',
          'image/png',
          <?php
          if (config::$PHOTOLOG_ENABLE_VIDEO) print ",'video/quicktime','video/mp4'";
          if (config::$PHOTOLOG_ENABLE_HEIC) print ",'image/heic'";
          ?>
        ],
        onError: d => console.error('error', d),
        onReject: d => {

          $(`<div class="alert alert-danger">
            <h5 class="alert-heading">${d.file.name}</h5>
            ${d.description}
          </div>`)
            .appendTo(cContainer);
        },
        onUpload: d => {

          if ('ack' == d.response) {

            $.each(d.files, (i, file) => displayCard(file));
            allDeleteVisibility();
            allDownloadVisibility();

            $(document).trigger('photolog-display-cards-complete');
          }
        }
      });
    <?php  } ?>

      (cds => $.each(cds, (i, file) => displayCard(file)))
      (<?= json_encode($files) ?>);

    allDeleteVisibility();
    allDownloadVisibility();

    _.fetch.post(_.url('<?= $this->route ?>'), {
      action: 'property-smokealarms',
      id: <?= (int)$dto->id ?>
    }).then(d => {

      if ('ack' == d.response) smokeAlarms = d.alarms;
    });

    $(document).on('photolog-carousel', photologCarousel);

    $('#<?= $_btnEditHeader ?>').on('click', e => {

      _.hideContexts(e);

      _.get.modal(_.url('<?= $this->route ?>/entry/<?= $dto->id ?>'))
        .then(d => d.on('success', (e, href) => location.reload()));
    });

    tabSet.find('button[data-bs-toggle="tab"]')
      .on('hide.bs.tab', e => e.stopPropagation())
      .on('hidden.bs.tab', e => e.stopPropagation())
      .on('show.bs.tab', e => e.stopPropagation())
      .on('shown.bs.tab', e => e.stopPropagation());

    $('#<?= $_uidTabset ?>-address-pane').on('refresh', function(e) {

      e.stopPropagation();
      <?php if ($dto->property_id) {  ?>
        $(this).load(_.url('<?= routes::property_lite ?>/<?= $dto->property_id ?>'));
      <?php } else {  ?>
        $(this).text('no property associated with this record');
      <?php }  ?>
    });

    $('#<?= $_uidTabset ?>-address')
      .on('hidden.bs.tab', e => $('#<?= $_uidTabset ?>-address-pane').empty())
      .on('show.bs.tab', function(e) {

        _.hideContexts(e);
        $('#<?= $_uidTabset ?>-address-pane').trigger('refresh');
      });

    $('#<?= $_uidTabset ?>-room-pane').on('refresh', function(e) {

      e.stopPropagation();
      <?php if ($dto->property_id) {  ?>
        // $(this).load(_.url('<?= routes::property_lite ?>/<?= $dto->property_id ?>'));
        $(this).text(`room ${this.dataset.room}`);
      <?php } else {  ?>
        $(this).text('no property associated with this record');
      <?php }  ?>
    });

    $('#<?= $_uidTabset ?>-photolog-pane').on('update-filter', function(e) {

      e.stopPropagation();

      const room = Number(this.dataset.room);
      const $this = $(this);

      $this.find('.js-filter-information').empty();
      // console.log(room);
      // console.log(rooms);

      if (room > 0) {

        const imgCount = () => {

          let i = 0;
          $this.find('.photolog-card').each((index, card) => {

            if (card.dataset.roomId == room) i++;
            $(card).parent().toggleClass('d-none', card.dataset.roomId != room);
          });

          // console.log($this.find('.photolog-card'),i);
          return i;
        };

        const info = $(`<div class="alert alert-primary alert-dismissible fade show">
            <h5 class="alert-heading m-0">Room ${room}</h5>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
          </div>`);

        $this.find('.js-filter-information').append(info);
        info.on('close.bs.alert', () => {

          this.dataset.room = 0;
          $(this).trigger('update-filter');
        });

        // find the element in the rooms array with an element.id that matches room
        const _room = rooms.find(r => r.id == room);
        // console.log( _room);

        if (!!_room) {

          info.find('.alert-heading').text(_room.name);

          <?php if ($enableAI) {  ?>

            info.append('<hr><div class="js-output"></div><div class="js-toolbar"></div>');
            info.append(`<p class="mt-2 mb-0">
              <i class="bi bi-exclamation-triangle text-warning" style="font-size: 2em;"></i>
              note analysing will update entry condition reports with the results</p>`);

            const aiBtns = () => {

              info.find('.js-toolbar').empty();

              if (imgCount() > 0) {

                const payload = {
                  action: 'openai-cache-file-exists',
                  id: <?= $dto->id ?>,
                  room: room,
                };

                _.fetch.post(_.url('<?= $this->route ?>'), payload).then(d => {

                  if ('ack' == d.response) {

                    const btnReprocess = $('<button type="button" class="btn btn-sm btn-outline-primary me-2">reprocess</button>')
                      .on('click', e => {

                        _.hideContexts(e);

                        // console.log(e);
                        // e.target.remove();
                        // return;
                        $(e.target).empty().append('<span class="spinner-border spinner-border-sm"></span>');

                        const payload = {
                          action: 'analyse-damage-reprocess',
                          id: <?= $dto->id ?>,
                          room: room,
                        };

                        _.fetch.post(_.url('<?= $this->route ?>'), payload).then(d => {

                          console.log(d);
                          if ('ack' == d.response) {

                            info.find('.js-output').html(d.reply);
                            e.target.remove();
                          } else {
                            _.growl(d);
                          }
                        });
                      });

                    const btnDelete = $('<button type="button" class="btn btn-sm btn-outline-primary">delete</button>')
                      .on('click', e => {

                        const payload = {
                          action: 'openai-cache-file-delete',
                          id: <?= $dto->id ?>,
                          room: room,
                        };

                        _.fetch.post(_.url('<?= $this->route ?>'), payload).then(d => {

                          if ('ack' == d.response) aiBtns();
                          _.growl(d);
                        });
                      });

                    info.find('.js-toolbar')
                      .append(`<div class="fw-bold pt-1 d-inline-block me-2">
                      <i class="bi bi-exclamation-triangle text-warning"></i> cache file exists</div>`)
                      .append(btnReprocess)
                      .append(btnDelete);

                  } else {

                    const btnAnalyse = $(`<button type="button" class="btn btn-sm btn-outline-primary">
                    analyse condition with AI</button>`).on('click', e => {

                      _.hideContexts(e);

                      // console.log(e);
                      // e.target.remove();
                      // return;
                      $(e.target).empty().append('<span class="spinner-border spinner-border-sm"></span>');

                      const imgs = [];
                      $(this).find('.photolog-card').each((i, card) => {

                        if (card.dataset.roomId == room) {

                          const file = $(card).data('file');
                          // console.log(file);
                          imgs.push(file.file);
                        }
                      });

                      const payload = {
                        action: 'analyse-damage',
                        images: imgs,
                        id: <?= $dto->id ?>,
                        room: room,
                        fake: 0
                      };

                      // console.log(payload);
                      // return;

                      _.fetch.post(_.url('<?= $this->route ?>'), payload).then(d => {

                        // console.log(d);
                        if ('ack' == d.response) {

                          info.find('.js-output').html(d.reply);
                          e.target.remove();
                        } else {

                          $(e.target).replaceWith('<i class="bi bi-exclamation-triangle"></i> an error occurred');
                          _.growl(d);
                        }
                      });
                    });

                    info.find('.js-toolbar').append(btnAnalyse);
                  }
                });
              } else {

                console.log('no images');
              }
            };

            aiBtns();
          <?php } else {  ?>

            imgCount();
            console.log('ai is not enabled ..');
            info.append(`<div class="pt-1 d-inline-block me-2">
                  <i class="bi bi-exclamation-triangle text-warning"></i> <?= $verbatimAI ?></div>`);
          <?php }  ?>
        }

      } else if (room < 0) {

        const info = $(`<div class="alert alert-primary alert-dismissible fade show">
            <h5 class="alert-heading">Untagged</h5>
            <p>untagged cards only</p>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
          </div>`);

        $(this).find('.js-filter-information').append(info);
        $(this).find('.photolog-card').each((i, card) => {

          $(card).parent().toggleClass('d-none', card.dataset.roomId > 0);
        });

      } else {

        $(this).find('.photolog-card').each((i, card) => {

          $(card).parent().removeClass('d-none');
        });
      }
    });

    $('#<?= $_uidTabset ?>-photolog')
      .on('show-room', function(e, room) {

        e.stopPropagation();
        $('#<?= $_uidTabset ?>-photolog-pane')
          .attr('data-room', room)
          .trigger('update-filter');
        $(this).tab('show');
      })
      .on('update-filter', function(e) {

        e.stopPropagation();
        $('#<?= $_uidTabset ?>-photolog-pane').trigger('update-filter');
      });

    $('#<?= $_uidTabset ?>-room')
      .on('hidden.bs.tab', e => $('#<?= $_uidTabset ?>-room-pane').empty())
      .on('show-room', function(e, room) {

        e.stopPropagation();
        $('#<?= $_uidTabset ?>-room-pane').attr('data-room', room).trigger('refresh');
        $(this).tab('show');
      })
      .on('show.bs.tab', e => _.hideContexts(e));

    tabEntryCr
      .on('discover-reports-id', function(e) {

        e.stopPropagation();
        // console.log(this.dataset, !this.dataset.entryexitEntryConditionsReportsId);
        // console.log(this);
        $(this).toggleClass('d-none', !(Number(this.dataset.entryexitEntryConditionsReportsId) > 0));
      })
      .on('show.bs.tab', function(e) {

        _.hideContexts(e);

        const pane = $(this.dataset.bsTarget);
        pane
          .addClass('pt-2')
          .load(_.url('<?= routes::entryconditionreports ?>/view/' + this.dataset.entryexitEntryConditionsReportsId));
      })
      .trigger('discover-reports-id');

    tabEntryCrPDF
      .on('discover-reports-id', function(e) {

        e.stopPropagation();
        $(this).toggleClass('d-none', !(Number(this.dataset.entryexitEntryConditionsReportsId) > 0));
      })
      .on('show.bs.tab', function(e) {

        _.hideContexts(e);

        const id = this.dataset.entryexitEntryConditionsReportsId;
        const pane = $(this.dataset.bsTarget);
        pane
          .empty()
          .addClass('pt-2')
          .append(
            `<div class="d-flex" style="height: calc(100vh - 10rem); width: 100%;">
              <embed class="flex-fill border rounded" src="${_.url(`<?= routes::entryconditionreports ?>/pdf/${id}`)}" 
                title="entry condition report">
            </div>`);
      })
      .trigger('discover-reports-id');

    _.ready(() => $(document).trigger('photolog-display-cards-complete'));
  })(_brayworth_);
</script>