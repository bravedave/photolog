<?php
/*
	David Bray
	BrayWorth Pty Ltd
	e. david@brayworth.com.au

	This work is licensed under a Creative Commons Attribution 4.0 International Public License.
		http://creativecommons.org/licenses/by/4.0/
	*/

	$uid = strings::rand();
	$dto = $this->data->dto;

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



<script>
(function( _b_ ) {
	let queue = [];
	let enqueue = function( params) {
		let options = $.extend({
			postData : {},
			droppedFiles : {},

		}, params);

		return new Promise( function( resolve, reject) {
			/*
			* create forms with 10 elements
			*/

			let data = new FormData();
			for(let o in options.postData) {
				data.append( o, options.postData[o]);

			}

			$.each( options.droppedFiles, function(i, file) {
				if ( i > 0 && i % 10 == 0) {
					queue.push( data);

					data = new FormData();
					for(let o in options.postData) {
						data.append( o, options.postData[o]);

					}

				}

				data.append('files-'+i, file);
				//~ console.log( file);

			});

			//~ return;

			queue.push( data);

			let progressQue = $('.progress-queue', options.host);
			if ( queue.length > 0) {
				progressQue
					.data('items', queue.length)
					.css('width', '0')
					.attr('aria-valuenow', '0');

				progressQue.parent().removeClass( 'd-none');

			}

			//~ console.log( queue.length)
			let queueHandler = function() {
				if ( queue.length > 0) {
					let data = queue.shift();
					let p = ( progressQue.data('items') - queue.length) / progressQue.data('items') * 100;
					//~ console.log( 'queue', p)
					progressQue
						.css('width', p + '%')
						.attr('aria-valuenow', p);

					//~ console.log( data, queue.length)
					sendData.call( data, options).then( queueHandler);

				}
				else {
					progressQue.parent().addClass( 'd-none');
					resolve();

				}

			}

			queueHandler();

		});

	}

	let sendData = function( params) {
			//~ droppedFiles : {},
			//~ postData : {},
		let options = $.extend({
			url : false,
			onUpload : function( response) {},
			host : $('body'),

		}, params);

		let formData = this;
		// Display the key/value pairs
		//~ for (var pair of formData.entries()) {
		    //~ console.log(pair[0]+ ', ' + pair[1]);
		//~ }

		return new Promise( function( resolve, reject) {

			// this is a form
			let progressBar = $('.box__fill', options.host);
			progressBar
				.css('width', '0')
				.attr('aria-valuenow', '0');

			options.host.addClass('is-uploading');

			$.ajax({
				url: options.url,
				type: 'POST',
				data: formData,
				dataType: 'json',
				cache: false,
				contentType: false,
				processData: false,
				xhr: function() {
					let xhr = new window.XMLHttpRequest();
					xhr.upload.addEventListener("progress", function (e) {
						//~ if (e.lengthComputable)
							//~ $('.box__fill', options.host).css('width', ( e.loaded / e.total * 100) + '%');
						if (e.lengthComputable) {
							progressBar
								.css('width', ( e.loaded / e.total * 100) + '%')
								.attr('aria-valuenow', ( e.loaded / e.total * 100));

						}

					})

					return xhr;

				}

			})
			.done( function( d) {
				if ( 'ack' == d.response) {
					$.each( d.data, function( i, j) {
						$('body').growl( j);

					})

				}
				else {
					$('body').growl( d);

				}

				options.onUpload( d);

				resolve()

			})
			.always( function( r) {
				options.host.removeClass('is-uploading');

			})
			.fail( function( r) {
				console.warn(r);
				_b_.modal({
					title : 'Upload Error',
					text : 'there was an error uploading the attachments<br />we recommend you reload your browser'
				});

			});

		});

	}

	let uploader = function( params) {
			//~ url : false,
			//~ onUpload : function( response) {},
			//~ host : false,
		let options = $.extend({
			postData : {},
			droppedFiles : {},

		}, params);

		let data = new FormData();
		for(let o in options.postData) { data.append( o, options.postData[o]); }

		$.each( options.droppedFiles, function(i, file) { data.append('files-'+i, file); });

		sendData.call( data, options);

	}
	;
	_b_.fileDragDropContainer = function( params) {
		let options = $.extend({fileControl : false}, params);

		//~ console.log( '_b_.fileDragDropContainer');
		let c = $('<div />');

		$('<div class="progress-bar progress-bar-striped box__fill" role="progressbar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" />')
			.appendTo( $('<div class="progress box__uploading" />').appendTo( c));

		$('<div class="progress-bar progress-bar-striped progress-queue text-center" role="progressbar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">queue</div>')
			.appendTo( $('<div class="progress d-none mt-2" />').appendTo( c));

		if ( options.fileControl) {
			let ig = $('<div class="input-group mb-1" />').appendTo( c);

			let rand = String( Math.round( Math.random() * 1000));
			let lbl = $('<span class="input-group-text">Upload</span>').attr( 'id', rand + 'FileAddon01');
			$('<div class="input-group-prepend" />').append( lbl).appendTo( ig);

			let div = $('<div class="custom-file" />').appendTo( ig);
			$('<input type="file" class="custom-file-input" multiple />')
				.attr( 'id', rand + 'File01')
				.attr('aria-describedby', rand + 'FileAddon01')
				.appendTo( div);

			$('<label class="custom-file-label">Choose file</label>')
				.attr( 'for', rand + 'File01')
				.appendTo( div);

		}

		return ( c);

	}
	;
	_b_.fileDragDropHandler = function( params) {
		let _el = $(this);

		let options = $.extend( {
			url : false,
			queue : false,
			host : _el

		}, params);

		if ( !options.url)
			throw 'Invalid upload url';

		let isAdvancedUpload = (function() {
			let div = document.createElement('div');
			return (('draggable' in div) || ('ondragstart' in div && 'ondrop' in div)) && 'FormData' in window && 'FileReader' in window;
		})();

		$('input[type="file"]', this).on( 'change', function( e) {
			let _me = $(this);

			options.droppedFiles = e.originalEvent.target.files;
			if ( options.droppedFiles) {
				_me.prop( 'disabled', true);
				options.queue ? enqueue( options).then( function() { _me.val('').prop( 'disabled', false); }) : uploader( options);

			}

		});

		if ( isAdvancedUpload && !options.host.hasClass('has-advanced-upload')) {

			//~ console.log( 'setup has-advanced-upload');
			options.host
			.addClass('has-advanced-upload')
			.on('drag dragstart dragend dragover dragenter dragleave drop', function(e) {
				e.preventDefault(); e.stopPropagation();
			})
			.on('dragover dragenter', function() { $(this).addClass('is-dragover'); })
			.on('dragleave dragend drop', function() { $(this).removeClass('is-dragover'); })
			.on('drop', function(e) {
				e.preventDefault();
				options.droppedFiles = e.originalEvent.dataTransfer.files;

				if ( options.droppedFiles) {
					options.queue ? enqueue( options) : uploader( options);

				}

			});

		}	// if (isAdvancedUpload && !options.host.hasClass('has-advanced-upload'))

	}
	;

})( _brayworth_ );

$(document).ready( function() {
	let displayCard = function( file) {
		let col = $('<div class="col-sm-4 col-md-3 col-xl-2 mb-1" />');
		let card = $('<div class="card" />').appendTo( col);
		let img = $('<img class="card-img-top" data-logimage />').attr( 'title', file.description).attr( 'src', file.url).appendTo( card);

		//~ console.log( file.url);

		let body = $('<div class="card-body py-1" />').appendTo( card);
		let menu = $('<ul class="list-inline" />');
		let openLink = $('<a target="_blank" title="open in new tab" class="px-2 btn btn-light btn-sm"><i class="fa fa-external-link" /></a>').attr( 'href', file.url + '&v=full');
		//~ let downloadLink = $('<a title="download" class="px-2 btn btn-light btn-sm"><i class="fa fa-download" /></a>').attr( 'download', file.description).attr( 'href', file.url);
			//~ $('<li class="list-inline-item" />').append( downloadLink).appendTo( menu);
		let deleteLink = $('<button data-delete type="button" title="delete" class="px-2 btn btn-light btn-sm"><i class="fa fa-trash" /></button>').on( 'click', function( e) {
			_cms_.post({
				url : _cms_.url('property_photolog'),
				data : {
					action : 'delete',
					id : <?= $dto->id ?>,
					file : file.description,

				}

			})
			.then( function( d) {
				_cms_.growl( d);
				if ( 'ack' == d.response) {
					col.remove();

				}

				allDeleteVisibility();
				allDownloadVisibility();

			});

		});

		$('<li class="list-inline-item" />').append( openLink).appendTo( menu);
		$('<li class="list-inline-item" />').append( deleteLink).appendTo( menu);
		$('<li class="list-inline-item text-truncate small" style="max-width: 100%;" />').attr( 'title', file.description).html( file.description).prependTo( menu);
		$('<div class="card-text" />').append( menu).appendTo( body);

		col.appendTo('#<?= $uid; ?>row');

		allDeleteVisibility();
		allDownloadVisibility();

	}

	/*--- ---[]--- ---*/
	//~ console.log( '<?= $uid ?>fileupload');
	/*--- ---[]--- ---*/

	let cContainer = $('<div class="col-sm-4 col-md-3 col-xl-4 mb-1" />').appendTo( '#<?= $uid ?>row');
	let c = _brayworth_.fileDragDropContainer({fileControl : true}).appendTo( cContainer);

	let allDownload =  $('<a title="download zip" class="px-2 btn btn-light btn-sm d-none"><i class="fa fa-fw fa-download" title="download as zip file" />Zip</a>').attr( 'href', _cms_.url('property_photolog/zip/<?= $dto->id ?>'));

	let allDelete =  $('<button title="delete all" class="px-2 btn btn-light btn-sm d-none"><i class="fa fa-fw fa-trash" />Delete All</button>');
	let btnNotepad =  $('<button title="notepad" class="px-2 btn btn-light btn-sm"><i class="fa fa-fw fa-pencil" />note</button>');
	//~ if ( !_cms_.currentUser.isDavid) btnNotepad.addClass( 'd-none');

	let bRow = $('<div class="row" />').appendTo( cContainer);
	let bCol = $('<div class="col text-center" />').appendTo( bRow);
	$('<div class="btn-group" />').appendTo( bCol).append( allDownload).append( allDelete).append( btnNotepad);

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
		col : $('<div class="col-sm-8 col-md-9 col-xl-8 mb-1 d-none" />').appendTo( '#<?= $uid ?>row'),
		text : $('<textarea class="form-control h-100" readonly />'),
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

		_brayworth_.loadModal({
			url : _brayworth_.url('property_photolog/notepad/<?= $dto->id ?>'),
			onSuccess : function( e, d) {
				if ( 'ack' == d.response) {
					//~ console.log( d);
					notepad.val( d.data.notes);

				}

			},

		});

	});

	_brayworth_.fileDragDropHandler.call( c, {
		url : _cms_.url( 'property_photolog'),
		queue : true,
		postData : {
			action : 'upload',
			id : <?= $dto->id ?>
		},
		onUpload : function( d) {
			//~ console.log( d);
			if ( 'ack' == d.response) {
				//~ displayCard( d);
				$.each( d.files, function( i, file) {
					//~ console.log( file);
					displayCard( file);

				});

			}

		}

	});


	(function( cards) {
		$.each( cards, function( i, file) {
			//~ console.log( file);
			displayCard( file);

		});

	})( <?= json_encode( $this->data->files) ?>);

});
</script>