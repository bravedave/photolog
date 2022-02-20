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

use strings;
use theme;

$dto = $this->data->dto;
?>

<form id="<?= $_form = strings::rand() ?>" autocomplete="off">
	<input type="hidden" name="id" value="<?= $dto->id ?>">
	<input type="hidden" name="action" value="save-notepad">

	<div class="modal fade" tabindex="-1" role="dialog" id="<?= $_modal = strings::rand() ?>" aria-labelledby="<?= $_modal ?>Label" aria-hidden="true">
		<div class="modal-dialog modal-dialog-centered" role="document">
			<div class="modal-content">
				<div class="modal-header <?= theme::modalHeader() ?>">
					<h5 class="modal-title" id="<?= $_modal ?>Label"><?= $this->title ?></h5>
					<button type="button" class="close" data-dismiss="modal" aria-label="Close">
						<span aria-hidden="true">&times;</span>
					</button>
				</div>
				<div class="modal-body">
					<textarea class="form-control" name="notes" rows="10"><?= $dto->notes ?></textarea>
				</div>
				<div class="modal-footer">
					<button type="submit" class="btn btn-primary">Save</button>
				</div>
			</div>
		</div>
	</div>
	<script>
		(_ => $('#<?= $_modal ?>').on('shown.bs.modal', () => {
			$('#<?= $_form ?>')
				.on('submit', function(e) {
					let _form = $(this);
					let _data = _form.serializeFormJSON();

					// console.table( _data);
					_.post({
						url: _.url('<?= $this->route ?>'),
						data: _data,

					}).then(d => {
						_.growl(d);
						if ('ack' == d.response) {
							$('#<?= $_modal ?>')
								.trigger('success', d)
								.modal('hide');

						}

					});

					return false;
				});
		}))(_brayworth_);
	</script>
</form>