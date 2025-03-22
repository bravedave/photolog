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

use cms\{currentUser, routes, strings};
use cms;

$bootstrap = $bootstrap ?? 4;
$uidTabs = strings::rand();  ?>

<ul class="nav nav-tabs" id="myTab" role="tablist">

  <li class="nav-item" role="presentation">
    <button class="nav-link px-2" type="button" role="tab" data-bs-toggle="tab"
      id="<?= $uidTabs ?>-aside-tab"
      data-bs-target="#<?= $uidTabs ?>-aside-pane" aria-controls="<?= $uidTabs ?>-aside-pane"
      aria-selected="true"><i class="bi bi-list"></i></button>
  </li>

  <li class="nav-item me-auto" role="presentation">
    <button class="nav-link active" type="button" role="tab" data-bs-toggle="tab"
      id="<?= $uidTabs ?>-menu-tab"
      data-bs-target="#<?= $uidTabs ?>-menu-pane" aria-controls="<?= $uidTabs ?>-menu-pane"
      aria-selected="true">PhotoLog</button>
  </li>

  <li class="nav-item" role="presentation">
    <button class="nav-link px-2" type="button" role="tab" data-bs-toggle="tab"
      id="<?= $uidTabs ?>-profile-tab"
      data-bs-target="#<?= $uidTabs ?>-profile-pane"
      aria-controls="<?= $uidTabs ?>-profile-panel" aria-selected="false">
      <i class="bi bi-gear"></i></button>
  </li>
</ul>

<div class="tab-content" id="<?= $uidTabs ?>">

  <div class="tab-pane fade py-2" role="tabpanel" tabindex="-1"
    id="<?= $uidTabs ?>-aside-pane" aria-labelledby="<?= $uidTabs ?>-aside-tab">
  </div>

  <div class="tab-pane fade show active" role="tabpanel" tabindex="-1"
    id="<?= $uidTabs ?>-menu-pane"
    aria-labelledby="<?= $uidTabs ?>-menu-tab">

    <nav class="nav flex-column mt-2" id="<?= $_uidNav = strings::rand() ?>">
      <?php
      if ($dto ?? null) {

        if ($referer ?? null) {  ?>

          <a href="<?= strings::url(sprintf('%s/view/%d?f=%d', $this->route, $dto->id, $referer->id)); ?>">
            <h6><?= $this->title ?></h6>
          </a>

          <div class="fw-light">record #<?= $dto->id ?></div>

          <a class="nav-link js-run-carousel" href="#">carousel</a>

          <div class="js-folders"></div>

          <a class="nav-link" href="<?= strings::url('property/view/' . $referer->id); ?>">
            <?= strings::GoodStreetString($referer->address_street) ?></a>

          <script>
            (_ => {

              const nav = $('#<?= $_uidNav ?>');
              _.ready(() => {

                _.fetch.post(_.url('<?= $this->route ?>'), {
                  action: 'get-photolog',
                  property: <?= $referer->id ?>
                }).then(d => {

                  if ('ack' == d.response) {

                    if (d.data.length > 0) {

                      // reverse
                      d.data.reverse();
                      d.data.forEach(entry => {

                        const m = _.dayjs(entry.date);
                        const href = _.url('<?= $this->route ?>/view/' + entry.id + '?f=<?= $referer->id ?>');
                        const text = entry.subject + ' (' + entry.files.total + ')';
                        const a = $(`<a class="d-block text-truncate py-2" href="${href}"></a>`)
                          .text(text).attr('title', text + ' ' + m.format('l'));

                        a.insertAfter(nav);
                      });
                    }
                  } else {

                    _.growl(d);
                  }
                });
              });
            })(_brayworth_);
          </script>
        <?php
        } else {  ?>
          <a href="<?= strings::url($this->route . '/view/' . $dto->id); ?>">
            <h6><?= $title ?></h6>
          </a>

          <div class="fw-light">record #<?= $dto->id ?></div>

          <a class="nav-link" href="<?= strings::url(sprintf('%s/?property=%d', $this->route, $dto->property_id)); ?>">
            <i class="bi bi-arrow-left-short"></i>
            <?= strings::GoodStreetString($dto->address_street) ?>
          </a>

          <a class="nav-link js-run-carousel" href="#">carousel</a>
          <div class="js-folders"></div>
        <?php } ?>

        <a class="nav-link js-add-entry-on-property" data-property-id="<?= $dto->property_id ?>"
          href="#"><i class="bi bi-plus"></i> add entry on <?= strings::GoodStreetString($dto->address_street) ?></a>

        <a class="nav-link js-list-all" href="<?= strings::url($this->route); ?>">
          <i class="bi bi-arrow-left-short"></i>list all</a>

      <?php } elseif ($referer ?? null) {  ?>

        <a href="<?= strings::url(sprintf('%s/?property=%d', $this->route, $referer->id)); ?>">
          <h6><?= strings::GoodStreetString($referer->address_street) ?></h6>
        </a>

        <a class="nav-link js-add-entry-on-property" data-property-id="<?= $referer->id ?>"
          href="#"><i class="bi bi-plus"></i>
          add entry on <?= strings::GoodStreetString($referer->address_street) ?></a>

        <div class="js-folders"></div>
        <a class="nav-link" href="<?= strings::url($this->route); ?>">
          <i class="bi bi-arrow-left-short"></i> list all</a>
      <?php } else {  ?>

        <a href="<?= strings::url($this->route); ?>">
          <h6><?= $title ?></h6>
        </a>
      <?php } ?>

      <?php if ($dto ?? null) {  ?>

        <a class="nav-link js-generate-public-link" href="#">generate public link</a>

        <div class="row g-2 d-none">

          <div class="col">
            <input type="text" class="form-control js-public-link" readonly>
            <div class="form-text text-right js-public-link-expires"></div>
          </div>
        </div>

        <div class="row g-2 mb-2 d-none js-public-link-toolbar">

          <div class="col">

            <div class="btn-group btn-group-sm d-flex" aria-label="Public link toolbar">

              <button type="button" class="btn btn-light flex-fill js-copy-to-clipboard"
                title="copy to clipboard"><i class="bi bi-clipboard"></i></button>
              <button type="button" class="btn btn-light flex-fill js-public-link-clear"
                title="clear link"><i class="bi bi-trash"></i></button>
              <button type="button" class="btn btn-light flex-fill js-public-link-email"
                title="email link"><i class="bi bi-cursor"></i></button>
              <button type="button" class="btn btn-light flex-fill js-public-link-regenerate"
                title="regenerate link"><i class="bi bi-arrow-repeat"></i></button>
              <button type="button" class="btn btn-light flex-fill js-public-link-view"
                title="view on portal"><i class="bi bi-box-arrow-up-right"></i></button>
            </div>
          </div>
        </div>
      <?php  }  ?>

      <?= 5 == $bootstrap ? '<div class="d-grid gap-2">' : '' ?>
      <button class="btn btn-outline-primary js-add-entry <?= $bootstrap < 5 ? 'btn-block' : '' ?>"
        type="button">add entry</button>

      <?= currentUser::isDavid() ? '<button class="btn btn-light js-run-cron">run cron</button>' : '' ?>
      <?= 5 == $bootstrap ? '</div>' : '' ?>
    </nav>
  </div>

  <div class="tab-pane fade py-2" role="tabpanel" tabindex="-1"
    id="<?= $uidTabs ?>-profile-pane" aria-labelledby="<?= $uidTabs ?>-profile-tab">

    <?php if ($dto ?? null) {  ?>
      <div class="form-check">

        <input type="checkbox" class="form-check-input" name="entry_condition_report"
          value="1" <?= $dto->entry_condition_report ? 'checked' : '' ?>
          id="<?= $_uid = strings::rand() ?>">
        <label class="form-check-label" for="<?= $_uid ?>">
          entry condition report
        </label>
      </div>

      <div class="js-entry-condition-reports"
        data-entryexit-entry-conditions-reports-id="<?= $dto->entryexit_entry_conditions_reports_id ?>"
        data-property-id="<?= $dto->property_id ?>"></div>

      <button class="btn btn-light btn-sm js-entry-condition-reports-add m-3 d-none"
        data-property-id="<?= $dto->property_id ?>">
        new <?= cms\entryexit\entryconditionreports\config::label_short ?>
      </button>
    <?php  }  ?>
  </div>

  <script>
    (_ => {
      const tabSet = $('#<?= $uidTabs ?>');
      const asideTab = $('#<?= $uidTabs ?>-aside-tab');
      const menuTab = $('#<?= $uidTabs ?>-menu-tab');
      const profileTab = $('#<?= $uidTabs ?>-profile-tab');
      const nav = $('#<?= $_uidNav ?>');

      <?php if ($dto ?? null) {  ?>

        const dragDropClear = () => {

          const cards = $('.photolog-card');
          cards.each((i, card) => {

            delete card.dataset.selected;

            $(card)
              .removeAttr('draggable')
              .off('dragstart');

            $(card).find('img').removeAttr('draggable')
          });
        };

        const dragDropSetup = () => {

          const cards = $('.photolog-card');
          cards.each((i, card) => {

            $(card)
              .attr('draggable', "true")
              .on('dragstart', function(e) {

                e.originalEvent.dataTransfer.setData("text/plain", e.target.id);
                e.originalEvent.dataTransfer.effectAllowed = "move";

                $(this).trigger('get-selected');
              });

            $(card).find('img').attr('draggable', "false")
          });

          // console.log(cards.first());
          // console.log('drag-drop-setup');

          const cssDropHere = 'border border-info bg-info-subtle';

          nav.find('.js-room-folder').each((i, folder) => {

            if (folder.dataset.room == 0) return; // this is view all
            if (!!folder.dataset.dragTarget) return;

            folder.dataset.dragTarget = true;

            $(folder)
              .on('dragover', e => e.preventDefault())
              .on('dragenter', function(e) {

                e.preventDefault();
                $(this).addClass(cssDropHere);
                e.originalEvent.dataTransfer.dropEffect = "move";
              })
              .on('dragleave', function(e) {

                e.preventDefault();
                $(this).removeClass(cssDropHere);
              })
              .on('drop', function(e) {

                _.hideContexts(e);

                // Get the data, which is the id of the drop target
                const data = e.originalEvent.dataTransfer.getData("text");
                if (data != '') {

                  const element = $(`#${data}`);

                  if (element.length > 0) {

                    const tagElement = element => {

                      const srcData = element.data();
                      const payload = {
                        action: 'photolog-tag-to-room',
                        file: srcData.file.description,
                        room_id: this.dataset.room,
                        id: <?= $dto->id ?>
                      };

                      // console.log(payload);
                      _.fetch.post(_.url('<?= $this->route ?>'), payload)
                        .then(d => {

                          if ('ack' == d.response) element.trigger('refresh-from-server');
                          _.growl(d);
                        });
                    };

                    const selected = element[0].dataset.selected;
                    if (!!selected) {

                      selected.split(',').forEach(id => {
                        const element = $(`#${id}`);
                        tagElement(element);
                      });
                    } else {

                      tagElement(element);
                    }
                  }

                  e.originalEvent.dataTransfer.clearData(); // Clear the drag data cache (for all formats/types)
                } else {

                  console.log('no data', e);
                }
                $(this).trigger('dragleave');
              });
          });
        };

        tabSet.find('.js-folders')
          .on('clear', function(e) {

            e.stopPropagation();
            $(this).empty();
          })
          .on('refresh', function(e) {

            e.stopPropagation();
            $(this).empty();
            dragDropClear();

            <?php if ($dto->property_id) { ?>

              // console.log('refresh folders');
              _.fetch.post(_.url('property'), {
                action: 'property-get-rooms-for-property',
                id: <?= $dto->property_id ?>
              }).then(d => {

                if ('ack' == d.response) {

                  if (!d.data || !d.data.length) return;

                  $(this).append(`<div class="ps-3 js-room-folder" data-room="0"><i class="bi bi-folder2-open"></i> All rooms</div>`);
                  $.each(d.data, (i, room) => {

                    $(this).append(
                      `<div class="ps-4 js-room-folder" data-room="${room.id}"><i class="bi bi-folder2"></i> ${room.name}</div>`);
                    // console.log(room);
                  });
                  $(this).append(`<div class="ps-4 js-room-folder" data-room="-1"><i class="bi bi-folder2"></i> un tagged</div>`);


                  $(this).find('.js-room-folder').each((i, folder) => {

                    $(folder)
                      .addClass('pointer')
                      .on('click', function(e) {

                        $('.js-photolog-tab').trigger('show-room', this.dataset.room);
                      });
                  })

                  dragDropSetup();
                }
              });
            <?php  }  ?>
          });

        tabSet.find('input[name="entry_condition_report"]').on('change', function(e) {

          _.fetch.post(_.url('<?= $this->route ?>'), {
              action: 'entry-condition-report-set',
              id: <?= $dto->id ?>,
              value: this.checked ? 1 : 0
            })
            .then(d => {

              tabSet.find('.js-folders').trigger(this.checked ? 'refresh' : 'clear');
              tabSet.find('.js-entry-condition-reports-add').toggleClass('d-none', !this.checked);

              if (this.checked) {

                tabSet.find('.js-entry-condition-reports').trigger('refresh')
              } else {

                tabSet.find('.js-entry-condition-reports').empty();
              }

              _.growl(d);
            });
        })

        <?php if ($dto->entry_condition_report) { ?>

          tabSet.find('.js-folders').trigger('refresh');
          $(document).on('photolog-display-cards-complete', dragDropSetup);
          $(document).on('property-updated', e => tabSet.find('.js-folders').trigger('refresh'));
        <?php  }  ?>

        nav.find('.js-generate-public-link')
          .on('refresh', function(e) {

            let _me = $(this);

            _.fetch.post(_.url('<?= $this->route ?>'), {
              action: 'public-link-get',
              id: <?= $dto->id ?>
            }).then(d => {

              if ('ack' == d.response) {

                _me.addClass('d-none');

                nav.find('.js-public-link').closest('.row').removeClass('d-none');
                nav.find('.js-public-link-toolbar').removeClass('d-none');
                nav.find('.js-public-link').val(d.url);
                nav.find('.js-public-link-expires').text('expires : ' + _.dayjs(d.expires).format('l'));
              } else {

                _me.removeClass('d-none');
                nav.find('.js-public-link-toolbar').addClass('d-none');
                nav.find('.js-public-link').closest('.row').addClass('d-none');
              }
            });
          })
          .on('clear-link', function(e) {

            let _me = $(this);

            _.fetch.post(_.url('<?= $this->route ?>'), {
              action: 'public-link-clear',
              id: <?= $dto->id ?>
            }).then(d => {

              _.growl(d);
              if ('ack' == d.response) _me.trigger('refresh');
            });
          })
          .on('create-link', function(e) {

            const _me = $(this);
            _.get.modal(_.url('<?= $this->route ?>/publicLink/<?= $dto->id ?>'))
              .then(m => m.on('success', d => _me.trigger('refresh')));
          })
          .on('click', function(e) {

            e.stopPropagation();
            e.preventDefault();

            $(this).trigger('create-link');
          })
          .on('email-link', function(e) {

            if (!!_.email.activate) {

              _.email.activate({
                subject: <?= json_encode(sprintf('%s - %s', strings::GoodStreetString($dto->address_street), $dto->subject)) ?>,
                message: `<br><br>View the images on our portal <a href="${nav.find('.js-public-link').val()}">here</a><br><br>${!!window._cms_ ? _cms_.currentUser.signoff : ''}`
              })
            } else {

              _.ask.alert('no email program');
            }
          });

        _.ready(() => nav.find('.js-public-link-email').toggleClass('d-none', !_.email.activate));

        nav.find('.js-public-link-clear').on('click', e => nav.find('.js-generate-public-link').trigger('clear-link'));
        nav.find('.js-public-link-regenerate').on('click', e => nav.find('.js-generate-public-link').trigger('create-link'));
        nav.find('.js-public-link-email').on('click', e => nav.find('.js-generate-public-link').trigger('email-link'));
        nav.find('.js-public-link-view').on('click', e => window.open(nav.find('.js-public-link').val()));
        nav.find('.js-generate-public-link').trigger('refresh');
      <?php  }  ?>

      _.photolog = {
        cron: () => {

          _.fetch.post(_.url('photolog'), {
            'action': 'cron',
          }).then(_.growl);
        }
      };
      // console.log(nav.find('.js-run-cron'));
      // console.log('run the cron job manually with : _brayworth_.photolog.cron()');

      nav.find('.js-copy-to-clipboard').on('click', e => {
        const el = nav.find('.js-public-link')[0];

        /* Select the text field */
        el.select();
        el.setSelectionRange(0, 99999); /*For mobile devices*/

        // document.execCommand("copy"); /* Copy the text inside the text field */
        navigator.clipboard.writeText(el.value);
        _.growl('Copied');
      });

      nav.find('.js-run-cron').on('click', function(e) {

        $(this).empty()
          .html('<i class="spinner-border spinner-border-sm"></i> running ..');

        _.fetch.post(_.url('<?= routes::photolog ?>'), {
          'action': 'cron',
        }).then(d => {

          $(this).text('run cron ..');
          _.growl(d);
        });
      });

      nav.find('.js-run-carousel').on('click', e => {

        _.hideContexts(e);
        e.preventDefault();
        $(document).trigger('photolog-carousel');
      });

      nav.find('.js-add-entry').on('click', e => {

        _.hideContexts(e);
        _.get.modal(_.url('<?= $this->route ?>/entry'))
          .then(d => d.on('success', (e, href) => location.href = href));
      });

      nav.find('.js-add-entry-on-property').on('click', function(e) {

        _.hideContexts(e);
        e.preventDefault();

        _.get.modal(_.url(`<?= $this->route ?>/entry?property=${this.dataset.propertyId}`))
          .then(d => d.on('success', (e, href) => location.href = href));
      });

      <?php if ($dto ?? null) {  ?>

        tabSet.find('.js-entry-condition-reports-add').on('click', function(e) {

          _.hideContexts(e);

          const url = _.url('<?= routes::entryconditionreports ?>/edit/?property_id=' + this.dataset.propertyId);
          _.get.modal(url).then(m => m.on('success', (e, data) => {

            const payload = {
              action: 'photolog-set-associated-entry-condition-report',
              id: <?= $dto->id ?>,
              entryexit_entry_conditions_reports_id: data.id
            };

            _.fetch.post(_.url('<?= $this->route ?>'), payload).then(_.growl);

            tabSet.find('.js-entry-condition-reports')
              .attr('data-entryexit-entry-conditions-reports-id', data.id)
              .trigger('refresh');

            /** this is in another tab, if you discover or this, let it know */
            $('.js-entry-cr-tab')
              .attr('data-entryexit-entry-conditions-reports-id', data.id)
              .trigger('discover-reports-id');
          }));
        })

        tabSet.find('.js-entry-condition-reports').on('refresh', function(e) {

          const $this = $(this);

          tabSet.find('.js-entry-condition-reports-add').removeClass('d-none');

          _.fetch.post(_.url('<?= routes::entryconditionreports ?>'), {
            action: 'get-entry-condition-reports-for-property-id',
            id: this.dataset.propertyId
          }).then(d => {

            if ('ack' == d.response) {

              $this.empty()
                .append('<h6 class="my-2"><?= cms\entryexit\entryconditionreports\config::label_short ?></h6>');
              $.each(d.data, (i, report) => {

                const checked = report.id == this.dataset.entryexitEntryConditionsReportsId;

                const uid = _.randomString();
                $this.append(
                  `<div class="form-check">
                  <input type="radio" class="form-check-input js-entry-condition-report" id="${uid}" 
                    value="${report.id}"
                    ${checked ? 'checked' : ''}>
                  <label class="form-check-label" for="${uid}">${_.asLocaleDate(report.date)}</label>
                </div>`);
              });

              // console.log(d);
              tabSet.find('.js-entry-condition-reports input.js-entry-condition-report')
                .on('change', function(e) {

                  const payload = {
                    action: 'photolog-set-associated-entry-condition-report',
                    id: <?= $dto->id ?>,
                    entryexit_entry_conditions_reports_id: this.value
                  };

                  _.fetch.post(_.url('<?= $this->route ?>'), payload).then(_.growl);

                  /** this is in another tab, if you discover or this, let it know */
                  $('.js-entry-cr-tab')
                    .attr('data-entryexit-entry-conditions-reports-id', this.value)
                    .trigger('discover-reports-id');
                });
            }
            _.growl(d);
          });
        });
      <?php  }  ?>

        [asideTab, menuTab, profileTab].forEach(tab => tab
          .on('hide.bs.tab', e => e.stopPropagation())
          .on('hidden.bs.tab', e => e.stopPropagation())
          .on('show.bs.tab', e => e.stopPropagation())
          .on('shown.bs.tab', e => e.stopPropagation()))

      asideTab.on('show.bs.tab', function(e) {

        $(this.dataset.bsTarget).load(_.url('<?= $this->route ?>/menu'));
      });
      profileTab.on('show.bs.tab', e => tabSet.find('.js-entry-condition-reports').trigger('refresh'));

      <?php if ($dto ?? null) {  ?>
      <?php  }  ?>

    })(_brayworth_);
  </script>
</div>

<p>&nbsp;</p>