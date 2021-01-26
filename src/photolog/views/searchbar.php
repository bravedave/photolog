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
	?>
<div class="form-group row">
	<div class="col">
		<input type="search" class="form-control" aria-label="search" autofocus id="<?= $uid ?>search" />

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
