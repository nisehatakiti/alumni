/**
 * 同窓会 > 基本設定: keeps the number of カラー fields in sync with
 * カラー周期数, without a page reload.
 */
( function () {
	'use strict';

	document.addEventListener( 'DOMContentLoaded', function () {
		var cycleInput  = document.getElementById( 'alumni_core_color_cycle' );
		var colorsWrap  = document.getElementById( 'alumni-core-colors' );
		var rowTemplate = document.getElementById( 'alumni-core-color-row-template' );

		if ( ! cycleInput || ! colorsWrap || ! rowTemplate ) {
			return;
		}

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

			// Replace the "__INDEX__" placeholder text node with the real number.
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
	} );
} )();
