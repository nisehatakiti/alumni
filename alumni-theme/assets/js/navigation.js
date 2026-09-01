/**
 * Toggles the primary navigation on small screens.
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

		// 「同窓会情報」等のドリルダウン（親子）メニュー: クリックで開閉する。
		// ホバーだけに頼らないことで、タッチ操作でも子メニュー（会長挨拶・
		// 校長挨拶など、人物挨拶コンテンツから動的に生成される項目）に
		// 確実に到達できるようにする。
		var drilldownToggles = nav.querySelectorAll( '.alumni-nav-drilldown-toggle' );

		Array.prototype.forEach.call( drilldownToggles, function ( drilldownToggle ) {
			drilldownToggle.addEventListener( 'click', function ( event ) {
				event.preventDefault();

				var parentItem = drilldownToggle.closest( '.alumni-nav-item-has-children' );
				if ( ! parentItem ) {
					return;
				}

				var isOpen = parentItem.classList.toggle( 'is-open' );
				drilldownToggle.setAttribute( 'aria-expanded', isOpen ? 'true' : 'false' );
			} );
		} );
	} );
} )();
