/**
 * コンテンツのメタボックス: コンテンツ種別に応じて
 * 人物挨拶専用フィールドの表示/非表示を切り替える。
 */
( function () {
	'use strict';

	document.addEventListener( 'DOMContentLoaded', function () {
		var radios = document.querySelectorAll( 'input[name="alumni_content_kind"]' );
		var fields = document.getElementById( 'alumni-person-greeting-fields' );

		if ( ! radios.length || ! fields ) {
			return;
		}

		function sync() {
			var selected = document.querySelector( 'input[name="alumni_content_kind"]:checked' );
			var isPersonGreeting = selected && 'person_greeting' === selected.value;
			fields.style.display = isPersonGreeting ? '' : 'none';
		}

		for ( var i = 0; i < radios.length; i++ ) {
			radios[ i ].addEventListener( 'change', sync );
		}

		sync();
	} );
} )();
