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
<div class="form-group row">
	<div class="col">
		<input type="search" class="form-control" autofocus id="<?= $uid ?>search" />

	</div>

</div>
<script>
$(document).ready( function() {
	$('#<?= $uid ?>search').on( 'keyup', function( e) {
		let _me = $(this)
		let txt = _me.val();

		if ( '' == txt.trim()) {
			$('tbody > tr', 'table[data-role="photolog-table"]').removeClass('d-none');	// remove filtering

		}
		else {
			$('tbody > tr', 'table[data-role="photolog-table"]').each( function( i, tr) {
				let _tr = $(tr);
				let str = _tr.text();
				if ( str.match( new RegExp(txt, 'gi'))) {
					_tr.removeClass('d-none');

				}
				else {
					_tr.addClass('d-none');

				}

			});

		}

	});

});
</script>
