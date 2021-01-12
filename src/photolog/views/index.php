<?php
/*
 * David Bray
 * BrayWorth Pty Ltd
 * e. david@brayworth.com.au
 *
 * MIT License
 *
*/

$_uidCarousel = false;	?>

<ul class="nav flex-column mt-2">
	<?php
	if ( isset( $this->data->dto) && $this->data->dto) {
		if ( isset( $this->data->referer) && $this->data->referer) {	?>

			<li class="nav-item" id="<?= $uid = strings::rand(); ?>">
				<a href="<?= strings::url( sprintf( '%s/view/%d?f=%d', $this->route, $this->data->dto->id, $this->data->referer->id)); ?>">
					<h6><?= $this->title ?> #<?= $this->data->dto->id ?></h6>

				</a>

			</li>
			<script>
			( _ => $(document).ready( () => {
				_.post({
					url : _.url( '<?= $this->route ?>'),
					data : {
						action : 'get-photolog',
						property : <?= $this->data->referer->id ?>

					}

				}).then( d => {
					if ( 'ack' == d.response) {
						//~ console.table( d.data);
						if ( d.data.length > 0) {
							let ul = $('<ul class="list-unstyled ml-4"></ul>').appendTo('#<?= $uid ?>');
							$.each( d.data, function( i, entry) {
								let li = $('<li></li>').appendTo( ul);
								let a = $('<a />').html( entry.subject + ' (' + entry.files.total + ')').appendTo( li);

								let m = _.dayjs( entry.date);
								li.attr( 'title', m.format( 'l'));

								a.attr( 'href', _.url( '<?= $this->route ?>/view/' + entry.id + '?f=<?= $this->data->referer->id ?>'));

							});

							$('#<?= $uid ?>').removeClass( 'd-none');

						}

					} else { _.growl( d); }

				});

			}))( _brayworth_);
			</script>

			<li class="nav-item"><a class="nav-link" href="#" id="<?= $_uidCarousel = strings::rand()  ?>">carousel</a></li>
			<li class="nav-item"><a class="nav-link" href="<?= strings::url('property/view/' . $this->data->referer->id); ?>"><?= $this->data->referer->address_street ?></a></li>

		<?php
		}
		else {	?>
			<li class="nav-item">
				<a href="<?= strings::url( $this->route . '/view/' . $this->data->dto->id); ?>">
					<h6><?= $this->title ?> #<?= $this->data->dto->id ?></h6>

				</a>

			</li>

			<li class="nav-item">
				<a class="nav-link" href="<?= strings::url( sprintf( '%s/?property=%d', $this->route, $this->data->dto->property_id)); ?>">
					<i class="bi bi-arrow-left-short"></i>
					<?= $this->data->dto->address_street ?>

				</a>

			</li>

			<li class="nav-item"><a class="nav-link" href="#" id="<?= $_uidCarousel = strings::rand() ?>">carousel</a></li>

			<li class="nav-item"><a class="nav-link" href="#" id="<?= $uid = strings::rand() ?>">add entry on <?= $this->data->dto->address_street ?></a></li>
			<script>
			( _ => $(document).ready( () => {
				$('#<?= $uid ?>').on( 'click', e => {
					e.preventDefault();

					_.get.modal( _.url('<?= $this->route ?>/entry?property=<?= (int)$this->data->dto->property_id ?>'))
					.then( d => d.on( 'success', ( e, href) => window.location.href = href));

				});

			}))( _brayworth_);
			</script>

		<?php
		}	// if ( isset( $this->data->referer) && $this->data->referer)	?>

		<li class="nav-item"><a class="nav-link" href="<?= strings::url( $this->route); ?>">list all</a></li>

	<?php
	}
	else {
		if ( isset( $this->data->referer) && $this->data->referer) {	?>

			<li class="nav-item">
				<a href="<?= strings::url( sprintf( '%s/?property=%d', $this->route, $this->data->referer->id)); ?>">
					<h6><?= $this->data->referer->address_street ?></h6>

				</a>

			</li>

			<li class="nav-item"><a class="nav-link" href="<?= strings::url($this->route); ?>">
				<i class="bi bi-arrow-left-short"></i>
				list all

			</a></li>

			<li class="nav-item"><a class="nav-link" href="#" id="<?= $uid = strings::rand() ?>">add entry on <?= $this->data->referer->address_street ?></a></li>
			<script>
			( _ => $(document).ready( () => {
				$('#<?= $uid ?>').on( 'click', e => {
					e.preventDefault();

					_.get.modal( _.url('<?= $this->route ?>/entry?property=<?= (int)$this->data->referer->id ?>'))
					.then( d => d.on( 'success', ( e, href) => window.location.href = href));

				});

			}))( _brayworth_);
			</script>

		<?php
		}
		else {	?>
			<li class="nav-item">
				<a href="<?= strings::url($this->route); ?>">
					<h6><?= $this->title ?></h6>

				</a>

			</li>

		<?php
		}	// if ( isset( $this->data->referer) && $this->data->referer)

	}	// if ( isset( $this->data->dto) && $this->data->dto->id)	?>

	<?php
	if ( isset( $this->data->dto) && $this->data->dto) {	?>

	<li class="nav-item"><a class="nav-link" href="#" id="<?= $uid = strings::rand() ?>">generate public link</a></li>
	<li class="nav-item d-none">
		<div class="row">
			<div class="col">
				<input type="text" class="form-control" readonly id="<?= $uid ?>public" />
					<div class="form-text text-right" id="<?= $uid ?>public_expires"></div>

			</div>

		</div>

		<div class="row">
			<div class="col">
				<div class="btn-group btn-group-sm d-flex" aria-label="Public link toolbar">
					<button class="btn btn-light flex-fill" type="button" title="copy to clipboard" id="<?= $uid ?>copy"><i class="bi bi-clipboard"></i></button>
					<button class="btn btn-light flex-fill" type="button" title="clear link" id="<?= $uid ?>clear"><i class="bi bi-trash"></i></button>
					<button class="btn btn-light flex-fill" type="button" title="email link" id="<?= $uid ?>email"><i class="bi bi-cursor"></i></button>
					<button class="btn btn-light flex-fill" type="button" title="regenerate link" id="<?= $uid ?>regenerate"><i class="bi bi-arrow-repeat"></i></button>
					<button class="btn btn-light flex-fill" type="button" title="view on portal" id="<?= $uid ?>view"><i class="bi bi-box-arrow-up-right"></i></button>

				</div>

			</div>

		</div>

	</li>
	<script>
	( _ => {
		$('#<?= $uid ?>copy').on( 'click', e => {
			let el = $('#<?= $uid ?>public')[0];

			/* Select the text field */
			el.select();
			el.setSelectionRange(0, 99999); /*For mobile devices*/

			document.execCommand("copy");	/* Copy the text inside the text field */

			_.growl('Copied');

		});

		$('#<?= $uid ?>')
		.on( 'refresh', function( e) {
			_.post({
				url : _.url('<?= $this->route ?>'),
				data : {
					action : 'public-link-get',
					id : <?= $this->data->dto->id ?>
				},

			}).then( d => {
				if ( 'ack' == d.response) {
					$('#<?= $uid ?>').closest('.nav-item').addClass('d-none');
					$('#<?= $uid ?>public').closest('.nav-item').removeClass('d-none');

					$('#<?= $uid ?>public').val(d.url);
					$('#<?= $uid ?>public_expires').html('expires : '+_.dayjs(d.expires).format('l'));

				}
				else {
					$('#<?= $uid ?>').closest('.nav-item').removeClass('d-none');
					$('#<?= $uid ?>public').closest('.nav-item').addClass('d-none');

				}

			});

		})
		.on( 'clear-link', function( e) {
			_.post({
				url : _.url('<?= $this->route ?>'),
				data : {
					action : 'public-link-clear',
					id : <?= $this->data->dto->id ?>
				},

			}).then( d => {
				_.growl(d);
				if ( 'ack' == d.response) {
					$('#<?= $uid ?>').trigger( 'refresh');

				}

			});

		})
		.on( 'create-link', function( e) {
			_.get( _.url('<?= $this->route ?>/publicLink/<?= $this->data->dto->id ?>'))
			.then( html => {
				let _html = $(html)
				_html.appendTo( 'body');

				$('.modal', _html).on( 'success', d => $('#<?= $uid ?>').trigger( 'refresh'))

			});

		})
		.on( 'click', function( e) {
			e.stopPropagation();e.preventDefault();

			$(this).trigger( 'create-link');

		})
		.on( 'email-link', function(e) {
			_cms_.email.activate({
				subject : '<?= htmlspecialchars( sprintf('%s - %s', $this->data->dto->address_street, $this->data->dto->subject)) ?>',
				message : '<br /><br />View the images on our portal <a href="' + $('#<?= $uid ?>public').val() + '">here</a><br /><br />' + _cms_.currentUser.signoff

			})

		});

		$('#<?= $uid ?>clear').on( 'click', e => $('#<?= $uid ?>').trigger( 'clear-link'));
		$('#<?= $uid ?>regenerate').on( 'click', e => $('#<?= $uid ?>').trigger( 'create-link'));
		$('#<?= $uid ?>email').on( 'click', e => $('#<?= $uid ?>').trigger( 'email-link'));
		$('#<?= $uid ?>view').on( 'click', e => window.open( $('#<?= $uid ?>public').val()));
		$('#<?= $uid ?>').trigger( 'refresh');

	}) (_brayworth_);
	</script>

	<?php
	}	?>

	<li class="nav-item pt-4"><a class="nav-link btn btn-outline-primary" href="#" id="<?= $_uidAdd = strings::rand() ?>">add entry</a></li>

</ul>
<script>
( _ => {
	$('#<?= $_uidAdd ?>').on( 'click', e => {
		e.preventDefault();

		_.get.modal( _.url('<?= $this->route ?>/entry'))
		.then( d => d.on( 'success', ( e, href) => window.location.href = href));

	});

	<?php	if ( $_uidCarousel) {	?>
		$('#<?= $_uidCarousel ?>').on( 'click', function( e) {
			e.stopPropagation(); e.preventDefault();
			$(document).trigger( 'photolog-carousel');

		});

	<?php	}	?>

})( _brayworth_);
</script>