/**
 * コンテンツのメタボックス: コンテンツ種別に応じて
 * 人物挨拶専用フィールド／規約類専用フィールドの表示/非表示を切り替える。
 * 規約類の改定履歴（複数の日付）の行の追加・削除も扱う。
 */
( function () {
	'use strict';

	document.addEventListener( 'DOMContentLoaded', function () {
		var radios       = document.querySelectorAll( 'input[name="alumni_content_kind"]' );
		var personFields = document.getElementById( 'alumni-person-greeting-fields' );
		var termsFields  = document.getElementById( 'alumni-terms-fields' );

		if ( radios.length && ( personFields || termsFields ) ) {
			var sync = function () {
				var selected = document.querySelector( 'input[name="alumni_content_kind"]:checked' );
				var value     = selected ? selected.value : '';

				if ( personFields ) {
					personFields.style.display = ( 'person_greeting' === value ) ? '' : 'none';
				}
				if ( termsFields ) {
					termsFields.style.display = ( 'terms' === value ) ? '' : 'none';
				}
			};

			for ( var i = 0; i < radios.length; i++ ) {
				radios[ i ].addEventListener( 'change', sync );
			}

			sync();
		}

		var revisionList     = document.getElementById( 'alumni-terms-revision-dates' );
		var revisionTemplate = document.getElementById( 'alumni-terms-revision-date-row-template' );
		var revisionAddBtn   = document.getElementById( 'alumni-terms-revision-date-add' );

		if ( ! revisionList || ! revisionTemplate || ! revisionAddBtn ) {
			return;
		}

		function bindRemove( row ) {
			var removeBtn = row.querySelector( '.alumni-terms-revision-date-remove' );
			if ( removeBtn ) {
				removeBtn.addEventListener( 'click', function () {
					row.parentNode.removeChild( row );
				} );
			}
		}

		var existingRows = revisionList.querySelectorAll( '.alumni-terms-revision-date-row' );
		for ( var j = 0; j < existingRows.length; j++ ) {
			bindRemove( existingRows[ j ] );
		}

		revisionAddBtn.addEventListener( 'click', function () {
			var clone = revisionTemplate.content.firstElementChild.cloneNode( true );
			bindRemove( clone );
			revisionList.appendChild( clone );
		} );
	} );
} )();
