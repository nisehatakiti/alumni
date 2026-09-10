( function () {
	'use strict';
	document.addEventListener( 'DOMContentLoaded', function () {
		if ( typeof wp === 'undefined' || ! wp.media ) { return; }
		document.querySelectorAll( '.alumni-song-motto-media-field' ).forEach( function ( field ) {
			var input = field.querySelector( 'input[type="hidden"]' );
			var name = field.querySelector( '.alumni-song-motto-media-name' );
			var select = field.querySelector( '.alumni-song-motto-media-select' );
			var clear = field.querySelector( '.alumni-song-motto-media-clear' );
			var type = field.getAttribute( 'data-library-type' ) || '';
			select.addEventListener( 'click', function ( e ) {
				e.preventDefault();
				var frame = wp.media( { title: select.textContent, button: { text: '選択' }, library: { type: type }, multiple: false } );
				frame.on( 'select', function () {
					var attachment = frame.state().get( 'selection' ).first().toJSON();
					input.value = attachment.id;
					name.textContent = attachment.title || attachment.filename || attachment.url;
					clear.style.display = '';
				} );
				frame.open();
			} );
			clear.addEventListener( 'click', function ( e ) {
				e.preventDefault(); input.value = ''; name.textContent = '未選択'; clear.style.display = 'none';
			} );
		} );
	} );
} )();