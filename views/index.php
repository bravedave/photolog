<?php
/*
 * David Bray
 * BrayWorth Pty Ltd
 * e. david@brayworth.com.au
 *
 * MIT License
 *
*/	?>

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
			$(document).ready( () => {
				_cms_.post({
					url : _cms_.url( '<?= $this->route ?>'),
					data : {
						action : 'get-photolog',
						property : <?= $this->data->referer->id ?>

					}

				}).then( function( d) {
					if ( 'ack' == d.response) {
						//~ console.table( d.data);
						if ( d.data.length > 0) {
							let ul = $('<ul class="list-unstyled ml-4"></ul>').appendTo('#<?= $uid ?>');
							$.each( d.data, function( i, entry) {
								let li = $('<li></li>').appendTo( ul);
								let a = $('<a />').html( entry.subject + ' (' + entry.files.total + ')').appendTo( li);

								let m = _cms_.moment( entry.date);
								li.attr( 'title', m.format( 'l'));

								a.attr( 'href', _cms_.url( '<?= $this->route ?>/view/' + entry.id + '?f=<?= $this->data->referer->id ?>'));

							});

							$('#<?= $uid ?>').removeClass( 'd-none');

						}

					}
					else {
						_cms_.growl( d);

					}

				});

			});
			</script>

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
					<i class="fa fa-level-up"></i>
					<?= $this->data->dto->address_street ?>

				</a>

			</li>

			<li class="nav-item"><a class="nav-link" href="#" carousel data-title=<?= json_encode( $this->data->dto->address_street, JSON_UNESCAPED_SLASHES) ?>>carousel</a></li>

			<li class="nav-item"><a class="nav-link" href="#" id="<?= $uid = strings::rand() ?>">add entry on <?= $this->data->dto->address_street ?></a></li>
			<script>
			$(document).ready( () => {
				$('#<?= $uid ?>').on( 'click', e => {
					e.preventDefault();

					( _ => {
						_.loadModal({
							url : _.url('<?= $this->route ?>/entry?property=<?= (int)$this->data->dto->property_id ?>'),
							onSuccess : ( e, href) => window.location.href = href,

						});

					}) (_brayworth_);

				});

			});
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
				<i class="fa fa-level-up"></i>
				list all

			</a></li>

			<li class="nav-item"><a class="nav-link" href="#" id="<?= $uid = strings::rand() ?>">add entry on <?= $this->data->referer->address_street ?></a></li>
			<script>
			$(document).ready( () => {
				$('#<?= $uid ?>').on( 'click', e => {
					e.preventDefault();

					( _ => {
						_.loadModal({
							url : _.url('<?= $this->route ?>/entry?property=<?= (int)$this->data->referer->id ?>'),
							onSuccess : ( e, href) => window.location.href = href,

						});

					}) (_brayworth_);

				});

			});
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
					<button class="btn btn-light flex-fill" type="button" title="copy to clipboard" id="<?= $uid ?>copy"><i class="fa fa-clipboard"></i></button>
					<button class="btn btn-light flex-fill" type="button" title="clear link" id="<?= $uid ?>clear"><i class="fa fa-trash"></i></button>
					<button class="btn btn-light flex-fill" type="button" title="email link" id="<?= $uid ?>email"><i class="fa fa-send-o"></i></button>
					<button class="btn btn-light flex-fill" type="button" title="regenerate link" id="<?= $uid ?>regenerate"><i class="fa fa-recycle"></i></button>
					<button class="btn btn-light flex-fill" type="button" title="view on portal" id="<?= $uid ?>view"><i class="fa fa-external-link"></i></button>

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
					$('#<?= $uid ?>public_expires').html('expires : '+_.moment(d.expires).format('l'));

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


	<li class="nav-item pt-4"><a class="nav-link btn btn-outline-primary" href="#" id="<?= $uid = strings::rand() ?>">add entry</a></li>
	<script>
	$(document).ready( () => {
		$('#<?= $uid ?>').on( 'click', e => {
			e.preventDefault();

			( _ => {
				_.loadModal({
					url : _.url('<?= $this->route ?>/entry'),
					onSuccess : ( e, href) => window.location.href = href,

				});

			}) (_brayworth_);

		});

	});
	</script>

</ul>
<script>
$(document).ready( function() {
	$('a[carousel]').on( 'click', function( e) {
		e.stopPropagation(); e.preventDefault();

		let _me = $(this);
		let imgs = $('img.card-img-top');

		if ( imgs.length > 0) {
			let id = 'carousel_' + String( Math.ceil( Math.random() * 10000));
			let ctrl = $('<div class="carousel slide" data-interval="5000"></div>').attr( 'id', id);
			let indicators = $('<ol class="carousel-indicators"></ol>').appendTo( ctrl);
			let inner = $('<div class="carousel-inner"></div>').appendTo( ctrl);

			ctrl.append( '<a class="carousel-control-prev" href="#' + id + '" role="button" data-slide="prev"><span class="carousel-control-prev-icon" aria-hidden="true"></span><span class="sr-only">Previous</span></a>');
			ctrl.append( '<a class="carousel-control-next" href="#' + id + '" role="button" data-slide="next"><span class="carousel-control-next-icon" aria-hidden="true"></span><span class="sr-only">Next</span></a>');

			let first = true;
			imgs.each( function( i, el) {
				let img = $(el);

				let _indicator = $('<li data-target="#' + id + '" data-slide-to="' + i + '"></li>').appendTo( indicators);
				let envelope = $( '<div class="carousel-item"></div>').appendTo( inner);
				$( '<img class="d-block w-100" alt="..." />').attr( 'src', img.attr( 'src')).appendTo( envelope);
				if ( first) {
					_indicator.addClass('active');
					envelope.addClass('active');

				}
				first = false;

			});

			_cms_.modal({
				beforeOpen : function() {
					let modal = this;
					$( '.modal-dialog', modal).addClass( 'modal-lg');
					$( '.modal-title', modal).html( _me.data( 'title'));
					$( '.modal-body', modal).append( ctrl);
					ctrl.carousel('cycle');

				}

			});

		}

		//~ console.log( imgs);

	});

});
</script>