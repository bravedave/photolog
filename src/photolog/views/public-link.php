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

?>

<form id="<?= $_form = strings::rand() ?>" autocomplete="off">
  <input type="hidden" name="action" value="public-link-create" />
  <input type="hidden" name="id" value="<?= $dto->id ?>" />

  <div class="modal fade" tabindex="-1" role="dialog" id="<?= $_modal = strings::rand() ?>" aria-labelledby="<?= $_modal ?>Label"
    aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
      <div class="modal-content">

        <div class="modal-header <?= theme::modalHeader() ?>">
          <h5 class="modal-title" id="<?= $_modal ?>Label"><?= $this->title ?></h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>

        <div class="modal-body">

          <div class="input-group">

            <input class="form-control" type="date" required name="public_link_expires" />
            <button type="button" class="btn btn-outline-secondary js-bump"
              data-bump="1">+1</button>
            <button type="button" class="btn btn-outline-secondary js-bump"
              data-bump="7">+7</button>
            <button type="button" class="btn btn-outline-secondary js-bump"
              data-bump="30">+30</button>
            <div class="input-group-text">days</div>
          </div>
        </div>

        <div class="modal-footer">

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

        modal.find('.js-bump').on('click', function(e) {

          _.hideContexts(e);

          $('input[name="public_link_expires"]').val(_.dayjs().add(this.dataset.bump, 'd').format('YYYY-MM-DD'));
          $('input[name="public_link_expires"]').focus();
        })

        form.on('submit', function(e) {

          try {

            _.fetch.post.form(_.url('<?= $this->route ?>'), this)
              .then(d => {

                if ('ack' == d.response) {

                  modal.trigger('success');
                  modal.modal('hide');
                } else {

                  _.growl(d);
                }
              });
          } catch (error) {

            console.error(error);
          }

          return false;
        });
      });
    })(_brayworth_);
  </script>
</form>