<?php
/*
	David Bray
	BrayWorth Pty Ltd
	e. david@brayworth.com.au

	This work is licensed under a Creative Commons Attribution 4.0 International Public License.
		http://creativecommons.org/licenses/by/4.0/
	*/

	$uid = strings::rand();

	?>
<form id="<?= $uid ?>frm">
	<input type="hidden" name="id" value="<?= $this->data->dto->id ?>" />
	<input type="hidden" name="action" value="save-notepad" />

	<div class="form-group row">
		<div class="col">
			<textarea class="form-control" name="notes" rows="10"><?= $this->data->dto->notes ?></textarea>

		</div>

	</div>

	<div class="form-group row">
		<div class="col">
			<button class="btn btn-primary">save</button>

		</div>

	</div>

</form>
<script>
$(document).ready( () => { ( _ => {
	$('#<?= $uid ?>frm').on( 'submit', function( e) {
		let frm = $(this);
		let data = frm.serializeFormJSON();
		//~ console.log( data);

		_.post({
			url : _.url('<?= $this->route ?>'),
			data : data,

		}).then( function( d) {
			_.growl( d);
			frm.closest('.modal').trigger( 'brayworth.success', d).modal('hide');

		});

		return false;

	});

}) (_brayworth_); });
</script>