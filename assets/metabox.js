/**
 * Light and Dark Mode Images: media frame for the classic editor meta box, and the regular and the second
 * featured image side by side with an aspect ratio check. Sizes come from the page; nothing is requested.
 */
( function ( $ ) {
	'use strict';
	var t = window.LDMI_BOX || {};
	var frame;

	$( function () {
		var box = $( '#ldmi-featured .ldmi-box' );
		if ( ! box.length ) {
			return;
		}
		var input = box.find( '.ldmi-id' );
		var preview = box.find( '.ldmi-preview' );
		var choose = box.find( '.ldmi-choose' );
		var remove = box.find( '.ldmi-remove' );
		var pair = box.find( '.ldmi-pair' );
		var regular = {
			src: pair.attr( 'data-regular-src' ) || '',
			w: parseInt( pair.attr( 'data-regular-w' ), 10 ) || 0,
			h: parseInt( pair.attr( 'data-regular-h' ), 10 ) || 0
		};
		var second = {
			w: parseInt( pair.attr( 'data-second-w' ), 10 ) || 0,
			h: parseInt( pair.attr( 'data-second-h' ), 10 ) || 0
		};
		var altScheme = t.baseScheme === 'dark' ? 'light' : 'dark';

		function tile( scheme, src, mode, what, empty ) {
			var fig = $( '<figure>', { 'class': 'ldmi-pair-tile is-' + scheme } );
			fig.append( src ? $( '<img>', { src: src, alt: '' } ) : $( '<span>', { 'class': 'ldmi-pair-empty', text: empty || '' } ) );
			fig.append( $( '<figcaption>' ).append( $( '<strong>', { text: mode } ), $( '<span>', { text: what } ) ) );
			return fig;
		}

		function drawPair() {
			var img = preview.find( 'img' );
			if ( ! img.length || '0' === input.val() ) {
				pair.prop( 'hidden', true ).empty();
				return;
			}
			var grid = $( '<div>', { 'class': 'ldmi-pair-grid' } );
			grid.append(
				tile( t.baseScheme, regular.src, t.baseLabel, t.regularImage, t.noRegular ),
				tile( altScheme, img.attr( 'src' ), t.altLabel, t.secondImage )
			);
			pair.empty().append( grid );
			if ( regular.w && regular.h && second.w && second.h ) {
				var a = regular.w / regular.h;
				var ok = Math.abs( a - second.w / second.h ) / a <= 0.02;
				var line = $( '<p>', { 'class': 'ldmi-pair-ratio ' + ( ok ? 'is-ok' : 'is-warning' ) } );
				line.append( $( '<span>', { 'class': 'dashicons ' + ( ok ? 'dashicons-yes' : 'dashicons-warning' ), 'aria-hidden': 'true' } ) );
				line.append( $( '<span>', { text: ok ? t.ratioOk : ( t.ratioWarning || '' ).replace( '%1$s', regular.w + ' × ' + regular.h ).replace( '%2$s', second.w + ' × ' + second.h ) } ) );
				pair.append( line );
			}
			pair.prop( 'hidden', false );
		}

		// A new regular featured image: WordPress replaces the content of the "Featured image" box. Its size is
		// known only when it was chosen in this page's media frame; otherwise the check waits for the next load.
		var featured = document.querySelector( '#postimagediv .inside' );
		if ( featured && window.MutationObserver ) {
			new window.MutationObserver( function () {
				var thumb = $( '#postimagediv .inside img' ).first();
				var id = parseInt( $( '#_thumbnail_id' ).val(), 10 ) || 0;
				regular = { src: id > 0 && thumb.length ? thumb.attr( 'src' ) : '', w: 0, h: 0 };
				// Only a frame that already exists is read; creating one here would load the media library.
				if ( id > 0 && window.wp && wp.media && wp.media.featuredImage && wp.media.featuredImage._frame ) {
					var sel = wp.media.featuredImage._frame.state().get( 'selection' );
					var att = sel && sel.first() ? sel.first().toJSON() : null;
					if ( att && att.id === id && att.width && att.height ) {
						regular.w = att.width;
						regular.h = att.height;
					}
				}
				drawPair();
			} ).observe( featured, { childList: true } );
		}

		choose.on( 'click', function ( e ) {
			e.preventDefault();
			if ( ! frame ) {
				frame = wp.media( { title: t.featuredTitle, library: { type: 'image' }, button: { text: t.choose }, multiple: false } );
				frame.on( 'select', function () {
					var a = frame.state().get( 'selection' ).first().toJSON();
					var url = a.sizes && a.sizes.medium ? a.sizes.medium.url : a.url;
					input.val( a.id );
					second = { w: a.width || 0, h: a.height || 0 };
					preview.empty().append( $( '<img>', { src: url, alt: '', 'class': 'ldmi-preview-image' } ) );
					choose.text( t.replace );
					remove.prop( 'hidden', false );
					drawPair();
				} );
			}
			frame.open();
		} );

		remove.on( 'click', function ( e ) {
			e.preventDefault();
			input.val( '0' );
			preview.empty();
			choose.text( t.choose );
			remove.prop( 'hidden', true );
			drawPair();
		} );

		drawPair();
	} );
}( jQuery ) );
