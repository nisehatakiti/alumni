/**
 * 学校写真の自動切替（フェード）。固定表示や、写真が1枚しかない場合は
 * 何もしない。最初の写真はサーバー側で is-active クラスが付与済みなので、
 * JavaScriptが無効/失敗してもその1枚は表示され続ける。
 */
( function () {
	'use strict';

	var INTERVAL_MS = 5000;

	document.addEventListener( 'DOMContentLoaded', function () {
		try {
			var sections = document.querySelectorAll( '.school-photos-slideshow' );

			for ( var s = 0; s < sections.length; s++ ) {
				initSlideshow( sections[ s ] );
			}
		} catch ( error ) {
			// A broken slideshow must never break the rest of the page.
		}
	} );

	function initSlideshow( section ) {
		var slides = section.querySelectorAll( '.school-photo-slide' );

		if ( slides.length <= 1 ) {
			return;
		}

		var current = 0;

		for ( var i = 0; i < slides.length; i++ ) {
			if ( slides[ i ].classList.contains( 'is-active' ) ) {
				current = i;
				break;
			}
		}

		setInterval( function () {
			try {
				slides[ current ].classList.remove( 'is-active' );
				current = ( current + 1 ) % slides.length;
				slides[ current ].classList.add( 'is-active' );
			} catch ( error ) {
				// Ignore a single failed tick rather than breaking the page.
			}
		}, INTERVAL_MS );
	}
} )();
