/**
 * コンテンツのメタボックス: コンテンツ種別に応じて
 * 人物挨拶専用フィールド／規約類専用フィールドの表示/非表示を切り替える。
 */
( function () {
	'use strict';

	document.addEventListener( 'DOMContentLoaded', function () {
		var radios       = document.querySelectorAll( 'input[name="alumni_content_kind"]' );
		var personFields = document.getElementById( 'alumni-person-greeting-fields' );
		var termsFields  = document.getElementById( 'alumni-terms-fields' );

		if ( ! radios.length || ( ! personFields && ! termsFields ) ) {
			return;
		}

		function sync() {
			var selected = document.querySelector( 'input[name="alumni_content_kind"]:checked' );
			var value     = selected ? selected.value : '';

			if ( personFields ) {
				personFields.style.display = ( 'person_greeting' === value ) ? '' : 'none';
			}
			if ( termsFields ) {
				termsFields.style.display = ( 'terms' === value ) ? '' : 'none';
			}
		}

		for ( var i = 0; i < radios.length; i++ ) {
			radios[ i ].addEventListener( 'change', sync );
		}

		sync();
	} );
} )();
