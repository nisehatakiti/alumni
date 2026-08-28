/**
 * ニュース／イベントのメタボックス: コンテンツ種別に応じて
 * イベント開催日欄の表示/非表示を切り替える。
 */
( function () {
	'use strict';

	document.addEventListener( 'DOMContentLoaded', function () {
		var radios    = document.querySelectorAll( 'input[name="alumni_content_type"]' );
		var dateField = document.getElementById( 'alumni-event-date-field' );

		if ( ! radios.length || ! dateField ) {
			return;
		}

		function sync() {
			var selected = document.querySelector( 'input[name="alumni_content_type"]:checked' );
			var isEvent  = selected && 'event' === selected.value;
			dateField.style.display = isEvent ? '' : 'none';
		}

		for ( var i = 0; i < radios.length; i++ ) {
			radios[ i ].addEventListener( 'change', sync );
		}

		sync();
	} );
} )();
