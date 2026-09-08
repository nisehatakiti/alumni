/**
 * 役員・理事紹介 管理画面: 一覧の行追加/削除/並び替え。
 * 学校写真ギャラリー(school-photos-admin.js)の add/remove/move-up/down
 * パターンを、写真サムネイルの代わりにテーブル行に適用したもの。
 */
( function () {
	'use strict';

	document.addEventListener( 'DOMContentLoaded', function () {
		initOfficersTable();
	} );

	function initOfficersTable() {
		var list      = document.getElementById( 'alumni-officers-list' );
		var template  = document.getElementById( 'alumni-officers-row-template' );
		var addButton = document.getElementById( 'alumni-officers-add' );

		if ( ! list || ! template || ! addButton ) {
			return;
		}

		bindRowControls( list );

		// Every newly-added row must get its OWN unique index: PHP parses
		// officers[N][field] into an associative array keyed by N, so two
		// rows sharing the same N (e.g. both left as the literal
		// "__INDEX__" placeholder) would silently overwrite each other in
		// $_POST, and only the last one would ever be saved. Starting
		// from the existing (server-rendered) row count and only ever
		// incrementing guarantees every index used during this page's
		// lifetime is unique, even across add/remove/add sequences.
		var nextIndex = list.querySelectorAll( '.alumni-officers-row' ).length;

		addButton.addEventListener( 'click', function ( event ) {
			event.preventDefault();

			var index    = nextIndex++;
			var fragment = template.content.cloneNode( true );
			var row      = fragment.querySelector( '.alumni-officers-row' );

			row.setAttribute( 'data-index', index );
			row.querySelectorAll( '[name]' ).forEach( function ( field ) {
				field.name = field.name.replace( '__INDEX__', index );
			} );

			list.appendChild( fragment );
			bindRowControls( list );
		} );
	}

	function bindRowControls( scope ) {
		scope.querySelectorAll( '.alumni-officers-move-up' ).forEach( function ( button ) {
			if ( button.dataset.bound ) {
				return;
			}
			button.dataset.bound = '1';
			button.addEventListener( 'click', function ( event ) {
				event.preventDefault();
				var row  = button.closest( '.alumni-officers-row' );
				var prev = row && row.previousElementSibling;
				if ( row && prev ) {
					row.parentNode.insertBefore( row, prev );
				}
			} );
		} );

		scope.querySelectorAll( '.alumni-officers-move-down' ).forEach( function ( button ) {
			if ( button.dataset.bound ) {
				return;
			}
			button.dataset.bound = '1';
			button.addEventListener( 'click', function ( event ) {
				event.preventDefault();
				var row  = button.closest( '.alumni-officers-row' );
				var next = row && row.nextElementSibling;
				if ( row && next ) {
					row.parentNode.insertBefore( next, row );
				}
			} );
		} );

		scope.querySelectorAll( '.alumni-officers-remove' ).forEach( function ( button ) {
			if ( button.dataset.bound ) {
				return;
			}
			button.dataset.bound = '1';
			button.addEventListener( 'click', function ( event ) {
				event.preventDefault();
				var row = button.closest( '.alumni-officers-row' );
				if ( row ) {
					row.remove();
				}
			} );
		} );
	}
} )();
