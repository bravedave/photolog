<?php
/*
 * David Bray
 * BrayWorth Pty Ltd
 * e. david@brayworth.com.au
 *
 * MIT License
 *
*/

extract((array)$this->data);	// $this->data->dtoSet, $this->data->page, $this->data->pages, $this->data->total
?>

<div class="row g-2">
  <div class="col mb-2">

    <input type="search" class="form-control" accesskey="/" aria-label="search" autofocus
      id="<?= $_search = strings::rand()  ?>">
  </div>

  <div class="col-auto mb-2">
    <button type="button" class="btn btn-light" id="<?= $_bottom = strings::rand() ?>"><i
        class="bi bi-chevron-bar-down"></i></button>
  </div>
</div>

<h3 class="d-none d-print-block"><?= $this->title ?></h3>
<table class="table table-sm" id="<?= $_table = strings::rand()  ?>" data-role="photolog-table">
  <thead class="small">

    <tr>

      <td class="align-bottom text-center js-line-number">#</td>
      <td class="d-none d-md-table-cell" data-role="sort-header" data-key="suburb">suburb
      </td>
      <td data-role="sort-header" data-key="address">address</td>
      <td class="text-center" data-role="sort-header" data-key="entries"
        data-sorttype="numeric" style="width: 17%;">entries</td>
      <td class="d-none d-sm-table-cell text-center" data-role="sort-header"
        data-key="files" data-sorttype="numeric" style="width: 17%;">files</td>
      <td class="d-none d-md-table-cell text-center" data-role="sort-header"
        data-key="size" data-sorttype="numeric" style="width: 17%;">size</td>
    </tr>
  </thead>

  <tbody>
    <?php
		$entries = 0;
		$totFiles = 0;
		$totProcessed = 0;
		$totQueued = 0;
		$totSize = 0;
		foreach ($dtoSet as $dto) {

			printf(
				'<tr class="%s" data-id="%d" data-address="%s" data-suburb="%s" data-entries="%s" data-files="%s" data-size="%s">',
				(bool)$dto->files->errors ? 'text-danger' : '',
				$dto->property_id,
				htmlentities($dto->street_index),
				htmlentities($dto->address_suburb),
				$dto->entries,
				$dto->files->total,
				$dto->files->dirSize
			);

			print '<td class="text-center small js-line-number"></td>';
			printf('<td class="d-none d-md-table-cell">%s</td>', $dto->address_suburb);
			printf(
				'<td data-role="address_street">%s<div class="d-md-none small text-muted font-italic">%s</div></td>',
				$dto->address_street,
				$dto->address_suburb
			);

			$entries += (int)$dto->entries;
			printf('<td class="text-center">%s</td>', $dto->entries);

			$totFiles += $dto->files->total;
			$totProcessed += $dto->files->processed;
			$totQueued += $dto->files->queued;

			printf(
				'<td class="d-none d-sm-table-cell text-center">%s%s</td>',
				$dto->files->total,
				$dto->files->queued > 0 ? sprintf(
					'<sup title="processed/unprocessed">(%d/%d)</sup>',
					$dto->files->processed,
					$dto->files->queued
				) : ''
			);

			$totSize += $dto->files->dirSize;
			if ($dto->files->dirSize > 1024000) {

				printf('<td class="d-none d-md-table-cell text-center">%dG</td>', $dto->files->dirSize / 1024000);
			} elseif ($dto->files->dirSize > 1024) {

				printf('<td class="d-none d-md-table-cell text-center">%dM</td>', $dto->files->dirSize / 1024);
			} else {

				printf('<td class="d-none d-md-table-cell text-center">%dk</td>', $dto->files->dirSize);
			}

			print "</tr>\n";
		}	// foreach( $this->data->dto as $dto) {

		print "</tbody>\n";

		if ($this->data->dtoSet) {

			print "<tfoot>\n";

			print '<tr><td class="d-none d-md-table-cell">&nbsp;</td>';

			if ($totFiles > 0) {

				$av = $totSize / $totFiles;
				if ($av > 1024000) {
					printf('<td colspan="2" class="text-muted small fst-italic">Average File Size:%dG</td>', $av / 1024000);
				} elseif ($av > 1024) {
					printf('<td colspan="2" class="text-muted small fst-italic">Average File Size:%dM</td>', $av / 1024);
				} else {
					printf('<td colspan="2" class="text-muted small fst-italic">Average File Size:%dk</td>', $av);
				}
			} else {

				print '<td colspan="2" class="text-muted small">Average File Size: N/A</td>';
			}

			printf('<td class="text-center">%s</td>', number_format($entries));
			printf(
				'<td class="d-none d-sm-table-cell text-center">%s%s</td>',
				number_format($totFiles),
				$totQueued > 0 ? sprintf(
					'<sup title="processed/unprocessed">(%d/%d)</sup>',
					$totProcessed,
					$totQueued
				) : ''
			);

			if ($totSize > 1024000) {
				printf('<td class="d-none d-md-table-cell text-center">%0.1fG</td>', $totSize / 1024000);
			} elseif ($totSize > 1024) {
				printf('<td class="d-none d-md-table-cell text-center">%dM</td>', $totSize / 1024);
			} else {
				printf('<td class="d-none d-md-table-cell text-center">%dk</td>', $totSize);
			}
			print "</tr>\n";

			print "</tfoot>\n";
		}	?>

</table>

<script>
  (_ => {
    const bottom = $('#<?= $_bottom ?>');
    const search = $('#<?= $_search ?>');
    const table = $('#<?= $_table ?>');

    const contextmenu = function(e) {

      if (e.shiftKey) return;
      let _context = _.context(e);
      let _tr = $(this);
      let _data = _tr.data();
      let street = _tr.find('td[data-role="address_street"]').html();

      _context.append.a({
        html: `<strong>Photolog : ${street}</strong>`,
        href: _.url('<?= $this->route ?>/?property=' + _data.id)
      });

      _context.append.a({
        html: `Goto : ${street}`,
        href: _.url('property/view/' + _data.id),
        target: '_blank'
      });

      _context.open(e);
    };

    table.find('> tbody > tr').each((i, tr) => {
      let _tr = $(tr);

      _tr
        .addClass('pointer user-select-none')
        .on('click', function(e) {

          if (!!this.dataset.pressLong) {

            delete this.dataset.pressLong;
            contextmenu.call(this, e);
          } else {

            let _tr = $(this);
            let _data = _tr.data();
            window.location.href = _.url('<?= $this->route ?>/?property=' + _data.id);
          }
        })
        .on('contextmenu', contextmenu);

      _.longTouchDetector(_tr, contextmenu);
    });

    _.table.search(search, table);

    // implies there is a cell with class js-line-number
    table
      .on('update-line-numbers', _.table._line_numbers_)
      .trigger('update-line-numbers');

    bottom.on('click', function(e) {
      e.stopPropagation();

      table.find('>tfoot')[0].scrollIntoView({
        behavior: 'smooth',
        block: 'end',
        inline: 'center'
      });

    });

  })(_brayworth_);
</script>