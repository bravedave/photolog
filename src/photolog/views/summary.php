<?php
/*
 * David Bray
 * BrayWorth Pty Ltd
 * e. david@brayworth.com.au
 *
 * MIT License
 *
*/

$uid = strings::rand(); ?>

<h3 class="d-none d-print-block"><?= $this->title ?></h3>
<table class="table table-sm" id="<?= $uid ?>" data-role="photolog-table">
	<thead class="small">
		<tr>
			<td class="align-bottom text-center" line-number>#</td>
			<td class="d-none d-md-table-cell" data-role="sort-header" data-key="suburb">suburb</td>
			<td data-role="sort-header" data-key="address">address</td>
			<td class="text-center" data-role="sort-header" data-key="entries" data-sorttype="numeric" style="width: 17%;">entries</td>
			<td class="d-none d-sm-table-cell text-center" data-role="sort-header" data-key="files" data-sorttype="numeric" style="width: 17%;">files</td>
			<td class="d-none d-md-table-cell text-center" data-role="sort-header" data-key="size" data-sorttype="numeric" style="width: 17%;">size</td>

		</tr>

	</thead>

	<tbody>
		<?php
			$entries = 0;
			$totFiles = 0;
			$totProcessed = 0;
			$totQueued = 0;
			$totSize = 0;
			foreach( $this->data->dtoSet as $dto) {	?>
			<tr data-id="<?=  $dto->property_id ?>"
				data-address=<?= json_encode( $dto->street_index, JSON_UNESCAPED_SLASHES) ?>
				data-suburb=<?= json_encode( $dto->address_suburb, JSON_UNESCAPED_SLASHES) ?>
				data-entries="<?= $dto->entries ?>"
				data-files="<?= $dto->files->total ?>"
				data-size="<?= $dto->files->dirSize ?>"
				class="<?= (bool)$dto->files->errors ? 'text-danger' : '' ?>"
				>
				<td class="text-center small" line-number>&nbsp;&nbsp;</td>
				<td class="d-none d-md-table-cell"><?=  $dto->address_suburb ?></td>
				<td data-role="address_street">
					<?=  $dto->address_street ?>
					<div class="d-md-none small text-muted font-italic"><?=  $dto->address_suburb ?></div>

				</td>
				<td class="text-center"><?php
					$entries += (int)$dto->entries;
					print $dto->entries;

				?></td>
				<td class="d-none d-sm-table-cell text-center"><?php

					$totFiles += $dto->files->total;
					$totProcessed += $dto->files->processed;
					$totQueued += $dto->files->queued;
					print $dto->files->total;
					if ( $dto->files->queued > 0) {
						printf( '<sup title="processed/unprocessed">(%d/%d)</sup>', $dto->files->processed, $dto->files->queued );

					}

				?></td>
				<td class="d-none d-md-table-cell text-center"><?php

					$totSize += $dto->files->dirSize;
					if ( $dto->files->dirSize > 1024000) {
						printf( '%dG', $dto->files->dirSize / 1024000 );

					}
					elseif ( $dto->files->dirSize > 1024) {
						printf( '%dM', $dto->files->dirSize / 1024 );

					}
					else {
						printf( '%dk', $dto->files->dirSize );

					}

				?></td>

			</tr>

		<?php	}	// foreach( $this->data->dto as $dto) {	?>

	</tbody>

	<?php if ( $this->data->dtoSet) {	?>

		<tfoot>
			<tr>
				<td class="d-none d-md-table-cell">&nbsp;</td>
				<td colspan="2">&nbsp;</td>
				<td class="text-center"><?= number_format( $entries) ?></td>
				<td class="d-none d-sm-table-cell text-center"><?php
					print number_format( $totFiles);
					if ( $totQueued > 0) {
						printf( '<sup title="processed/unprocessed">(%d/%d)</sup>', $totProcessed, $totQueued );

					}

				?></td>

				<td class="d-none d-md-table-cell text-center"><?php
					if ( $totSize > 1024000) {
						printf( '%0.1fG', $totSize / 1024000 );

					}
					elseif ( $totSize > 1024) {
						printf( '%dM', $totSize / 1024 );

					}
					else {
						printf( '%dk', $totSize );

					}

				?></td>
			</tr>

			<?php	if ( $totFiles > 0) {	?>
				<tr>
					<td class="border-bottom-0" colspan="3"><em class="text-muteds small">Average File Size: <?php
						$av = $totSize / $totFiles;
						if ( $av > 1024000) {
							printf( '%dG', $av / 1024000 );

						}
						elseif ( $av > 1024) {
							printf( '%dM', $av / 1024 );

						}
						else {
							printf( '%dk', $av );

						}

					?></em></td>
					<td class="d-none d-sm-table-cell border-bottom-0">&nbsp;</td>
					<td colspan="2" class="d-none d-md-table-cell border-bottom-0">&nbsp;</td>

				</tr>

			<?php	}	// if ( $totFiles > 0) {	?>

		</tfoot>

	<?php	}	?>

</table>

<script>
( _ => $(document).ready( () => {
	$('#<?= $uid ?>').on('update-line-numbers', function( i, tr) {
		let _table = $(this);
		let lines = $('> tbody > tr', this);

		$('> thead > tr > td[line-number]', this).html( lines.length);
		lines.each( ( i, tr) => $('> td[line-number]', tr).html( i+1));

	})
	.trigger('update-line-numbers');

	$('tbody > tr', '#<?= $uid ?>').each( ( i,tr) => {
		let _tr = $(tr);

		_tr
		.addClass( 'pointer')
		.on( 'click', e => window.location.href = _.url( '<?= $this->route ?>/?property=' + _tr.data('id'))).
		on( 'contextmenu', function( e) {
			if ( e.shiftKey)
				return;

			e.stopPropagation();e.preventDefault();

			_brayworth_.hideContexts();

			let _context = _brayworth_.context();

			_context.append( $('<a class="font-weight-bold" />').html( 'Photolog : ' + $('td[data-role="address_street"]', _tr).html()).attr( 'href', _.url( '<?= $this->route ?>/?property=' + _tr.data('id'))));
			_context.append( $('<a />').html( 'Goto : ' + $('td[data-role="address_street"]', _tr).html()).attr( 'href', _.url( 'property/view/' + _tr.data('id'))));

			_context.open( e);

		});

	});

}))( _brayworth_);
</script>
