/**
 * Light and Dark Mode Images: Settings > Media.
 *
 * Keeps the two example screens in step with the chosen base scheme, checks the
 * selector fields in this browser and inserts example selectors on request.
 * Nothing is sent anywhere.
 */
( function () {
	'use strict';
	var t = window.LDMI_SETTINGS || {};

	var still = window.matchMedia && window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches;
	var system = window.matchMedia ? window.matchMedia( '(prefers-color-scheme: dark)' ) : null;
	var preview = 'system';
	var shownBase = t.base || 'light';

	function base() {
		var checked = document.querySelector( '.ldmi-base input:checked' );
		return checked ? checked.value : shownBase;
	}

	function updateDemo() {
		var now = base();
		document.querySelectorAll( '.ldmi-demo-screen' ).forEach( function ( screen ) {
			var which = screen.querySelector( '.ldmi-demo-which' );
			if ( which ) {
				which.textContent = screen.getAttribute( 'data-mode' ) === now ? t.regular : t.second;
			}
		} );
		// With a real pair, the regular image moves to the screen of the chosen base scheme.
		var photos = document.querySelectorAll( '.ldmi-demo-image.has-photo img' );
		if ( photos.length === 2 && now !== shownBase ) {
			var a = photos[ 0 ].getAttribute( 'src' );
			photos[ 0 ].setAttribute( 'src', photos[ 1 ].getAttribute( 'src' ) );
			photos[ 1 ].setAttribute( 'src', a );
			shownBase = now;
		}
		updatePreview();
	}

	/* "Preview as": marks the screen a visitor in that mode gets; "This device" follows the system setting live. */
	function updatePreview() {
		var group = document.querySelector( '.ldmi-preview-switch' );
		if ( ! group ) {
			return;
		}
		var mode = 'system' === preview ? ( system && system.matches ? 'dark' : 'light' ) : preview;
		document.querySelectorAll( '.ldmi-demo-screen' ).forEach( function ( screen ) {
			var on = screen.getAttribute( 'data-mode' ) === mode;
			screen.classList.toggle( 'is-active', on );
			screen.classList.toggle( 'is-dimmed', ! on );
		} );
		group.querySelectorAll( '.ldmi-preview-mode' ).forEach( function ( button ) {
			button.setAttribute( 'aria-pressed', button.getAttribute( 'data-mode' ) === preview ? 'true' : 'false' );
		} );
		var status = document.querySelector( '.ldmi-preview-status' );
		if ( status && t.sees && t.sees[ mode ] ) {
			status.textContent = t.sees[ mode ][ mode === base() ? 'regular' : 'second' ] || '';
		}
	}

	( function () {
		var group = document.querySelector( '.ldmi-preview-switch' );
		if ( ! group ) {
			return;
		}
		group.hidden = false;
		group.querySelectorAll( '.ldmi-preview-mode' ).forEach( function ( button ) {
			button.addEventListener( 'click', function () {
				preview = button.getAttribute( 'data-mode' );
				updatePreview();
			} );
		} );
		if ( system ) {
			var onChange = function () {
				if ( 'system' === preview ) {
					updatePreview();
				}
			};
			if ( system.addEventListener ) {
				system.addEventListener( 'change', onChange );
			} else if ( system.addListener ) {
				system.addListener( onChange );
			}
		}
		updatePreview();
	}() );

	/* Coverage rings fill and their numbers count up once. */
	( function () {
		var rings = document.querySelectorAll( '.ldmi-cover-ring' );
		if ( still || ! rings.length || ! window.requestAnimationFrame ) {
			return;
		}
		var ends = Array.prototype.map.call( rings, function ( ring ) {
			var label = ring.querySelector( 'span' );
			return [ ring, parseFloat( ring.style.getPropertyValue( '--ldmi-share' ) ) || 0, label, /%/.test( label ? label.textContent : '' ) ];
		} );
		var t0 = window.performance.now();
		function step( now ) {
			var p = Math.min( 1, Math.max( 0, ( now - t0 ) / 800 ) );
			var e = 1 - Math.pow( 1 - p, 3 );
			ends.forEach( function ( x ) {
				x[ 0 ].style.setProperty( '--ldmi-share', ( x[ 1 ] * e ).toFixed( 1 ) + '%' );
				if ( x[ 3 ] ) {
					x[ 2 ].textContent = Math.round( x[ 1 ] * e ) + ' %';
				}
			} );
			if ( p < 1 ) {
				window.requestAnimationFrame( step );
			}
		}
		window.requestAnimationFrame( step );
	}() );

	function check( input ) {
		var out = document.getElementById( input.getAttribute( 'aria-describedby' ) );
		if ( ! out ) {
			return;
		}
		var value = input.value.trim();
		var ok = true;
		if ( value ) {
			try {
				document.querySelector( value );
			} catch ( e ) {
				ok = false;
			}
		}
		out.className = 'ldmi-check' + ( value ? ( ok ? ' is-valid' : ' is-invalid' ) : '' );
		out.textContent = value ? ( ok ? t.valid : t.invalid ) : '';
		if ( ok ) {
			input.removeAttribute( 'aria-invalid' );
		} else {
			input.setAttribute( 'aria-invalid', 'true' );
		}
	}

	document.querySelectorAll( '.ldmi-base input' ).forEach( function ( radio ) {
		radio.addEventListener( 'change', updateDemo );
	} );

	document.querySelectorAll( '.ldmi-selector' ).forEach( function ( input ) {
		input.addEventListener( 'input', function () {
			check( input );
		} );
		check( input );
	} );

	document.querySelectorAll( '.ldmi-examples' ).forEach( function ( box ) {
		box.hidden = false;
	} );
	document.querySelectorAll( '.ldmi-example' ).forEach( function ( button ) {
		var value = button.getAttribute( 'data-value' );
		if ( t.add ) {
			button.setAttribute( 'aria-label', t.add.replace( '%s', value ) );
		}
		button.addEventListener( 'click', function () {
			var input = document.getElementById( button.getAttribute( 'data-target' ) );
			if ( ! input ) {
				return;
			}
			var parts = input.value.split( ',' ).map( function ( part ) {
				return part.trim();
			} ).filter( Boolean );
			if ( parts.indexOf( value ) === -1 ) {
				parts.push( value );
			}
			input.value = parts.join( ', ' ).slice( 0, 200 );
			check( input );
			input.focus();
		} );
	} );
}() );
