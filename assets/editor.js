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
			return id ? select( 'core' ).getMedia( id ) : null;
		}, [ id ] );
	}

	function Chooser( props ) {
		var id = props.id || 0;
		var media = useMedia( id );
		var url = previewUrl( media );
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
						url ? el( 'img', { src: url, alt: '', style: { maxWidth: '100%', height: 'auto', display: 'block', marginBottom: '8px' } } ) : null,
						el( c.Button, { variant: 'secondary', onClick: r.open }, id ? cfg.replace : cfg.choose ),
						id ? el( c.Button, { variant: 'link', isDestructive: true, onClick: function () { props.onChange( 0 ); }, style: { marginLeft: '8px' } }, cfg.remove ) : null,
						el( 'p', { className: 'components-base-control__help', style: { marginTop: '8px' } }, cfg.help )
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
					return ed ? { type: ed.getCurrentPostType(), meta: ed.getEditedPostAttribute( 'meta' ) } : { type: '', meta: null };
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
