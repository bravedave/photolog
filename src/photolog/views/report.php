<?php
/*
 * David Bray
 * BrayWorth Pty Ltd
 * e. david@brayworth.com.au
 *
 * MIT License
 *
*/

$uid = strings::rand();	?>

<h3 class="d-none d-print-block"><?= $this->title ?></h3>
<table class="table table-sm" id="<?= $uid ?>" data-role="photolog-table">
	<thead class="small">
		<tr>
			<td class="text-center" line-number>#</td>
			<td data-role="sort-header" data-key="date">date</td>
			<td class="d-none d-md-table-cell">address</td>
			<td data-role="sort-header" data-key="subject">subject</td>
			<td class="text-center" data-role="sort-header" data-key="files" data-sorttype="numeric">files</td>
			<td class="text-center" data-role="sort-header" data-key="size" data-sorttype="numeric">size</td>
			<td class="d-none d-md-table-cell">updated</td>

		</tr>

	</thead>

	<tbody>
		<?php
		$totFiles = 0;
		$totProcessed = 0;
		$totQueued = 0;
		$totSize = 0;
		foreach ($this->data->dtoSet as $dto) {

			printf(
				'<tr
					data-id="%s"
					data-property_id="%s"
					data-count="%s"
					data-date="%s"
					data-subject=%s
					data-files="%s"
					data-size="%s"
					class="%s">',
				$dto->id,
				(int)$dto->property_id,
				(int)$dto->files->total,
				$dto->date,
				json_encode($dto->subject, JSON_UNESCAPED_SLASHES),
				$dto->files->total,
				$dto->files->dirSize,
				(bool)$dto->files->errors ? 'text-danger' : ''
			);	?>

			<td class="small text-center" line-number></td>
			<td><?= strings::asShortDate($dto->date) ?></td>
			<td class="d-none d-md-table-cell" data-address><?= strings::GoodStreetString($dto->address_street) ?></td>
			<td>
				<?= $dto->subject ?>
				<div class="d-md-none text-muted small font-italic"><?= strings::GoodStreetString($dto->address_street) ?></div>
			</td>
			<td class="text-center">
				<?php
				$totProcessed += $dto->files->processed;
				$totQueued += $dto->files->queued;
				$totFiles += $dto->files->total;
				print $dto->files->total;
				if ($dto->files->queued > 0) {
					printf('<sup title="processed/unprocessed">(%d/%d)</sup>', $dto->files->processed, $dto->files->queued);
				}
				?></td>

			<td class="text-center">
				<?php
				$totSize += $dto->files->dirSize;
				if ($dto->files->dirSize > 1024000) {
					printf('%dG', $dto->files->dirSize / 1024000);
				} elseif ($dto->files->dirSize > 1024) {
					printf('%dM', $dto->files->dirSize / 1024);
				} else {
					printf('%dk', $dto->files->dirSize);
				}
				?></td>

			<td class="d-none d-md-table-cell"><?= strings::asShortDate($dto->updated) ?></td>

		<?php
			print '</tr>';
		}	?>

	</tbody>

	<?php
	if ($this->data->dtoSet) {	?>

		<tfoot>
			<tr>
				<td class="d-none d-md-table-cell">&nbsp;</td>
				<td colspan="3">&nbsp;</td>
				<td class="text-center">
					<?php
					print number_format($totFiles);
					if ($totQueued > 0) {
						printf('<sup title="processed/unprocessed">(%d/%d)</sup>', $totProcessed, $totQueued);
					}
					?></td>

				<td class="text-center">
					<?php
					if ($totSize > 1024000) {
						printf('%dG', $totSize / 1024000);
					} elseif ($totSize > 1024) {
						printf('%dM', $totSize / 1024);
					} else {
						printf('%dk', $totSize);
					}
					?></td>

				<td class="d-none d-md-table-cell">&nbsp;</td>
			</tr>

		</tfoot>

	<?php
	}
	?>
</table>
<script>
	(_ => {
		$('#<?= $uid ?>')
			.on('update-line-numbers', function(e) {
				let t = 0;
				this.querySelectorAll('tbody > tr:not(.d-none) >td[line-number]').forEach(td => {
					t++;
					td.setAttribute('data-line', t);
					td.textContent = t;
				});

				let td = this.querySelector('thead > tr:not(.d-none) >td[line-number]');
				if (!!td) {
					td.setAttribute('data-count', t);
					td.textContent = t;
				}
			})
			.trigger('update-line-numbers');

		_('#<?= $uid ?> > tbody > tr').forEach(tr => {
			$(tr)
				.addClass('pointer')
				.on('click', function(e) {
					e.stopPropagation();
					e.preventDefault();

					$(this).trigger('view');

				})
				.on('contextmenu', function(e) {
					if (e.shiftKey)
						return;

					e.stopPropagation();
					e.preventDefault();

					_.hideContexts();

					let _tr = $(this);
					let _data = _tr.data();
					let _context = _.context();

					_context.append(
						$('<a href="#"><strong>view files</strong></a>')
						.on('click', e => {
							e.stopPropagation();
							e.preventDefault();

							_context.close();
							$(this).trigger('view');

						}));

					_context.append(
						$('<a href="#">edit</a>')
						.on('click', e => {
							e.stopPropagation();
							e.preventDefault();

							_context.close();

							_.get.modal(_.url('<?= $this->route ?>/entry/' + _data.id))
								.then(d => d.on('success', (e, href) => window.location.reload()));

						}));

					let pid = _data.property_id;
					if (pid > 0) {
						_context.append(
							$('<a>Goto ' + $('td[data-address]', _tr).html() + '</a>')
							.attr('href', _.url('property/view/' + pid))
						);

					}

					if (_tr.data('count') < 1) {
						_context.append('<hr>');
						_context.append(
							$('<a href="#"><i class="bi bi-trash"></i>delete</a>')
							.on('click', e => {
								e.stopPropagation();
								e.preventDefault();

								_context.close();

								_.post({
									url: _.url('<?= $this->route ?>'),
									data: {
										id: _data.id,
										action: 'delete-entry',

									}

								}).then(d => {
									if ('ack' == d.response) {
										window.location.reload();
									} else {
										_.growl(d);
									}
								});
							}));

					}

					if (_context.length > 0) _context.open(e);

				})
				.on('view', function(e) {
					let _tr = $(this);
					let _data = _tr.data();

					<?php if (isset($this->data->dto->id) && (int)$this->data->dto->id) {	?>
						window.location.href = _.url('<?= $this->route ?>/view/' + _data.id + '?f=<?= $this->data->dto->id ?>');

					<?php } else {	?>
						window.location.href = _.url('<?= $this->route ?>/view/' + _data.id);

					<?php }	?>

				});

		});

	})(_brayworth_);
</script>