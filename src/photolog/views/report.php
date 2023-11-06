<?php
/*
 * David Bray
 * BrayWorth Pty Ltd
 * e. david@brayworth.com.au
 *
 * MIT License
 *
*/

/**
 * replace:
 * [x] data-dismiss => data-bs-dismiss
 * [x] data-toggle => data-bs-toggle
 * [x] data-parent => data-bs-parent
 * [x] text-right => text-end
 * [x] mr-* => me-*
 * [x] ml-* => ms-*
 * [x] input-group-prepend - remove
 * [x] input-group-append - remove
 */

extract((array)$this->data);
?>

<h3 class="d-none d-print-block"><?= $this->title ?></h3>
<table class="table table-sm" id="<?= $_table = strings::rand() ?>" data-role="photolog-table">
  <thead class="small">

    <tr>
      <td class="text-center js-line-number">#</td>
      <td data-role="sort-header" data-key="date">date</td>
      <td class="d-none d-md-table-cell">address</td>
      <td data-role="sort-header" data-key="subject">subject</td>
      <td class="text-center" data-role="sort-header" data-key="files"
        data-sorttype="numeric">files</td>
      <td class="text-center" data-role="sort-header" data-key="size"
        data-sorttype="numeric">size</td>
      <td class="d-none d-md-table-cell">updated</td>
    </tr>
  </thead>

  <tbody>
    <?php
    $totFiles = 0;
    $totProcessed = 0;
    $totQueued = 0;
    $totSize = 0;
    array_walk($dtoSet, function ($dto) use (&$totFiles, &$totProcessed, &$totQueued, &$totSize) {

      $totProcessed += $dto->files->processed;
      $totQueued += $dto->files->queued;
      $totFiles += $dto->files->total;
      $totSize += $dto->files->dirSize;

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
        htmlentities($dto->subject),
        $dto->files->total,
        $dto->files->dirSize,
        (bool)$dto->files->errors ? 'text-danger' : ''
      );

      print '<td class="small text-center js-line-number"></td>';
      printf('<td>%s</td>', strings::asShortDate($dto->date));
      printf('<td class="d-none d-md-table-cell" data-address>%s</td>', strings::GoodStreetString($dto->address_street));
      printf(
        '<td>%s<div class="d-md-none text-muted small font-italic">%s</div></td>',
        $dto->subject,
        strings::GoodStreetString($dto->address_street)
      );

      printf(
        '<td class="text-center">%s%s</td>',
        $dto->files->total,
        $dto->files->queued > 0 ?
          sprintf('<sup title="processed/unprocessed">(%d/%d)</sup>', $dto->files->processed, $dto->files->queued) : ''
      );

      if ($dto->files->dirSize > 1024000) {

        printf('<td class="text-center">%dG</td>', $dto->files->dirSize / 1024000);
      } elseif ($dto->files->dirSize > 1024) {

        printf('<td class="text-center">%dM</td>', $dto->files->dirSize / 1024);
      } else {

        printf('<td class="text-center">%dk</td>', $dto->files->dirSize);
      }

      printf('<td class="d-none d-md-table-cell">%s</td>', strings::asShortDate($dto->updated));

      print '</tr>';
    });  ?>
  </tbody>

  <?php if ($dtoSet) {  ?>

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


        <?php
        if ($totSize > 1024000) {
          printf('<td class="text-center">%dG</td>', $totSize / 1024000);
        } elseif ($totSize > 1024) {
          printf('<td class="text-center">%dM</td>', $totSize / 1024);
        } else {
          printf('<td class="text-center">%dk</td>', $totSize);
        }
        ?>

        <td class="d-none d-md-table-cell">&nbsp;</td>
      </tr>
    </tfoot>

  <?php } ?>

</table>
<script>
  (_ => {
    const table = $('#<?= $_table ?>');

    const contextmenu = function(e) {

      if (e.shiftKey) return;
      let _ctx = _.context(e); // hides any open contexts and stops bubbling

      let _tr = $(this);

      _ctx.append.a({
        html: '<strong>view files</strong>',
        click: e => $(this).trigger('view')
      });

      _ctx.append.a({
        html: 'edit',
        click: e => {

          _.get.modal(_.url('<?= $this->route ?>/entry/' + this.dataset.id))
            .then(d => d.on('success', (e, href) => window.location.reload()));
        }
      });

      if (Number(this.dataset.property_id) > 0) {

        _ctx.append.a('Goto ' + _tr.find('td[data-address]').html())
          .attr('href', _.url(`property/view/${this.dataset.property_id}`));
      }

      if (Number(this.dataset.count) < 1) {

        _ctx.append('<hr>');
        _ctx.append.a({

          html: '<i class="bi bi-trash"></i>delete',
          click: e => {

            _.fetch
              .post(_.url('<?= $this->route ?>'), {
                id: this.dataset.id,
                action: 'delete-entry',
              }).then(d => {
                if ('ack' == d.response) {
                  window.location.reload()
                } else {
                  _.growl(d)
                }
              });
          }
        });
      }

      _ctx.append('<hr>');

      _ctx.append.a({
        html: 'refresh file count',
        click: e => {

          _.fetch
            .post(_.url('<?= $this->route ?>'), {
              action: 'touch',
              id: this.dataset.id,
            })
            .then(d => {
              if ('ack' == d.response) {
                window.location.reload();
              } else {
                _.growl(d);
              }
            });
        }
      });

      _ctx.open(e);
    };

    table.find('> tbody > tr').each((i, tr) => {

      let _tr = $(tr);

      _tr
        .addClass('pointer')
        .on('click', function(e) {

          e.stopPropagation();
          e.preventDefault();

          _.hideContexts();

          $(this).trigger('view');
        })
        .on('contextmenu', contextmenu)
        .on('view', function(e) {

          let _tr = $(this);

          <?php if (isset($dto->id) && (int)$dto->id) {  ?>

            window.location.href = _.url(
              `<?= $this->route ?>/view/${this.dataset.id}?x=1&f=<?= $dto->id ?>`);
          <?php } else {  ?>

            window.location.href = _.url(`<?= $this->route ?>/view/${this.dataset.id}`);
          <?php }  ?>
        });

      _.longTouchDetector(_tr, contextmenu);
    })

    table
      .on('update-line-numbers', _.table._line_numbers_)
      .trigger('update-line-numbers');
  })(_brayworth_);
</script>