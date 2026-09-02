/**
 * コンテンツのメタボックス: 規約類の改定履歴（複数の日付）の行の追加・
 * 削除を扱う。コンテンツ種別に応じた人物挨拶専用フィールド／規約類専用
 * フィールドの表示・非表示は、種別が作成時の入口だけで決まり以後は不変
 * になった(コンテンツ種別のラジオボタン自体を廃止した)ため、もう
 * クライアント側で切り替える必要がなく、PHP側(Content_Meta_Box::render())
 * が最初から該当するブロックだけを表示状態で出力する。
 */
( function () {
	'use strict';

	document.addEventListener( 'DOMContentLoaded', function () {
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
