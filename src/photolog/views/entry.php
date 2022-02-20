<?php
/*
 * David Bray
 * BrayWorth Pty Ltd
 * e. david@brayworth.com.au
 *
 * MIT License
 *
*/

$uid = strings::rand();
$dto = $this->data->dto;

$autoFocus = 'street';
if (!$dto->id && $dto->property_id) $autoFocus = 'subject';	?>

<form id="<?= $_form = strings::rand() ?>" autocomplete="off">
	<input type="hidden" name="id" value="<?= $dto->id ?>" />
	<input type="hidden" name="property_id" value="<?= $dto->property_id ?>" />
	<input type="hidden" name="action" value="<?= (int)$dto->id ? 'update-entry' : 'add-entry' ?>" />

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
					<div class="form-row mb-2">
						<div class="col">
							<input type="text" class="form-control" name="address_street" placeholder="address" required <?= 'street' == $autoFocus ? 'autofocus' : '' ?> autocomplete="off" value="<?= $dto->address_street ?>">

						</div>

					</div>

					<div class="form-row mb-2">
						<label class="col-md-2 col-form-label" for="<?= $_uid = strings::rand() ?>">
							subject

						</label>

						<div class="col">
							<input type="text" class="form-control" name="subject" placeholder="log entry" required <?= 'subject' == $autoFocus ? 'autofocus' : '' ?> id="<?= $_uid ?>" value="<?= $dto->subject ?>">

						</div>

					</div>

					<div class="form-group row">
						<label class="col-md-2 col-form-label" for="<?= $_uid = strings::rand() ?>">
							date

						</label>

						<div class="col">
							<input type="date" class="form-control" name="date" placeholder="log entry" required id="<?= $_uid ?>" value="<?= $dto->date ?>">

						</div>

					</div>

				</div>

				<div class="modal-footer">
					<button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
					<button type="submit" class="btn btn-primary"><?= (int)$dto->id ? 'update' : 'add entry' ?></button>

				</div>

			</div>

		</div>

	</div>

	<script>
		(_ => $(document).ready(() => {
			$('input[name="address_street"]', '#<?= $_form ?>').autofill({
				autoFocus: true,
				source: _.search.address,
				select: function(event, ui) {
					let o = ui.item;
					$('input[name="property_id"]', '#<?= $_form ?>').val(o.id);

				}

			});

			$('#<?= $_form ?>')
				.on('submit', function(e) {
					let _form = $(this);
					let _data = _form.serializeFormJSON();

					// console.table( _data);
					// return false;

					_.post({
						url: _.url('<?= $this->route ?>'),
						data: _data,

					}).then(function(d) {
						if ('ack' == d.response) {
							$('#<?= $_modal ?>').trigger('success', _.url('<?= $this->route ?>/view/' + d.id));

						} else {
							_.growl(d);

						}

						$('#<?= $_modal ?>').modal('hide');

					});

					return false;

				});

		}))(_brayworth_);
	</script>

</form>