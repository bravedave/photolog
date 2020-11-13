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
	<div class="col">date : <?= strings::asShortDate( $dto->date) ?></div>

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
$(document).ready( () => { ( _ => {

  let smokeAlarms = [];

	let displayCard = ( file) => {
		let col = $('<div class="col-sm-4 col-md-3 col-xl-2 mb-1"></div>');
		let card = $('<div class="card"></div>').appendTo( col);
		let img = $('<img class="card-img-top" data-logimage />').attr( 'title', file.description).attr( 'src', file.url).appendTo( card);

		//~ console.log( file.url);

		let body = $('<div class="card-body py-1"></div>').appendTo( card);
		let menu = $('<ul class="list-inline"></ul>');
		let openLink = $('<a target="_blank" title="open in new tab" class="px-2 btn btn-light btn-sm"><i class="fa fa-external-link"></i></a>').attr( 'href', file.url + '&v=full');
		//~ let downloadLink = $('<a title="download" class="px-2 btn btn-light btn-sm"><i class="fa fa-download"></i></a>').attr( 'download', file.description).attr( 'href', file.url);
			//~ $('<li class="list-inline-item"></li>').append( downloadLink).appendTo( menu);
		let deleteLink = $('<button data-delete type="button" title="delete" class="px-2 btn btn-light btn-sm"><i class="fa fa-trash"></i></button>').on( 'click', function( e) {
			_.post({
				url : _.url('<?= $this->route ?>'),
				data : {
					action : 'delete',
					id : <?= $dto->id ?>,
					file : file.description,

				}

			})
			.then( function( d) {
				_.growl( d);
				if ( 'ack' == d.response) {
					col.remove();

				}

				allDeleteVisibility();
				allDownloadVisibility();

			});

		});

		$('<li class="list-inline-item"></li>').append( openLink).appendTo( menu);
		$('<li class="list-inline-item"></li>').append( deleteLink).appendTo( menu);
		$('<li class="list-inline-item text-truncate small" style="max-width: 100%;"></li>').attr( 'title', file.description).html( file.description).prependTo( menu);

		let cardText = $('<div class="card-text"></div>').append( menu).appendTo( body);
		if ( !!file.error) {
			if ( 10 > Number( file.size)) {
				cardText.prepend( '<h6 class="text-danger">file size error</h6>');

			}
			else {
				cardText.prepend( '<h6 class="text-danger">ERROR</h6>');

			}

		}

		col.appendTo('#<?= $uid; ?>row');

		allDeleteVisibility();
		allDownloadVisibility();

		card
		.data( 'file', file)
		.on( 'clear-location', function(e) {

			let _me = $(this);
			let _data = _me.data();

			_.post({
				url : _.url('<?= $this->route ?>'),
				data : {
					action : 'set-alarm-location-clear',
					id : <?= (int)$dto->id ?>,
					file : _data.file.description,

				},

			}).then( d => {
				_.growl( d)

				if ( 'ack' == d.response) {
					_data.file.location = '';
					_me.data('file', _data.file);

				}

			});

		})
		.on( 'set-location', function(e, location) {

			let _me = $(this);
			let _data = _me.data();

			_.post({
				url : _.url('<?= $this->route ?>'),
				data : {
					action : 'set-alarm-location',
					id : <?= (int)$dto->id ?>,
					file : _data.file.description,
					location : location


				},

			}).then( d => {
				_.growl( d)

				if ( 'ack' == d.response) {
					_data.file.location = location;
					_me.data('file', _data.file);

				}

			});

		})
		.on( 'contextmenu', function( e) {
			if ( e.shiftKey)
				return;

			e.stopPropagation();e.preventDefault();

			_.hideContexts();

			if ( smokeAlarms.length > 0) {
				// console.log( smokeAlarms);

				let _me = $(this);
				let _data = _me.data();
				let _context = _.context();

				$.each( smokeAlarms, (i, alarm) => {
					let ctrl = $('<a href="#"></a>');
					ctrl
					.data( 'file', _data.file)
					.data( 'location', alarm.location)
					.html( alarm.location)
					.on( 'click', function( e) {
						e.stopPropagation();e.preventDefault();

						let _me = $(this);
						let _data = _me.data();

						if ( _data.file.location == _data.location) {
							card.trigger('clear-location');

						}
						else {
							card.trigger('set-location', _data.location);

						}

						_context.close();

					});

					if ( _data.file.location == alarm.location) ctrl.prepend( '<i class="fa fa-check"></i>');

					_context.append( ctrl);

				});


				_context.open( e);

			}

		});

	};

	/*--- ---[]--- ---*/
	//~ console.log( '<?= $uid ?>fileupload');
	/*--- ---[]--- ---*/

	let cContainer = $('<div class="col-sm-4 col-md-3 col-xl-4 mb-1 d-print-none"></div>').appendTo( '#<?= $uid ?>row');
	<?php	if ( $diskSpace->exceeded) {	?>
	cContainer.append('<div class="alert alert-warning"><h5 class="alert-heading">disk space low</h5>uploaded disabled</div>');
	<?php	}
			else {	?>
	let c = _.fileDragDropContainer({fileControl : true}).appendTo( cContainer);
	<?php	}	?>

	let allDownload =  $('<a title="download zip" class="px-2 btn btn-light btn-sm d-none"><i class="fa fa-fw fa-download" title="download as zip file"></i>Zip</a>').attr( 'href', _.url('<?= $this->route ?>/zip/<?= $dto->id ?>'));

	let allDelete =  $('<button title="delete all" class="px-2 btn btn-light btn-sm d-none"><i class="fa fa-fw fa-trash"></i>Delete All</button>');
	let btnNotepad =  $('<button title="notepad" class="px-2 btn btn-light btn-sm"><i class="fa fa-fw fa-pencil"></i>note</button>');

	let bRow = $('<div class="row"></div>').appendTo( cContainer);
	let bCol = $('<div class="col text-center"></div>').appendTo( bRow);
	$('<div class="btn-group"></div>').appendTo( bCol).append( allDownload).append( allDelete).append( btnNotepad);

	allDelete.on( 'click', function( e) {
		$('button[data-delete]').each( function( i, el) { el.click(); });

	});

	let allDownloadVisibility = function() {
		$('img[data-logimage]').length > 0 ?
			allDownload.removeClass( 'd-none') :
			allDownload.addClass( 'd-none');

	};

	let allDeleteVisibility = function() {
		$('button[data-delete]').length > 0 ? allDelete.removeClass( 'd-none') : allDelete.addClass( 'd-none');

	};

	let notepad = {
		col : $('<div class="col-sm-8 col-md-9 col-xl-8 mb-1 d-none"></div>').appendTo( '#<?= $uid ?>row'),
		text : $('<textarea class="form-control h-100" readonly></textarea>'),
		val : function( v) {
			let ret = notepad.text.val( v);
			'' == v || null == v ? notepad.col.addClass( 'd-none') : notepad.col.removeClass( 'd-none');
			//~ console.log( v, '' == v);

			return ret;

		}

	};

	notepad.text.appendTo( notepad.col);
	notepad.val( <?= json_encode( $this->data->dto->notes, JSON_UNESCAPED_SLASHES ) ?>);

	btnNotepad.on( 'click', function( e) {
		e.stopPropagation(); e.preventDefault();

		_.loadModal({
			url : _.url('<?= $this->route ?>/notepad/<?= $dto->id ?>'),
			onSuccess : function( e, d) {
				if ( 'ack' == d.response) {
					//~ console.log( d);
					notepad.val( d.data.notes);

				}

			},

		});

	});

	<?php	if ( !$diskSpace->exceeded) {	?>
	_.fileDragDropHandler.call( c, {
		url : _.url( '<?= $this->route ?>'),
		queue : true,
		postData : {
			action : 'upload',
			id : <?= $dto->id ?>
		},
		onUpload : d => {
			if ( 'ack' == d.response) {
				$.each( d.files, ( i, file) => displayCard( file));

			}

		}

	});
	<?php	}	// if ( !$diskSpace->exceeded)	?>


	(cards => {
		$.each( cards, ( i, file) => displayCard( file));

	})( <?= json_encode( $this->data->files) ?>);

	_.post({
		url : _.url('<?= $this->route ?>'),
		data : {
			action : 'property-smokealarms',
			id : <?= (int)$dto->id ?>

		},

	}).then( d => {
		if ( 'ack' == d.response) {
			// console.log( d.alarms);
			smokeAlarms = d.alarms;
			// _.growl( d);

		}

	});

}) (_brayworth_); });
</script>
