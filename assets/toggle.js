/**
 * Light and Dark Mode Images: follow a theme's own light/dark switch.
 *
 * Without a switch the browser picks the <source> through its media query.
 * When the visitor has chosen a mode on the page, the theme marks it with a
 * class or attribute; this script reads the configured selectors and turns
 * the second image on ("all") or off ("not all"). When neither selector
 * matches, the original media query applies again.
 */
( function () {
	'use strict';
	var cfg = window.LDMI || {};

	function matches( selector ) {
		if ( ! selector ) {
			return false;
		}
		try {
			return document.querySelector( selector ) !== null;
		} catch ( e ) {
			return false;
		}
	}

	function apply() {
		var chosen = matches( cfg.dark ) ? 'dark' : ( matches( cfg.light ) ? 'light' : '' );
		var sources = document.querySelectorAll( 'source[data-ldmi-media]' );
		for ( var i = 0; i < sources.length; i++ ) {
			var media = chosen === '' ? sources[ i ].getAttribute( 'data-ldmi-media' ) : ( chosen === cfg.alt ? 'all' : 'not all' );
			if ( sources[ i ].media !== media ) {
				sources[ i ].media = media;
			}
		}
	}

	var pending = false;
	function schedule() {
		if ( pending ) {
			return;
		}
		pending = true;
		// One pass per burst of mutations. Not requestAnimationFrame: it does
		// not run in background tabs, and the switch must apply there as well.
		window.setTimeout( function () {
			pending = false;
			apply();
		}, 0 );
	}

	apply();
	if ( window.MutationObserver ) {
		var observer = new MutationObserver( schedule );
		var options = { attributes: true, attributeFilter: [ 'class', 'data-theme', 'data-color-scheme', 'data-mode' ] };
		observer.observe( document.documentElement, options );
		if ( document.body ) {
			observer.observe( document.body, options );
		}
		// Content loaded later (infinite scroll, page transitions).
		observer.observe( document.body || document.documentElement, { childList: true, subtree: true } );
	}
}() );
