/**
 * Light and Dark Mode Images: media frame for the classic editor meta box.
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

		choose.on( 'click', function ( e ) {
			e.preventDefault();
			if ( ! frame ) {
				frame = wp.media( { title: t.featuredTitle, library: { type: 'image' }, button: { text: t.choose }, multiple: false } );
				frame.on( 'select', function () {
					var a = frame.state().get( 'selection' ).first().toJSON();
					var url = a.sizes && a.sizes.medium ? a.sizes.medium.url : a.url;
					input.val( a.id );
					preview.empty().append( $( '<img>', { src: url, alt: '' } ).css( { maxWidth: '100%', height: 'auto', display: 'block', marginBottom: '8px' } ) );
					choose.text( t.replace );
					remove.prop( 'hidden', false );
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
		} );
	} );
}( jQuery ) );
