<?php
/*
 * David Bray
 * BrayWorth Pty Ltd
 * e. david@brayworth.com.au
 *
 * MIT License
 *
*/

namespace photolog;

use strings;
use sys;

$uid = strings::rand();
$dto = $this->data->dto;

$diskSpace = sys::diskspace();

?>
<style>
	#<?= $uid; ?>row .has-advanced-upload {
		padding: 40% .5rem .5rem .5rem;
		margin-bottom: 1rem;

	}
</style>

<div class="row">
	<div class="col"><?= $dto->address_street ?></div>
	<div class="col"><?= $dto->subject ?></div>
	<div class="col">date : <?= strings::asShortDate($dto->date) ?></div>
	<div class="col-auto"><button type="button" class="btn btn-sm pt-0" id="<?= $_btnEditHeader = strings::rand() ?>"><i class="bi bi-pencil"></i></button></div>

</div>

<div id="<?= $uid; ?>row" class="row"></div>

<template id="<?= $uid ?>fileupload">
	<div class="input-group mb-3">
		<div class="input-group-prepend">
			<span class="input-group-text" id="<?= $uid ?>FileAddon01">Upload</span>

		</div>

		<div class="custom-file">
			<input type="file" class="custom-file-input" id="<?= $uid ?>File01" aria-describedby="<?= $uid ?>FileAddon01" multiple>
			<label class="custom-file-label" for="<?= $uid ?>File01">Choose file</label>

		</div>

	</div>

</template>

<script>
	(_ => $(document).ready(() => {
		let smokeAlarms = [];
		let allCards = [];

		let confirmDeleteAction = () => {
			return new Promise(resolve => {
				_.ask({
					headClass: 'text-white bg-danger',
					text: 'Are you sure ?',
					title: 'Confirm Delete',
					buttons: {
						yes: function(e) {
							$(this).modal('hide');
							resolve();

						}

					}

				});

			});

		};

		let displayCard = (file) => {
			let card = $('<div class="card"></div>');

			allCards.push(card);

			let img = $('<img class="card-img-top pointer" data-logimage />');

			img
				.attr('title', file.description)
				.attr('src', file.url)
				.appendTo(card);

			let body = $('<div class="card-body px-2 py-1"></div>').appendTo(card);

			$('<div class="card-title text-truncate"></div>')
				.html(file.description)
				.attr('title', file.description)
				.appendTo(body);

			if (!!file.error) {
				if (10 > Number(file.size)) {
					body.append('<h6 class="text-danger">file size error</h6>');

				} else {
					body.append('<h6 class="text-danger">ERROR</h6>');

				}

			}

			$('<div class="col-sm-4 col-md-3 col-xl-2 mb-1"></div>').append(card).appendTo('#<?= $uid ?>row');

			allDeleteVisibility();
			allDownloadVisibility();

			card
				.data('file', file)
				.on('clear-location', function(e) {

					let _me = $(this);
					let _data = _me.data();

					_.post({
						url: _.url('<?= $this->route ?>'),
						data: {
							action: 'set-alarm-location-clear',
							id: <?= (int)$dto->id ?>,
							file: _data.file.description,

						},

					}).then(d => {
						_.growl(d)

						if ('ack' == d.response) {
							_data.file.location = '';
							_me.data('file', _data.file);

						}

					});

				})
				.on('click', function(e) {
					e.stopPropagation();
					e.preventDefault();

					let _me = $(this);
					let _data = _me.data();
					$(document).trigger('photolog-carousel', _data.file.description);

				})
				.on('delete', function(e) {
					let _me = $(this);
					confirmDeleteAction().then(() => _me.trigger('delete-confirmed'));

				})
				.on('delete-confirmed', function(e) {
					let _me = $(this);
					let _data = _me.data();
					let file = _data.file;

					_.post({
							url: _.url('<?= $this->route ?>'),
							data: {
								action: 'delete',
								id: <?= $dto->id ?>,
								file: file.description,

							}

						})
						.then(d => {
							_.growl(d);

							if ('ack' == d.response) _me.parent().remove();

							allDeleteVisibility();
							allDownloadVisibility();

						});

				})
				.on('set-location', function(e, location) {

					let _me = $(this);
					let _data = _me.data();

					_.post({
						url: _.url('<?= $this->route ?>'),
						data: {
							action: 'set-alarm-location',
							id: <?= (int)$dto->id ?>,
							file: _data.file.description,
							location: location


						},

					}).then(d => {
						_.growl(d)

						if ('ack' == d.response) {
							/**
							 * forum : 6300
							 * allow multiple alamrs to share a location
							 */
							// $.each( allCards, (i, card) => {
							// 	let _me = $(card);
							// 	let _data = _me.data();

							// 	console.log( 'check', _data.file);

							// 	if ( _data.file.location == location) {
							// 		_data.file.location = '';
							// 		_me.data('file', _data.file);

							// 	}

							// });

							_data.file.location = location;
							_me.data('file', _data.file);

						}

					});

				})
				.on('contextmenu', function(e) {
					if (e.shiftKey)
						return;

					e.stopPropagation();
					e.preventDefault();

					_.hideContexts();

					let _me = $(this);
					let _data = _me.data();
					let _context = _.context();

					_context.append(
						$('<a href="#" title="open in new tab"><i class="bi bi-collection-play"></i>Start Carousel</a>')
						.on('click', function(e) {
							e.stopPropagation();
							e.preventDefault();


							$(document).trigger('photolog-carousel', _data.file.description);
							_context.close();

						})

					);

					_context.append(
						$('<a target="_blank" title="open in new tab"><i class="bi bi-box-arrow-up-right"></i>Open in new Window</a>')
						.attr('href', _data.file.url + '&v=full')
						.on('click', e => _context.close())

					);

					if (smokeAlarms.length > 0) {
						// console.log( smokeAlarms);

						_context.append('<hr>');
						$.each(smokeAlarms, (i, alarm) => {
							let ctrl = $('<a href="#"></a>');

							ctrl
								.data('file', _data.file)
								.data('location', alarm.location)
								.html(alarm.location)
								.on('click', function(e) {
									e.stopPropagation();
									e.preventDefault();

									let _me = $(this);
									let _data = _me.data();

									if (_data.file.location == _data.location) {
										card.trigger('clear-location');

									} else {
										card.trigger('set-location', _data.location);

									}

									_context.close();

								});

							if (_data.file.location == alarm.location) ctrl.prepend('<i class="bi bi-check"></i>');

							_context.append(ctrl);

						});


					}

					_context.append('<hr>');
					_context.append($('<a class="#"><i class="bi bi-trash"></i>delete</a>').on('click', e => {
						e.stopPropagation();
						e.preventDefault();

						_me.trigger('delete');

					}))

					_context.open(e);

				});

		};

		/*--- ---[]--- ---*/
		//~ console.log( '<?= $uid ?>fileupload');
		/*--- ---[]--- ---*/

		let cContainer = $('<div class="col-sm-4 col-md-3 col-xl-4 mb-1 d-print-none"></div>').appendTo('#<?= $uid ?>row');
		<?php if ($diskSpace->exceeded) {	?>
			cContainer.append('<div class="alert alert-warning"><h5 class="alert-heading">disk space low</h5>uploaded disabled</div>');
		<?php	} else {	?>
			let c = _.fileDragDropContainer({
				fileControl: true
			}).appendTo(cContainer);
		<?php	}	?>

		let allDownload = $('<a title="download zip" class="px-2 btn btn-light btn-sm d-none"><i class="bi bi-download" title="download as zip file"></i>Zip</a>').attr('href', _.url('<?= $this->route ?>/zip/<?= $dto->id ?>'));

		let allDelete = $('<button title="delete all" class="px-2 btn btn-light btn-sm d-none"><i class="bi bi-trash"></i> Delete All</button>');
		let btnNotepad = $('<button title="notepad" class="px-2 btn btn-light btn-sm"><i class="bi bi-pencil"></i> note</button>');

		let bRow = $('<div class="row"></div>').appendTo(cContainer);
		let bCol = $('<div class="col text-center"></div>').appendTo(bRow);

		$('<div class="btn-group"></div>')
			.append(allDownload)
			.append(allDelete)
			.append(btnNotepad)
			.appendTo(bCol);

		allDelete.on('click', e => {
			e.stopPropagation();

			confirmDeleteAction()
				.then(() => {
					$('button[data-delete]').each((i, el) => {
						$(el).trigger('delete-confirmed');

					});

				});

		});

		let allDownloadVisibility = () => {
			$('img[data-logimage]').length > 0 ?
				allDownload.removeClass('d-none') :
				allDownload.addClass('d-none');

		};

		let allDeleteVisibility = () => {
			$('button[data-delete]').length > 0 ? allDelete.removeClass('d-none') : allDelete.addClass('d-none');

		};

		let notepad = {
			col: $('<div class="col-sm-8 col-md-9 col-xl-8 mb-1 d-none"></div>').appendTo('#<?= $uid ?>row'),
			text: $('<textarea class="form-control h-100" readonly></textarea>'),
			val: v => {
				let ret = notepad.text.val(v);
				'' == v || null == v ? notepad.col.addClass('d-none') : notepad.col.removeClass('d-none');
				//~ console.log( v, '' == v);

				return ret;

			}

		};

		notepad.text.appendTo(notepad.col);
		notepad.val(<?= json_encode($this->data->dto->notes, JSON_UNESCAPED_SLASHES) ?>);

		btnNotepad.on('click', function(e) {
			e.stopPropagation();
			e.preventDefault();

			_.loadModal({
				url: _.url('<?= $this->route ?>/notepad/<?= $dto->id ?>'),
				onSuccess: function(e, d) {
					if ('ack' == d.response) {
						//~ console.log( d);
						notepad.val(d.data.notes);

					}

				},

			});

		});

		<?php if (!$diskSpace->exceeded) {	?>
			// console.log( 'assign');
			_.fileDragDropHandler.call(c, {
				url: _.url('<?= $this->route ?>'),
				queue: true,
				postData: {
					action: 'upload',
					id: <?= $dto->id ?>
				},
				accept: [
					'application/pdf',
					'image/jpeg',
					'image/pjpeg',
					'image/png',
					'video/quicktime',
					'video/mp4'
					<?= config::photolog_enable_heic ? ',\'image/heic\'' : '' ?>

				],
				onError: d => {
					console.log('error', d);

				},
				onReject: d => {
					// console.log( 'reject', d);
					let alert = $('<div class="alert alert-danger"></div>');

					alert.html(d.description);
					$('<h5 class="alert-heading"></h5>').html(d.file.name).prependTo(alert);
					alert.appendTo(cContainer);
					// console.log( d.file);

				},
				onUpload: d => {
					if ('ack' == d.response) {
						$.each(d.files, (i, file) => displayCard(file));

					}

				}

			});
		<?php	}	?>

			(cards => $.each(cards, (i, file) => displayCard(file)))(<?= json_encode($this->data->files) ?>);

		_.post({
			url: _.url('<?= $this->route ?>'),
			data: {
				action: 'property-smokealarms',
				id: <?= (int)$dto->id ?>

			},

		}).then(d => {
			if ('ack' == d.response) {
				smokeAlarms = d.alarms;

			}

		});

		$(document).on('photolog-carousel', (e, file) => {
			let imgs = $('img.card-img-top');

			if (imgs.length > 0) {
				let id = 'carousel_' + String(Math.ceil(Math.random() * 10000));
				let ctrl = $('<div class="carousel slide" data-interval="5000"></div>').attr('id', id);
				let indicators = $('<ol class="carousel-indicators"></ol>').appendTo(ctrl);
				let inner = $('<div class="carousel-inner"></div>').appendTo(ctrl);

				ctrl.append('<a class="carousel-control-prev" href="#' + id + '" role="button" data-slide="prev"><span class="carousel-control-prev-icon" aria-hidden="true"></span><span class="sr-only">Previous</span></a>');
				ctrl.append('<a class="carousel-control-next" href="#' + id + '" role="button" data-slide="next"><span class="carousel-control-next-icon" aria-hidden="true"></span><span class="sr-only">Next</span></a>');

				// console.log( file);
				let first = true;
				imgs.each((i, el) => {
					let img = $(el);

					let _indicator = $('<li data-target="#' + id + '" data-slide-to="' + i + '"></li>').appendTo(indicators);
					let envelope = $('<div class="carousel-item"></div>').appendTo(inner);
					$('<img class="d-block w-100" alt="..." />').attr('src', img.attr('src')).appendTo(envelope);

					let title = String(img.attr('title'));
					if ('' != title) {
						let caption = $('<div class="carousel-caption d-none d-md-block"></div>').appendTo(envelope);
						$('<h5></h5>').html(title).appendTo(caption);

					}

					if (!!file) {
						if (title == file) {
							_indicator.addClass('active');
							envelope.addClass('active');

						}

					} else if (first) {
						_indicator.addClass('active');
						envelope.addClass('active');

					}
					first = false;

				});

				_.get.modal().then(modal => {
					$('.modal-dialog', modal).addClass('modal-lg');
					$('.modal-title', modal).html(<?= json_encode($this->data->dto->address_street) ?>);
					$('.modal-body', modal).append(ctrl);
					ctrl.carousel('cycle');

				});

			}

		});

		$('#<?= $_btnEditHeader ?>').on('click', function(e) {
			e.stopPropagation();
			e.preventDefault();

			_.get.modal(_.url('<?= $this->route ?>/entry/<?= $dto->id ?>'))
				.then(d => d.on('success', (e, href) => window.location.reload()));

		});

	}))(_brayworth_);
</script>