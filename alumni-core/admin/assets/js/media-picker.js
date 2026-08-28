/**
 * 校章／同窓会ロゴなど、単一画像を選択するWordPressメディアライブラリ
 * ピッカー。`.alumni-media-picker` 要素を対象に動作する（複数存在してOK）。
 */
( function () {
	'use strict';

	document.addEventListener( 'DOMContentLoaded', function () {
		if ( typeof wp === 'undefined' || ! wp.media ) {
			return;
		}

		var pickers = document.querySelectorAll( '.alumni-media-picker' );

		pickers.forEach( function ( picker ) {
			var input     = picker.querySelector( '.alumni-media-picker-input' );
			var preview   = picker.querySelector( '.alumni-media-preview' );
			var selectBtn = picker.querySelector( '.alumni-media-picker-select' );
			var clearBtn  = picker.querySelector( '.alumni-media-picker-clear' );

			if ( ! input || ! preview || ! selectBtn || ! clearBtn ) {
				return;
			}

			var frame = null;

			selectBtn.addEventListener( 'click', function ( event ) {
				event.preventDefault();

				if ( frame ) {
					frame.open();
					return;
				}

				frame = wp.media( {
					title: picker.getAttribute( 'data-title' ) || '',
					button: { text: picker.getAttribute( 'data-button-text' ) || '' },
					library: { type: 'image' },
					multiple: false
				} );

				frame.on( 'select', function () {
					var attachment = frame.state().get( 'selection' ).first().toJSON();
					var thumbUrl   = ( attachment.sizes && attachment.sizes.thumbnail )
						? attachment.sizes.thumbnail.url
						: attachment.url;

					input.value = attachment.id;

					while ( preview.firstChild ) {
						preview.removeChild( preview.firstChild );
					}
					var img = document.createElement( 'img' );
					img.src = thumbUrl;
					img.alt = '';
					preview.appendChild( img );

					clearBtn.style.display = '';
				} );

				frame.open();
			} );

			clearBtn.addEventListener( 'click', function ( event ) {
				event.preventDefault();

				input.value = '';
				while ( preview.firstChild ) {
					preview.removeChild( preview.firstChild );
				}
				clearBtn.style.display = 'none';
			} );
		} );
	} );
} )();
