/**
 * Alumni Core admin interactions.
 */
( function () {
	'use strict';

	document.addEventListener( 'DOMContentLoaded', function () {
		// 基本設定: カラー周期数に合わせてカラー入力行を即時増減する。
		var cycleInput  = document.getElementById( 'alumni_core_color_cycle' );
		var colorsWrap  = document.getElementById( 'alumni-core-colors' );
		var rowTemplate = document.getElementById( 'alumni-core-color-row-template' );

		if ( cycleInput && colorsWrap && rowTemplate ) {
			function currentRowCount() {
				return colorsWrap.querySelectorAll( '.alumni-core-color-row' ).length;
			}

			function addRow( index ) {
				var fragment = rowTemplate.content.cloneNode( true );
				var row      = fragment.querySelector( '.alumni-core-color-row' );
				var label    = row.querySelector( 'label' );
				var input    = row.querySelector( 'input[type="color"]' );

				row.setAttribute( 'data-index', index );
				input.setAttribute( 'name', 'colors[' + index + ']' );

				for ( var i = 0; i < label.childNodes.length; i++ ) {
					var node = label.childNodes[ i ];
					if ( node.nodeType === Node.TEXT_NODE && node.textContent.indexOf( '__INDEX__' ) !== -1 ) {
						node.textContent = node.textContent.replace( '__INDEX__', index );
					}
				}

				colorsWrap.appendChild( fragment );
			}

			function removeLastRow() {
				var rows = colorsWrap.querySelectorAll( '.alumni-core-color-row' );
				if ( rows.length ) {
					rows[ rows.length - 1 ].remove();
				}
			}

			function syncRows() {
				var target = parseInt( cycleInput.value, 10 );
				if ( isNaN( target ) || target < 1 ) {
					target = 1;
				}

				var count = currentRowCount();
				while ( count < target ) {
					count++;
					addRow( count );
				}
				while ( count > target ) {
					removeLastRow();
					count--;
				}
			}

			cycleInput.addEventListener( 'change', syncRows );
			cycleInput.addEventListener( 'input', syncRows );
		}

		// トップページ設定: 表示数と項目種別をページ再読み込みなしで即時反映する。
		var homepageSections = document.querySelectorAll( '.alumni-homepage-section' );
		homepageSections.forEach( function ( section ) {
			var columnsSelect = section.querySelector( '.alumni-homepage-columns' );
			var slots = section.querySelectorAll( '.alumni-homepage-slot' );

			function syncSlotCount() {
				if ( ! columnsSelect ) {
					return;
				}

				var target = parseInt( columnsSelect.value, 10 );
				if ( isNaN( target ) || target < 1 ) {
					target = 1;
				}

				slots.forEach( function ( slot, index ) {
					slot.hidden = index >= target;
				} );
			}

			function syncSlotType( slot ) {
				var typeSelect = slot.querySelector( '.alumni-homepage-slot-type' );
				var contentField = slot.querySelector( '.alumni-homepage-slot-content-field' );
				var headingField = slot.querySelector( '.alumni-homepage-slot-heading-field' );

				if ( ! typeSelect ) {
					return;
				}

				var isHeading = typeSelect.value === 'heading';
				if ( contentField ) {
					contentField.hidden = isHeading;
				}
				if ( headingField ) {
					headingField.hidden = ! isHeading;
				}
			}

			if ( columnsSelect ) {
				columnsSelect.addEventListener( 'change', syncSlotCount );
			}

			slots.forEach( function ( slot ) {
				var typeSelect = slot.querySelector( '.alumni-homepage-slot-type' );
				if ( typeSelect ) {
					typeSelect.addEventListener( 'change', function () {
						syncSlotType( slot );
					} );
				}
				syncSlotType( slot );
			} );

			syncSlotCount();
		} );
	} );
} )();
