<?php
/*
 * David Bray
 * BrayWorth Pty Ltd
 * e. david@brayworth.com.au
 *
 * MIT License
 *
*/  ?>

<div id="<?= $_wrap = strings::rand() ?>">
  <form id="<?= $_form = strings::rand() ?>" autocomplete="off">
    <input type="hidden" name="action" value="public-link-create" />
    <input type="hidden" name="id" value="<?= $this->data->dto->id ?>" />

    <div class="modal fade" tabindex="-1" role="dialog" id="<?= $_modal = strings::rand() ?>" aria-labelledby="<?= $_modal ?>Label" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
          <div class="modal-header bg-secondary text-white py-2">
            <h5 class="modal-title" id="<?= $_modal ?>Label"><?= $this->title ?></h5>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
              <span aria-hidden="true">&times;</span>
            </button>

          </div>

          <div class="modal-body">
            <div class="input-group">
                <input class="form-control" type="date" required name="public_link_expires" />

                <div class="input-group-append">
                  <button class="btn btn-outline-secondary" type="button" id="<?= $_uid = strings::rand() ?>1">+1</button>
                </div>

                <div class="input-group-append">
                  <button class="btn btn-outline-secondary" type="button" id="<?= $_uid ?>7">+7</button>
                </div>

                <div class="input-group-append">
                  <button class="btn btn-outline-secondary" type="button" id="<?= $_uid ?>30">+30</button>
                </div>

                <div class="input-group-append">
                  <div class="input-group-text">days</div>
                </div>

            </div>
            <script>
            ( _ => {
              $('#<?= $_uid ?>1').on( 'click', e => {
                $('input[name="public_link_expires"]').val( _.dayjs().add(1,'d').format('YYYY-MM-DD'));
                $('input[name="public_link_expires"]').focus();

              });

              $('#<?= $_uid ?>7').on( 'click', e => {
                $('input[name="public_link_expires"]').val( _.dayjs().add(7,'d').format('YYYY-MM-DD'));
                $('input[name="public_link_expires"]').focus();

              });

              $('#<?= $_uid ?>30').on( 'click', e => {
                $('input[name="public_link_expires"]').val( _.dayjs().add(30,'d').format('YYYY-MM-DD'));
                $('input[name="public_link_expires"]').focus();

              });

            })(_brayworth_);
            </script>

          </div>

          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
            <button type="submit" class="btn btn-primary">Save</button>

          </div>

        </div>

      </div>

    </div>

  </form>

  <script>
  $(document).ready( () => {

      $('#<?= $_modal ?>').on( 'hidden.bs.modal', e => { $('#<?= $_wrap ?>').remove(); });
      $('#<?= $_modal ?>').modal( 'show');

      $('#<?= $_form ?>')
      .on( 'submit', function( e) {
          let _form = $(this);
          let _data = _form.serializeFormJSON();
          let _modalBody = $('.modal-body', _form);

          ( _ => {
              _.post({
                  url : _.url('<?= $this->route ?>'),
                  data : _data,

              }).then( d => {
                  if ( 'ack' == d.response) {
                      $('#<?= $_modal ?>').trigger( 'success');

                  }
                  else {
                      _.growl( d);

                  }

                  $('#<?= $_modal ?>').modal( 'hide');

              });

          }) (_brayworth_);

          return false;

      });

  });
  </script>

</div>
