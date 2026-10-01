<?php
/**
 * Front-end output: wraps images in <picture> with a source for the other color scheme.
 *
 * @package LightDarkModeImages
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Builds the <picture> markup for featured images and Image blocks.
 */
class LDMI_Output {

	const META = '_ldmi_alt_image_id';

	/** Hook into WordPress. */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'register_meta' ) );
		add_filter( 'post_thumbnail_html', array( __CLASS__, 'featured_image' ), 20, 5 );
		add_filter( 'render_block_core/image', array( __CLASS__, 'image_block' ), 20, 2 );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'toggle_script' ) );
	}

	/** Post meta for the second featured image, editable through the REST API. */
	public static function register_meta() {
		foreach ( get_post_types_by_support( 'thumbnail' ) as $type ) {
			register_post_meta(
				$type,
				self::META,
				array(
					'type'              => 'integer',
					'single'            => true,
					'default'           => 0,
					'show_in_rest'      => true,
					'sanitize_callback' => 'absint',
					'auth_callback'     => function ( $allowed, $meta_key, $post_id ) {
						return current_user_can( 'edit_post', $post_id );
					},
				)
			);
		}
	}

	/**
	 * Media query under which the second image applies.
	 *
	 * @return string
	 */
	public static function media_query() {
		return '(prefers-color-scheme: ' . LDMI_Settings::alt_scheme() . ')';
	}

	/**
	 * Whether an ID points to an image attachment.
	 *
	 * @param int $id Attachment ID.
	 * @return bool
	 */
	public static function is_image( $id ) {
		return $id > 0 && wp_attachment_is_image( $id );
	}

	/**
	 * The <source> element for the second image.
	 *
	 * @param int    $id    Attachment ID of the second image.
	 * @param string $size  Image size.
	 * @param string $sizes Value of the sizes attribute of the regular image, if any.
	 * @return string Empty if the attachment cannot be used.
	 */
	public static function source_tag( $id, $size, $sizes = '' ) {
		$src = wp_get_attachment_image_src( $id, $size );
		if ( ! $src ) {
			return '';
		}
		$srcset = wp_get_attachment_image_srcset( $id, $size );
		if ( ! $srcset ) {
			$srcset = $src[0];
		}
		if ( '' === $sizes ) {
			$sizes = (string) wp_get_attachment_image_sizes( $id, $size );
		}
		return sprintf(
			'<source data-ldmi-media="%1$s" media="%1$s" srcset="%2$s"%3$s width="%4$d" height="%5$d">',
			esc_attr( self::media_query() ),
			esc_attr( $srcset ),
			'' !== $sizes ? ' sizes="' . esc_attr( $sizes ) . '"' : '',
			(int) $src[1],
			(int) $src[2]
		);
	}

	/**
	 * Put the first <img> of an HTML fragment into a <picture> with the given source.
	 *
	 * @param string $html   Markup with one image.
	 * @param string $source The <source> element.
	 * @return string
	 */
	public static function wrap( $html, $source ) {
		if ( '' === $source || false !== stripos( $html, '<picture' ) ) {
			return $html;
		}
		return (string) preg_replace_callback(
			'/<img\b[^>]*>/i',
			function ( $m ) use ( $source ) {
				return '<picture class="ldmi-picture">' . $source . $m[0] . '</picture>';
			},
			$html,
			1
		);
	}

	/**
	 * Read the sizes attribute of the first <img>.
	 *
	 * @param string $html Markup.
	 * @return string
	 */
	private static function img_sizes( $html ) {
		if ( preg_match( '/<img\b[^>]*\ssizes=("|\')([^"\']*)\1/i', $html, $m ) ) {
			return html_entity_decode( $m[2], ENT_QUOTES );
		}
		return '';
	}

	/**
	 * Featured image.
	 *
	 * @param string       $html         Markup.
	 * @param int          $post_id      Post ID.
	 * @param int          $thumbnail_id Attachment ID of the regular image.
	 * @param string|int[] $size         Requested size.
	 * @return string
	 */
	public static function featured_image( $html, $post_id, $thumbnail_id, $size ) {
		if ( '' === $html ) {
			return $html;
		}
		$alt = (int) get_post_meta( $post_id, self::META, true );
		if ( ! self::is_image( $alt ) || $alt === (int) $thumbnail_id ) {
			return $html;
		}
		$size = is_string( $size ) && '' !== $size ? $size : 'post-thumbnail';
		return self::wrap( $html, self::source_tag( $alt, $size, self::img_sizes( $html ) ) );
	}

	/**
	 * Image block.
	 *
	 * @param string $content Rendered block.
	 * @param array  $block   Parsed block.
	 * @return string
	 */
	public static function image_block( $content, $block ) {
		$attrs = isset( $block['attrs'] ) && is_array( $block['attrs'] ) ? $block['attrs'] : array();
		$alt   = isset( $attrs['ldmiAltId'] ) ? (int) $attrs['ldmiAltId'] : 0;
		if ( ! self::is_image( $alt ) ) {
			return $content;
		}
		$size = isset( $attrs['sizeSlug'] ) && is_string( $attrs['sizeSlug'] ) ? $attrs['sizeSlug'] : 'full';
		return self::wrap( $content, self::source_tag( $alt, $size, self::img_sizes( $content ) ) );
	}

	/** Small script that follows a theme's own light/dark switch, only when selectors are set. */
	public static function toggle_script() {
		$s = LDMI_Settings::get();
		if ( '' === $s['dark_selector'] && '' === $s['light_selector'] ) {
			return;
		}
		wp_enqueue_script( 'ldmi-toggle', plugins_url( 'assets/toggle.js', LDMI_FILE ), array(), LDMI_VERSION, array( 'in_footer' => true ) );
		wp_localize_script(
			'ldmi-toggle',
			'LDMI',
			array(
				'alt'   => LDMI_Settings::alt_scheme(),
				'dark'  => $s['dark_selector'],
				'light' => $s['light_selector'],
			)
		);
	}
}
