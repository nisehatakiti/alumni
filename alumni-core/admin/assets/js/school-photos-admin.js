/**
 * 学校写真 管理画面: 複数画像ギャラリーの追加/削除/並び替えと、
 * 表示方式（固定表示／自動切替）に応じた「表示画像」欄の表示切替。
 */
( function () {
	'use strict';

	document.addEventListener( 'DOMContentLoaded', function () {
		initDisplayModeToggle();
		initGallery();
	} );

	function initDisplayModeToggle() {
		var radios      = document.querySelectorAll( 'input[name="school_photo_display_mode"]' );
		var featuredRow = document.getElementById( 'alumni-photo-featured-row' );
		var intervalRow = document.getElementById( 'alumni-photo-interval-row' );

		if ( ! radios.length || ( ! featuredRow && ! intervalRow ) ) {
			return;
		}

		function sync() {
			var selected = document.querySelector( 'input[name="school_photo_display_mode"]:checked' );
			var isFixed  = !! ( selected && 'fixed' === selected.value );

			// 表示画像（固定表示）は固定表示の時だけ、写真の切替時間（自動
			// 切替）は自動切替の時だけ意味を持つので、常に逆の表示状態にする。
			if ( featuredRow ) {
				featuredRow.style.display = isFixed ? '' : 'none';
			}
			if ( intervalRow ) {
				intervalRow.style.display = isFixed ? 'none' : '';
			}
		}

		for ( var i = 0; i < radios.length; i++ ) {
			radios[ i ].addEventListener( 'change', sync );
		}

		sync();
	}

	function initGallery() {
		var list         = document.getElementById( 'alumni-photo-gallery-list' );
		var template     = document.getElementById( 'alumni-photo-gallery-item-template' );
		var addButton    = document.getElementById( 'alumni-photo-gallery-add' );
		var featuredSelect = document.getElementById( 'alumni-photo-featured-select' );

		if ( ! list || ! template || ! addButton ) {
			return;
		}

		bindItemControls( list );

		if ( typeof wp === 'undefined' || ! wp.media ) {
			return;
		}

		var frame = null;

		addButton.addEventListener( 'click', function ( event ) {
			event.preventDefault();

			if ( ! frame ) {
				frame = wp.media( {
					title: addButton.getAttribute( 'data-title' ) || '',
					button: { text: addButton.getAttribute( 'data-button-text' ) || '' },
					library: { type: 'image' },
					multiple: true
				} );

				frame.on( 'select', function () {
					var attachments = frame.state().get( 'selection' ).toJSON();
					for ( var i = 0; i < attachments.length; i++ ) {
						addPhoto( attachments[ i ] );
					}
				} );
			}

			frame.open();
		} );

		function existingIds() {
			var ids = [];
			list.querySelectorAll( '.alumni-photo-gallery-item' ).forEach( function ( item ) {
				var id = item.getAttribute( 'data-id' );
				if ( id && '0' !== id ) {
					ids.push( id );
				}
			} );
			return ids;
		}

		function addPhoto( attachment ) {
			var id = String( attachment.id );

			if ( existingIds().indexOf( id ) !== -1 ) {
				return; // already in the gallery
			}

			var fragment = template.content.cloneNode( true );
			var item     = fragment.querySelector( '.alumni-photo-gallery-item' );
			var input    = item.querySelector( '.alumni-photo-gallery-item-input' );
			var thumb    = item.querySelector( '.alumni-photo-gallery-thumb' );

			item.setAttribute( 'data-id', id );
			input.value = id;

			var thumbUrl = ( attachment.sizes && attachment.sizes.thumbnail )
				? attachment.sizes.thumbnail.url
				: attachment.url;
			var img = document.createElement( 'img' );
			img.src = thumbUrl;
			img.alt = '';
			thumb.appendChild( img );

			list.appendChild( fragment );
			bindItemControls( list );

			if ( featuredSelect ) {
				var option = document.createElement( 'option' );
				option.value = id;
				option.textContent = attachment.title || ( '#' + id );
				featuredSelect.appendChild( option );
			}
		}

		function bindItemControls( scope ) {
			scope.querySelectorAll( '.alumni-photo-move-up' ).forEach( function ( button ) {
				if ( button.dataset.bound ) {
					return;
				}
				button.dataset.bound = '1';
				button.addEventListener( 'click', function ( event ) {
					event.preventDefault();
					var item = button.closest( '.alumni-photo-gallery-item' );
					var prev = item && item.previousElementSibling;
					if ( item && prev ) {
						list.insertBefore( item, prev );
					}
				} );
			} );

			scope.querySelectorAll( '.alumni-photo-move-down' ).forEach( function ( button ) {
				if ( button.dataset.bound ) {
					return;
				}
				button.dataset.bound = '1';
				button.addEventListener( 'click', function ( event ) {
					event.preventDefault();
					var item = button.closest( '.alumni-photo-gallery-item' );
					var next = item && item.nextElementSibling;
					if ( item && next ) {
						list.insertBefore( next, item );
					}
				} );
			} );

			scope.querySelectorAll( '.alumni-photo-remove' ).forEach( function ( button ) {
				if ( button.dataset.bound ) {
					return;
				}
				button.dataset.bound = '1';
				button.addEventListener( 'click', function ( event ) {
					event.preventDefault();
					var item = button.closest( '.alumni-photo-gallery-item' );
					if ( ! item ) {
						return;
					}
					var id = item.getAttribute( 'data-id' );
					item.remove();

					if ( featuredSelect && id ) {
						var option = featuredSelect.querySelector( 'option[value="' + id + '"]' );
						if ( option ) {
							if ( option.selected ) {
								featuredSelect.value = '';
							}
							option.remove();
						}
					}
				} );
			} );
		}
	}
} )();
