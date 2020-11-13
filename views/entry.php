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
	if ( !$dto->id && $dto->property_id) {
		$autoFocus = 'subject';

	}
	?>

<form id="<?= $uid ?>frm">
	<input type="hidden" name="id" value="<?= $dto->id ?>" />
	<input type="hidden" name="property_id" value="<?= $dto->property_id ?>" />
	<input type="hidden" name="action" value="<?= (int)$dto->id ? 'update-entry' : 'add-entry' ?>" />

	<div class="form-group row">
		<div class="col">
			<input type="text" class="form-control"
				name="address_street" placeholder="address" required
				<?php if ( 'street' == $autoFocus) print 'autofocus' ?>
				autocomplete="off" value="<?= $dto->address_street ?>" />

		</div>

	</div>

	<div class="form-group row">
		<label class="col-md-2 col-form-label" for="<?= $uid ?>subject">
			subject

		</label>

		<div class="col">
			<input type="text" class="form-control" name="subject"
				placeholder="log entry" required
				<?php if ( 'subject' == $autoFocus) print 'autofocus' ?>
				id="<?= $uid ?>subject"
				value="<?= $dto->subject ?>" />

		</div>

	</div>

	<div class="form-group row">
		<label class="col-md-2 col-form-label" for="<?= $uid ?>date">
			date

		</label>

		<div class="col">
			<input type="date" class="form-control" name="date" placeholder="log entry" required id="<?= $uid ?>date" value="<?= $dto->date ?>" />

		</div>

	</div>


	<div class="form-group row">
		<div class="offset-md-2 col">
			<button class="btn btn-primary"><?= (int)$dto->id ? 'update' : 'add entry' ?></button>

		</div>

	</div>

</form>
<script>
$(document).ready( function() {
	$('input[name="address_street"]', '#<?= $uid ?>frm').autofill({
		autoFocus : true,
		source: _cms_.search.address,
		select: function(event, ui) {
			let o = ui.item;
			$('input[name="property_id"]', '#<?= $uid ?>frm').val( o.id);

		}

	});

	$('#<?= $uid ?>frm').on( 'submit', function( e) {
		let frm = $(this);
		let data = frm.serializeFormJSON();
		console.log( data);
		//~ return false;

		_cms_.post({
			url : _cms_.url('property_photolog'),
			data : data,

		}).then( function( d) {
			if ( 'ack' == d.response) {
				frm.closest( '.modal').trigger( 'brayworth.success', _cms_.url( 'property_photolog/view/' + d.id));

			}
			else {
				_cms_.growl( d);

			}
			frm.closest( '.modal').modal( 'hide');

		});

		return false;

	});

});
</script>
