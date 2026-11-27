/**
 * Light and Dark Mode Images: block editor integration.
 *
 * Adds a panel to the Image block for the second image and a document panel
 * for the second featured image. Plain wp.* globals, no build step.
 */
( function ( wp ) {
	'use strict';
	if ( ! wp || ! wp.element || ! wp.hooks ) {
		return;
	}
	var el = wp.element.createElement;
	var Fragment = wp.element.Fragment;
	var cfg = window.LDMI_EDITOR || {};
	var be = wp.blockEditor;
	var c = wp.components;

	function previewUrl( media ) {
		if ( ! media ) {
			return '';
		}
		var sizes = media.media_details && media.media_details.sizes;
		if ( sizes && sizes.medium && sizes.medium.source_url ) {
			return sizes.medium.source_url;
		}
		return media.source_url || '';
	}

	function useMedia( id ) {
		return wp.data.useSelect( function ( select ) {
			// getEntityRecord instead of the getMedia shortcut, which is deprecated since WordPress 6.9.
			return id ? select( 'core' ).getEntityRecord( 'postType', 'attachment', id ) : null;
		}, [ id ] );
	}

	function size( media ) {
		var d = media && media.media_details;
		return d && d.width && d.height ? { w: d.width, h: d.height } : null;
	}

	function Tile( props ) {
		return el(
			'figure',
			{ className: 'ldmi-tile is-' + props.scheme },
			props.url
				? el( 'img', { src: props.url, alt: '' } )
				: el( 'span', { className: 'ldmi-tile-empty' }, props.empty || '' ),
			el( 'figcaption', null, el( 'strong', null, props.mode ), el( 'span', null, props.what ) )
		);
	}

	/*
	 * Wipe: both images on top of each other, a slider reveals the second one from the right.
	 * The native range input works with the arrow keys; each side keeps the background of its scheme.
	 */
	function Wipe( props ) {
		var state = wp.element.useState( 50 );
		var pos = state[ 0 ];
		var setPos = state[ 1 ];
		var id = 'ldmi-wipe-' + props.uid;
		var altScheme = cfg.baseScheme === 'dark' ? 'light' : 'dark';
		return el(
			'div',
			{ className: 'ldmi-wipe is-base-' + cfg.baseScheme + ' is-alt-' + altScheme, style: { '--ldmi-pos': pos + '%' } },
			el(
				'div',
				{ className: 'ldmi-wipe-stage', 'aria-hidden': 'true' },
				el( 'img', { className: 'ldmi-wipe-a', src: props.a, alt: '' } ),
				el( 'img', { className: 'ldmi-wipe-b', src: props.b, alt: '' } ),
				el( 'span', { className: 'ldmi-wipe-handle' } ),
				el( 'span', { className: 'ldmi-wipe-tag is-a' }, cfg.baseLabel ),
				el( 'span', { className: 'ldmi-wipe-tag is-b' }, cfg.altLabel )
			),
			el( 'label', { className: 'ldmi-wipe-label', htmlFor: id }, cfg.wipeLabel ),
			el( 'input', {
				id: id,
				className: 'ldmi-wipe-range',
				type: 'range',
				min: 0,
				max: 100,
				step: 1,
				value: pos,
				'aria-valuetext': ( cfg.wipeValue || '%d' ).replace( '%d', String( 100 - pos ) ).replace( '%%', '%' ),
				onChange: function ( e ) {
					setPos( parseInt( e.target.value, 10 ) || 0 );
				}
			} )
		);
	}

	var wipeCount = 0;

	function Comparison( props ) {
		var regular = props.regular;
		var second = props.second;
		var a = size( regular );
		var b = size( second );
		var notice = null;
		if ( a && b ) {
			var diff = Math.abs( a.w / a.h - b.w / b.h ) / ( a.w / a.h );
			notice = diff > 0.02
				? el( c.Notice, { status: 'warning', isDismissible: false, className: 'ldmi-ratio' },
					( cfg.ratioWarning || '' ).replace( '%1$s', a.w + ' × ' + a.h ).replace( '%2$s', b.w + ' × ' + b.h ) )
				: el( 'p', { className: 'ldmi-ratio-ok' }, el( 'span', { className: 'dashicons dashicons-yes', 'aria-hidden': 'true' } ), cfg.ratioOk );
		}
		var uid = wp.element.useState( function () {
			wipeCount += 1;
			return wipeCount;
		} )[ 0 ];
		var urlA = previewUrl( regular );
		var urlB = previewUrl( second );
		return el(
			Fragment,
			null,
			urlA && urlB ? el( Wipe, { a: urlA, b: urlB, uid: uid } ) : null,
			el(
				'div',
				{ className: 'ldmi-compare' },
				el( Tile, { scheme: cfg.baseScheme, url: previewUrl( regular ), mode: cfg.baseLabel, what: cfg.regularImage, empty: cfg.noRegular } ),
				el( Tile, { scheme: cfg.baseScheme === 'dark' ? 'light' : 'dark', url: previewUrl( second ), mode: cfg.altLabel, what: cfg.secondImage } )
			),
			notice
		);
	}

	function Chooser( props ) {
		var id = props.id || 0;
		var media = useMedia( id );
		var regular = useMedia( props.regularId || 0 );
		return el(
			be.MediaUploadCheck,
			{ fallback: el( 'p', null, cfg.noPermission ) },
			el( be.MediaUpload, {
				onSelect: function ( m ) {
					props.onChange( m && m.id ? m.id : 0 );
				},
				allowedTypes: [ 'image' ],
				value: id,
				render: function ( r ) {
					return el(
						'div',
						{ className: 'ldmi-chooser' },
						id ? el( Comparison, { regular: regular, second: media } ) : null,
						el(
							'div',
							{ className: 'ldmi-actions' },
							el( c.Button, { variant: 'secondary', onClick: r.open, __next40pxDefaultSize: true }, id ? cfg.replace : cfg.choose ),
							id ? el( c.Button, { variant: 'link', isDestructive: true, onClick: function () { props.onChange( 0 ); } }, cfg.remove ) : null
						),
						el( 'p', { className: 'components-base-control__help ldmi-help' }, cfg.help )
					);
				}
			} )
		);
	}

	// Image block: attribute and panel.
	wp.hooks.addFilter( 'blocks.registerBlockType', 'ldmi/attribute', function ( settings, name ) {
		if ( name !== 'core/image' ) {
			return settings;
		}
		settings.attributes = Object.assign( {}, settings.attributes, { ldmiAltId: { type: 'number' } } );
		return settings;
	} );

	var withPanel = wp.compose.createHigherOrderComponent( function ( BlockEdit ) {
		return function ( props ) {
			if ( props.name !== 'core/image' ) {
				return el( BlockEdit, props );
			}
			var id = props.attributes.ldmiAltId || 0;
			return el(
				Fragment,
				null,
				el( BlockEdit, props ),
				el(
					be.InspectorControls,
					null,
					el(
						c.PanelBody,
						{ title: cfg.blockTitle, initialOpen: !! id },
						el( Chooser, {
							id: id,
							regularId: props.attributes.id || 0,
							onChange: function ( v ) {
								props.setAttributes( { ldmiAltId: v || undefined } );
							}
						} )
					)
				)
			);
		};
	}, 'withLdmiPanel' );
	wp.hooks.addFilter( 'editor.BlockEdit', 'ldmi/panel', withPanel );

	// Document panel for the second featured image.
	var DocPanel = ( wp.editor && wp.editor.PluginDocumentSettingPanel ) || ( wp.editPost && wp.editPost.PluginDocumentSettingPanel );
	if ( DocPanel && wp.plugins && wp.data ) {
		wp.plugins.registerPlugin( 'ldmi-featured', {
			render: function () {
				var state = wp.data.useSelect( function ( select ) {
					var ed = select( 'core/editor' );
					return ed ? { type: ed.getCurrentPostType(), meta: ed.getEditedPostAttribute( 'meta' ), featured: ed.getEditedPostAttribute( 'featured_media' ) } : { type: '', meta: null, featured: 0 };
				}, [] );
				var dispatch = wp.data.useDispatch( 'core/editor' );
				if ( ! state.type || ( cfg.postTypes || [] ).indexOf( state.type ) === -1 || ! state.meta ) {
					return null;
				}
				var id = state.meta[ cfg.metaKey ] || 0;
				return el(
					DocPanel,
					{ name: 'ldmi-featured', title: cfg.featuredTitle },
					el( Chooser, {
						id: id,
						regularId: state.featured || 0,
						onChange: function ( v ) {
							var meta = {};
							meta[ cfg.metaKey ] = v || 0;
							dispatch.editPost( { meta: meta } );
						}
					} )
				);
			}
		} );
	}
}( window.wp ) );
