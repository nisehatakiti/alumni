/**
 * Toggles the primary navigation on small screens, and the desktop
 * drilldown (parent/child) flyout menus.
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

		// Simple（上部）はホバー基本・Formal（左サイド）はクリックの
		// アコーディオン基本、と操作方法をレイアウトごとに分ける
		// （main.cssのドキュメントコメント参照）。レイアウトはbody要素の
		// クラス（alumni_theme_get_nav_layout()由来）で判定する。
		var isTopLayout = document.body.classList.contains( 'alumni-nav-layout-top' );

		// 「共通」「卒業生向け」「在校生向け」等のドリルダウン（親子）
		// メニュー。Simpleではクリックはタッチ操作向けの補助手段
		// （CSS側の:hover/:focus-withinが基本の展開操作）。Formalでは
		// クリックが唯一の開閉操作。
		var drilldownToggles = nav.querySelectorAll( '.alumni-nav-drilldown-toggle' );
		var allDrilldownItems = nav.querySelectorAll( '.alumni-nav-item-has-children' );

		function closeItem( item ) {
			item.classList.remove( 'is-open' );
			var itemToggle = item.querySelector( ':scope > .alumni-nav-drilldown-toggle' );
			if ( itemToggle ) {
				itemToggle.setAttribute( 'aria-expanded', 'false' );
			}
		}

		// 開いているすべてのドリルダウン項目を閉じる（外側クリック・
		// Escapeキー用）。Simple（浮いているフライアウトパネル）だけの
		// 挙動 — Formalの左サイドメニューは常設のアコーディオンなので、
		// ページの他の場所をクリックしただけで開いた枝が畳まれると
		// かえって使いにくい。
		function closeAllItems() {
			if ( ! isTopLayout ) {
				return;
			}
			Array.prototype.forEach.call( allDrilldownItems, closeItem );
		}

		// item と同じ階層（同じ親<ul>の直下）にある、他の開いている項目を
		// 閉じる — 兄弟フォルダを開いたら、同じ段の他の開いたフォルダを
		// 閉じる（隣接するサブメニュー同士が重なって表示されるのを防ぐ）。
		// item自身の祖先（上位階層）は対象外なので、深い階層を開いても
		// 親のフライアウトは開いたままになる。Simpleのフライアウト限定の
		// 挙動 — Formalの左サイドメニューでは複数のブランチを同時に
		// 開いたままブラウジングできる方が自然なので、兄弟は自動で
		// 閉じない。
		function closeSiblingItems( item ) {
			if ( ! isTopLayout ) {
				return;
			}
			var parentList = item.parentElement;
			if ( ! parentList ) {
				return;
			}
			Array.prototype.forEach.call( parentList.children, function ( sibling ) {
				if ( sibling === item || ! sibling.classList || ! sibling.classList.contains( 'alumni-nav-item-has-children' ) ) {
					return;
				}
				closeItem( sibling );
				// 兄弟の配下で開いたままの、さらに深い階層も一緒に閉じる
				// （次に同じ兄弟を開いたとき、前回開いていた孫階層が
				// いきなり見えている状態にならないように）。
				var deeperOpen = sibling.querySelectorAll( '.alumni-nav-item-has-children.is-open' );
				Array.prototype.forEach.call( deeperOpen, closeItem );
			} );
		}

		// 上部メニュー（Simple）のフライアウトが画面右端からはみ出す場合、
		// 右へではなく左へ開くよう切り替える（.alumni-nav-flyout-left、
		// main.css参照）。1階層目はヘッダー下に開くため対象外。
		function updateFlyoutDirection( item ) {
			var isTopLevel = item.parentElement && item.parentElement.classList && item.parentElement.classList.contains( 'alumni-nav-menu' );
			if ( isTopLevel ) {
				return; // 1階層目は右揃え/左揃えの既存ルール(main.css)に任せる。
			}
			var estimatedPanelWidth = 320; // main.cssのmax-width(22em相当)に合わせた概算値。
			var rect = item.getBoundingClientRect();
			var wouldOverflowRight = ( rect.right + estimatedPanelWidth ) > window.innerWidth;
			item.classList.toggle( 'alumni-nav-flyout-left', wouldOverflowRight );
		}

		Array.prototype.forEach.call( drilldownToggles, function ( drilldownToggle ) {
			drilldownToggle.addEventListener( 'click', function ( event ) {
				event.preventDefault();

				var parentItem = drilldownToggle.closest( '.alumni-nav-item-has-children' );
				if ( ! parentItem ) {
					return;
				}

				var isOpen = parentItem.classList.toggle( 'is-open' );
				drilldownToggle.setAttribute( 'aria-expanded', isOpen ? 'true' : 'false' );

				if ( isOpen ) {
					closeSiblingItems( parentItem );
					updateFlyoutDirection( parentItem );
				}
			} );
		} );

		// マウスホバーで開く場合（CSS :hover、Simpleのみ）も、はみ出し
		// 判定だけはJSで更新しておく — CSSだけでは画面端までの距離が
		// 分からないため。Formalはフライアウトしないので対象外。
		if ( isTopLayout ) {
			Array.prototype.forEach.call( allDrilldownItems, function ( item ) {
				item.addEventListener( 'mouseenter', function () {
					updateFlyoutDirection( item );
				} );
			} );
		}

		// メニューの外側をクリックしたら、開いているドリルダウンを
		// すべて閉じる。
		document.addEventListener( 'click', function ( event ) {
			if ( nav.contains( event.target ) ) {
				return;
			}
			closeAllItems();
		} );

		// Escapeキーで、開いているドリルダウンをすべて閉じる
		// （キーボード操作での抜け道を確保）。
		nav.addEventListener( 'keydown', function ( event ) {
			if ( 'Escape' === event.key ) {
				closeAllItems();
			}
		} );
	} );
} )();
