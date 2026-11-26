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

	function updateDemo() {
		var checked = document.querySelector( '.ldmi-base input:checked' );
		if ( ! checked ) {
			return;
		}
		document.querySelectorAll( '.ldmi-demo-screen' ).forEach( function ( screen ) {
			var which = screen.querySelector( '.ldmi-demo-which' );
			if ( which ) {
				which.textContent = screen.getAttribute( 'data-mode' ) === checked.value ? t.regular : t.second;
			}
		} );
	}

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
