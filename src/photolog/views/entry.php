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

use cms\{strings, theme};

$autoFocus = 'street';
if (!$dto->id && $dto->property_id) $autoFocus = 'subject';  ?>

<form id="<?= $_form = strings::rand() ?>" autocomplete="off">
  <input type="hidden" name="id" value="<?= $dto->id ?>">
  <input type="hidden" name="property_id" value="<?= $dto->property_id ?>">
  <input type="hidden" name="action" value="<?= (int)$dto->id ? 'update-entry' : 'add-entry' ?>">

  <div class="modal fade" tabindex="-1" role="dialog" id="<?= $_modal = strings::rand() ?>"
    aria-labelledby="<?= $_modal ?>Label" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
      <div class="modal-content">
        <div class="modal-header <?= theme::modalHeader() ?>">
          <h5 class="modal-title" id="<?= $_modal ?>Label"><?= $this->title ?></h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"
            aria-label="Close"></button>
        </div>

        <div class="modal-body">

          <div class="row g-2">

            <div class="col mb-2">

              <input type="text" class="form-control" name="address_street"
                placeholder="address" autocomplete="off" required value="<?= $dto->address_street ?>">
            </div>
          </div>

          <div class="row g-2">

            <label class="col-md-2 col-form-label" for="<?= $_uid = strings::rand() ?>">subject</label>
            <div class="col mb-2">

              <input type="text" class="form-control" name="subject"
                placeholder="log entry" required id="<?= $_uid ?>" value="<?= $dto->subject ?>">
            </div>
          </div>

          <div class="row g-2">

            <label class="col-md-2 col-form-label" for="<?= $_uid = strings::rand() ?>">date</label>

            <div class="col mb-2">

              <input type="date" class="form-control" name="date" placeholder="log entry"
                required id="<?= $_uid ?>" value="<?= $dto->date ?>">
            </div>
          </div>
        </div>

        <div class="modal-footer">

          <button type="button" class="btn btn-outline-secondary"
            data-bs-dismiss="modal">close</button>
          <button type="submit" class="btn btn-primary">Save</button>
        </div>
      </div>
    </div>
  </div>
  <script>
    (_ => {
      const form = $('#<?= $_form ?>');
      const modal = $('#<?= $_modal ?>');

      modal.on('shown.bs.modal', () => {

        form.find('input[name="address_street"]')
          .autofill({
            autoFocus: true,
            source: _.search.address,
            select: function(event, ui) {

              let o = ui.item;
              form.find('input[name="property_id"]').val(o.id);
            }
          });

        form
          .on('submit', function(e) {
            let _form = $(this);
            let _data = _form.serializeFormJSON();

            // console.table( _data);
            _.post({
              url: _.url('<?= $this->route ?>'),
              data: _data,
            }).then(d => {

              if ('ack' == d.response) {

                modal.trigger('success', _.url('<?= $this->route ?>/view/' + d.id));
              } else {

                _.growl(d);
              }

              modal.modal('hide');
            });

            return false;
          });

        <?php if ('street' == $autoFocus) {  ?>

          form.find('input[name="address_street"]').focus();
        <?php } elseif ('subject' == $autoFocus) {  ?>

          form.find('input[name="subject"]').focus();
        <?php  } ?>

      });
    })(_brayworth_);
  </script>
</form>