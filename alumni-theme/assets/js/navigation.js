/**
 * Toggles the primary navigation on small screens, and the desktop
 * drilldown (parent/child) dropdown menus.
 */
( function () {
	'use strict';

	document.addEventListener( 'DOMContentLoaded', function () {
		var nav    = document.getElementById( 'site-navigation' );
		var toggle = nav ? nav.querySelector( '.menu-toggle' ) : null;

		if ( ! nav || ! toggle ) {
			return;
		}

		toggle.addEventListener( 'click', function () {
			var isOpen = nav.classList.toggle( 'is-open' );
			toggle.setAttribute( 'aria-expanded', isOpen ? 'true' : 'false' );
		} );

		// 「共通」「卒業生向け」「在校生向け」等のドリルダウン（親子）
		// メニュー: クリックで開閉する。ホバーだけに頼らないことで、
		// タッチ操作でも子メニューに確実に到達できるようにする。
		var drilldownToggles = nav.querySelectorAll( '.alumni-nav-drilldown-toggle' );

		// トップレベルのドリルダウン項目だけ（メニュー直下の<li>）を
		// 「同時に1つだけ開く」対象にする — ネストした項目は独立した
		// フローティングパネルを持たない(CSS側でposition: staticに
		// している)ため、閉じる対象から除く。
		var topLevelItems = nav.querySelectorAll( '.alumni-nav-menu > .alumni-nav-item-has-children' );

		function closeOtherTopLevelItems( exceptItem ) {
			Array.prototype.forEach.call( topLevelItems, function ( item ) {
				if ( item === exceptItem ) {
					return;
				}
				item.classList.remove( 'is-open' );
				var itemToggle = item.querySelector( ':scope > .alumni-nav-drilldown-toggle' );
				if ( itemToggle ) {
					itemToggle.setAttribute( 'aria-expanded', 'false' );
				}
			} );
		}

		Array.prototype.forEach.call( drilldownToggles, function ( drilldownToggle ) {
			drilldownToggle.addEventListener( 'click', function ( event ) {
				event.preventDefault();

				var parentItem = drilldownToggle.closest( '.alumni-nav-item-has-children' );
				if ( ! parentItem ) {
					return;
				}

				var isTopLevel = parentItem.parentElement && parentItem.parentElement.classList.contains( 'alumni-nav-menu' );
				var isOpen     = parentItem.classList.toggle( 'is-open' );
				drilldownToggle.setAttribute( 'aria-expanded', isOpen ? 'true' : 'false' );

				// トップレベル項目を開いたら、他に開いていたトップレベルの
				// ドロップダウンを閉じる — 隣接するメニュー項目のサブメニュー
				// 同士が同時に重なって表示されるのを防ぐ。
				if ( isTopLevel && isOpen ) {
					closeOtherTopLevelItems( parentItem );
				}
			} );
		} );

		// メニューの外側をクリックしたら、開いているトップレベルの
		// ドロップダウンをすべて閉じる。
		document.addEventListener( 'click', function ( event ) {
			if ( nav.contains( event.target ) ) {
				return;
			}
			closeOtherTopLevelItems( null );
		} );

		// Escapeキーで、開いているトップレベルのドロップダウンを閉じる
		// （キーボード操作での抜け道を確保）。
		nav.addEventListener( 'keydown', function ( event ) {
			if ( 'Escape' === event.key ) {
				closeOtherTopLevelItems( null );
			}
		} );
	} );
} )();
