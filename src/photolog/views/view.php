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

use currentUser;
use strings;
use sys;

$uid = strings::rand();
$dto = $this->data->dto;

$diskSpace = sys::diskspace();	?>
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

<div id="<?= $uid; ?>row" class="row g-2"></div>

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
			return new Promise((resolve, reject) => {
				let resolved = false;
				let m = _.ask({
					headClass: 'text-white bg-danger',
					text: 'Are you sure ?',
					title: 'Confirm Delete',
					buttons: {
						yes: function(e) {
							resolved = true;
							resolve();
							$(this).modal('hide');
						}
					}
				});

				m.on('hidden.bs.modal', e => {
					if (!resolved) reject('confirmDeleteAction - reject');
				});

			}).catch(msg => console.warn(msg));
		};

		let deleting = 0;

		let displayCard = file => {
			let card = $('<div class="card photolog-card"></div>')
				.data('file', file);

			allCards.push(card);

			card
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
					if (/mp4|mov/.test(String(_data.file.extension))) {
						let options = {
							size: 'lg',
							title: ('mov' == _data.file.extension ? 'quicktime' : 'mp4') + ' Viewer',
							text: '',
							headClass: '',
							url: _data.file.url
						};

						let m = _.ask(options);

						let id = _.randomString();

						let video = $(`<video controls autoplay id="${id}" width="100%">
							<source src="${_data.file.url + '&v=full'}" type="video/${ 'mov' == _data.file.extension ? 'quicktime' : 'mp4' }">
							Sorry, your browser doesn't support embedded videos.
						</video>`);

						$('.modal-body', m)
							.addClass('p-2')
							.append(video);

						// 			.append(`<style>
						// @media (min-width: 768px) {
						//   #${id} { min-height: calc(100vh - 230px) !important; }
						// }
						// </style>`)

						console.log(_data.file);
					} else {
						$(document).trigger('photolog-carousel', _data.file.description);
					}
				})
				.on('delete', function(e) {
					let _me = $(this);
					confirmDeleteAction().then(() => _me.trigger('delete-confirmed'));

				})
				.on('delete-confirmed', function(e) {
					let _me = $(this);
					let _data = _me.data();
					let file = _data.file;

					deleting++;
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

							deleting--;
							if ('ack' == d.response) _me.parent().remove();

							if (0 == deleting) {
								allDeleteVisibility();
								allDownloadVisibility();

							}
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

					// console.table(_data.file);

					if (_data.file.prestamp) {
						_context.append(
							$('<a href="#" title="open in new tab"><i class="bi bi-arrow-counterclockwise"></i>Rotate Left</a>')
							.on('click', e => {
								e.stopPropagation();
								e.preventDefault();

								_context.close();
								_me.trigger('rotate-left');

							}));

						_context.append(
							$('<a href="#" title="open in new tab"><i class="bi bi-arrow-clockwise"></i>Rotate Right</a>')
							.on('click', e => {
								e.stopPropagation();
								e.preventDefault();

								_context.close();
								_me.trigger('rotate-right');

							}));

						_context.append(
							$('<a href="#" title="open in new tab"><i class="bi bi-emoji-smile-upside-down"></i>Rotate 180</a>')
							.on('click', e => {
								e.stopPropagation();
								e.preventDefault();

								_context.close();
								_me.trigger('rotate-180');

							}));

					}

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

				})
				.on('refresh', function(e) {
					let _me = $(this);
					let file = _me.data('file');

					let img = $('<img class="card-img-top pointer" logimage>');

					img
						.attr('title', file.description)
						.attr('src', file.url)
						.appendTo(this);

					let body = $('<div class="card-body px-2 py-1"></div>')
						.appendTo(this);

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
				})
				.on('rotate-180', function(e) {
					let _me = $(this);
					let file = _me.data('file');

					_.post({
						url: _.url('<?= $this->route ?>'),
						data: {
							action: 'rotate-180',
							id: <?= $dto->id ?>,
							file: file.description,

						},

					}).then(d => {
						// console.log(d);
						if ('ack' == d.response) {
							$('img[logimage]', _me).attr('src', d.data.url);
							_me.data('file', d.data);
						} else {
							_.growl(d);

						}

					});

				})
				.on('rotate-left', function(e) {
					let _me = $(this);
					let file = _me.data('file');

					_.post({
						url: _.url('<?= $this->route ?>'),
						data: {
							action: 'rotate-left',
							id: <?= $dto->id ?>,
							file: file.description,

						},

					}).then(d => {
						// console.log(d);
						if ('ack' == d.response) {
							$('img[logimage]', _me).attr('src', d.data.url);
							_me.data('file', d.data);
						} else {
							_.growl(d);

						}

					});

				})
				.on('rotate-right', function(e) {
					let _me = $(this);
					let file = _me.data('file');

					_.post({
						url: _.url('<?= $this->route ?>'),
						data: {
							action: 'rotate-right',
							id: <?= $dto->id ?>,
							file: file.description,

						},

					}).then(d => {
						// console.log(d);
						if ('ack' == d.response) {
							$('img[logimage]', _me).attr('src', d.data.url);
							_me.data('file', d.data);
						} else {
							_.growl(d);

						}

					});

				});

			$('<div class="col-md-4 col-lg-3 col-xl-2 mb-2"></div>')
				.append(card)
				.appendTo('#<?= $uid ?>row');

			card.trigger('refresh');

		};

		/*--- ---[]--- ---*/
		//~ console.log( '<?= $uid ?>fileupload');
		/*--- ---[]--- ---*/

		let cContainer = $('<div class="col-md-8 col-lg-3 col-xl-4 mb-2 d-print-none"></div>').appendTo('#<?= $uid ?>row');
		<?php if ($diskSpace->exceeded) {	?>
			cContainer.append('<div class="alert alert-warning"><h5 class="alert-heading">disk space low</h5>uploaded disabled</div>');
		<?php	} else {	?>
			let c = _.fileDragDropContainer({
				fileControl: true
			}).appendTo(cContainer);
		<?php	}	?>

		let allDownload = $('<a title="download zip" class="btn btn-light btn-sm d-none"><i class="bi bi-download" title="download as zip file"></i> zip</a>').attr('href', _.url('<?= $this->route ?>/zip/<?= $dto->id ?>'));

		let allDelete = $('<button title="delete all" class="btn btn-light btn-sm d-none"><i class="bi bi-trash"></i> delete all</button>');
		let btnNotepad = $('<button title="notepad" class="btn btn-light btn-sm"><i class="bi bi-pencil"></i> note</button>');

		let bCol = $('<div class="col text-center"></div>')

		$('<div class="row g-2"></div>')
			.append(bCol)
			.appendTo(cContainer);

		$('<div class="btn-group"></div>')
			.append(allDownload)
			.append(allDelete)
			.append(btnNotepad)
			.appendTo(bCol);

		allDelete.on('click', e => {
			e.stopPropagation();

			confirmDeleteAction()
				.then(() => $('.photolog-card').each((i, el) => $(el).trigger('delete-confirmed')));

		});

		const allDownloadVisibility = () => {
			$('img[logimage]').length > 0 ?
				allDownload.removeClass('d-none') :
				allDownload.addClass('d-none');

		};

		const allDeleteVisibility = () => {
			<?php if (currentUser::isadmin()) {	?>
				$('.photolog-card').length > 0 ? allDelete.removeClass('d-none') : allDelete.addClass('d-none');
			<?php }	?>
		};

		let notepad = {
			col: $('<div class="col-md-4 col-lg-3 col-xl-8 mb-2 d-none"></div>').appendTo('#<?= $uid ?>row'),
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

			_.get.modal(_.url('<?= $this->route ?>/notepad/<?= $dto->id ?>'))
				.then(m => m.on('success', (e, d) => notepad.val(d.data.notes)));

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
					<?php
					if (config::$PHOTOLOG_ENABLE_VIDEO) print ",'video/quicktime','video/mp4'";
					if (config::$PHOTOLOG_ENABLE_HEIC) print ",'image/heic'";
					?>
				],
				onError: d => console.log('error', d),
				onReject: d => {
					$(`<div class="alert alert-danger">
							<h5 class="alert-heading">${d.file.name}</h5>
							${d.description}
						</div>`)
						.appendTo(cContainer);

				},
				onUpload: d => {

					if ('ack' == d.response) {

						$.each(d.files, (i, file) => displayCard(file));
						allDeleteVisibility();
						allDownloadVisibility();
					}

				}

			});
		<?php	}	?>

			(cards => {
				$.each(cards, (i, file) => displayCard(file))
			})(<?= json_encode($this->data->files) ?>);

		allDeleteVisibility();
		allDownloadVisibility();

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
				let id = 'carousel_' + _.randomString();
				let ctrl = $(`<div class="carousel slide" data-interval="5000" id=${id}></div>`);
				let indicators = $('<ol class="carousel-indicators"></ol>').appendTo(ctrl);
				let inner = $('<div class="carousel-inner"></div>').appendTo(ctrl);

				ctrl
					.append(`<a class="carousel-control-prev" href="#${id}" role="button" data-slide="prev"><span class="carousel-control-prev-icon" aria-hidden="true"></span><span class="sr-only">Previous</span></a>`)
					.append(`<a class="carousel-control-next" href="#${id}" role="button" data-slide="next"><span class="carousel-control-next-icon" aria-hidden="true"></span><span class="sr-only">Next</span></a>`);

				// console.log( file);
				let first = true;
				imgs.each((i, el) => {
					let img = $(el);
					let src = img.attr('src');

					let _indicator = $(`<li data-target="#${id}" data-slide-to="${i}"></li>`)
						.appendTo(indicators);
					let envelope = $(`<div class="carousel-item">
							<img class="d-block w-100" src="${src}" alt="...">
						</div>`)
						.appendTo(inner);

					let title = String(img.attr('title'));
					if ('' != title) {

						envelope.append(`<div class="carousel-caption d-none d-md-block"><h5>${title}</h5></div>`);
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