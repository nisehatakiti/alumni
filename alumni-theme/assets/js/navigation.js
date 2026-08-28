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
	} );
} )();
