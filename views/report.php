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
<h3 class="d-none d-print-block"><?= $this->title ?></h3>
<table class="table table-sm" id="<?= $uid ?>" data-role="photolog-table">
	<thead class="small">
		<tr>
			<td role="sort-header" data-key="date">date</td>
			<td>address</td>
			<td role="sort-header" data-key="subject">subject</td>
			<td role="sort-header" data-key="files" data-sorttype="numeric">files</td>
			<td role="sort-header" data-key="size" data-sorttype="numeric">size</td>
			<td>updated</td>

		</tr>


	</thead>

	<tbody>
	<?php
		$totFiles = 0;
		$totProcessed = 0;
		$totQueued = 0;
		$totSize = 0;
		foreach ( $this->data->dtoSet as $dto) {	?>
		<tr data-id="<?= $dto->id ?>"
			data-property_id="<?= (int)$dto->property_id ?>"
			data-count="<?= (int)$dto->files->total ?>"
			data-date="<?= $dto->date ?>"
			data-subject=<?= json_encode( $dto->subject, JSON_UNESCAPED_SLASHES) ?>
			data-files="<?= $dto->files->total ?>"
			data-size="<?= $dto->files->dirSize ?>"
			class="<?= (bool)$dto->files->errors ? 'text-danger' : '' ?>"
			>
			<td><?= strings::asShortDate( $dto->date) ?></td>
			<td data-address><?= PropertyUtility::GoodStreetString( $dto->address_street) ?></td>
			<td><?= $dto->subject ?></td>
			<td><?php

				$totProcessed += $dto->files->processed;
				$totQueued += $dto->files->queued;
				$totFiles += $dto->files->total;
				print $dto->files->total;
				if ( $dto->files->queued > 0) {
					printf( '<sup title="processed/unprocessed">(%d/%d)</sup>', $dto->files->processed, $dto->files->queued );

				}

			?></td>
			<td><?php

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
			<td><?= strings::asShortDate( $dto->updated) ?></td>

		</tr>

	<?php
		}	// while ( $dto = $this->data->res->dto())		?>

	</tbody>

	<?php
		if ( $this->data->dtoSet) {	?>

	<tfoot>
		<tr>
			<td colspan="3">&nbsp;</td>
			<td><?php
				print number_format( $totFiles);
				if ( $totQueued > 0) {
					printf( '<sup title="processed/unprocessed">(%d/%d)</sup>', $totProcessed, $totQueued );

				}

			?></td>
			<td><?php
				if ( $totSize > 1024000) {
					printf( '%dG', $totSize / 1024000 );

				}
				elseif ( $totSize > 1024) {
					printf( '%dM', $totSize / 1024 );

				}
				else {
					printf( '%dk', $totSize );

				}

			?></td>
			<td>&nbsp;</td>
		</tr>

	</tfoot>

	<?php
		}
		?>
</table>
<script>
$(document).ready( function() {
	$('tbody > tr', '#<?= $uid ?>').each( function( i, tr) {
		let _tr = $(tr);

		_tr.addClass( 'pointer').on( 'click', function( e) {
		<?php if ( isset( $this->data->dto->id) && (int)$this->data->dto->id) {	?>
			window.location.href = _cms_.url( 'property_photolog/view/' + _tr.data('id') + '?f=<?= $this->data->dto->id ?>');

		<?php }
			else {	?>
			window.location.href = _cms_.url( 'property_photolog/view/' + _tr.data('id'));

		<?php }	// if ( isset( $this->data->dto->id) && (int)$this->data->dto->id)	?>

		});

		_tr.on( 'contextmenu', function( e) {
			if ( e.shiftKey)
				return;

			e.stopPropagation();e.preventDefault();

			_brayworth_.hideContexts();

			let _context = _brayworth_.context();

			(function() {
		<?php if ( isset( $this->data->dto->id) && (int)$this->data->dto->id) {	?>
				let href = _cms_.url( 'property_photolog/view/' + _tr.data('id') + '?f=<?= $this->data->dto->id ?>');

		<?php }
			else {	?>
				let href = _cms_.url( 'property_photolog/view/' + _tr.data('id'));

		<?php }	// if ( isset( $this->data->dto->id) && (int)$this->data->dto->id)	?>

				_context.append( $('<a><strong>view files</strong></a>').attr( 'href', href));

			})();

			_context.append( $('<a href="#">edit</a>').on( 'click', function( e) {
				e.stopPropagation(); e.preventDefault();

				_context.close();

					//~ headerClass : '',
					//~ beforeOpen : function() {},
					//~ onClose : function() {},
				_brayworth_.loadModal({
					url : _cms_.url('property_photolog/entry/' + _tr.data('id')),
					onSuccess : function() {
						window.location.reload();

					},

				});

			}));

			let pid = _tr.data('property_id');
			if ( pid > 0) {
				_context.append( $('<a>Goto ' + $('td[data-address]', _tr).html() + '</a>').attr( 'href', _cms_.url('property/view/' + pid)));

			}

			if ( _tr.data('count') < 1) {
				_context.append( '<hr />');
				_context.append( $('<a href="#"><i class="fa fa-trash"></i>delete</a>').on( 'click', function( e) {
					e.stopPropagation(); e.preventDefault();

					_context.close();

					_cms_.post({
						url : _cms_.url('property_photolog'),
						data : {
							id : _tr.data('id'),
							action : 'delete-entry',

						}

					}).then( function( d) {
						if ( 'ack' == d.response) {
							window.location.reload();

						}
						else {
							_cms_.growl( d);

						}

					});

				}));

			}

			if ( _context.length > 0 ) _context.open( e);

		});

	});

});
</script>