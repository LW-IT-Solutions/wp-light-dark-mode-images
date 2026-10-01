<?php
/**
 * Editor integration: Image block setting, document panel and classic meta box.
 *
 * @package LightDarkModeImages
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Lets authors pick the second image.
 */
class LDMI_Editor {

	/** Hook into WordPress. */
	public static function init() {
		add_filter( 'register_block_type_args', array( __CLASS__, 'block_attribute' ), 10, 2 );
		add_action( 'enqueue_block_editor_assets', array( __CLASS__, 'block_editor_assets' ) );
		add_action( 'add_meta_boxes', array( __CLASS__, 'add_meta_box' ) );
		add_action( 'save_post', array( __CLASS__, 'save_meta_box' ), 10, 2 );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'meta_box_assets' ) );
	}

	/**
	 * Register the block attribute on the server, so it survives validation and rendering.
	 *
	 * @param array  $args Block type arguments.
	 * @param string $name Block name.
	 * @return array
	 */
	public static function block_attribute( $args, $name ) {
		if ( 'core/image' === $name ) {
			$args['attributes']              = isset( $args['attributes'] ) && is_array( $args['attributes'] ) ? $args['attributes'] : array();
			$args['attributes']['ldmiAltId'] = array( 'type' => 'number' );
		}
		return $args;
	}

	/**
	 * Labels that depend on the configured color scheme.
	 *
	 * @return array
	 */
	private static function labels() {
		$dark = 'dark' === LDMI_Settings::alt_scheme();
		return array(
			'blockTitle'    => $dark ? __( 'Image for dark mode', 'light-dark-mode-images' ) : __( 'Image for light mode', 'light-dark-mode-images' ),
			'featuredTitle' => $dark ? __( 'Featured image for dark mode', 'light-dark-mode-images' ) : __( 'Featured image for light mode', 'light-dark-mode-images' ),
			'help'          => $dark ? __( 'Shown instead of the regular image to visitors in dark mode. Use the same aspect ratio as the regular image.', 'light-dark-mode-images' ) : __( 'Shown instead of the regular image to visitors in light mode. Use the same aspect ratio as the regular image.', 'light-dark-mode-images' ),
			'choose'        => __( 'Choose image', 'light-dark-mode-images' ),
			'replace'       => __( 'Replace image', 'light-dark-mode-images' ),
			'remove'        => __( 'Remove', 'light-dark-mode-images' ),
			'noPermission'  => __( 'You need permission to upload files to choose an image.', 'light-dark-mode-images' ),
		);
	}

	/** Script for the block editor (no build step, plain wp.* globals). */
	public static function block_editor_assets() {
		wp_enqueue_script(
			'ldmi-editor',
			plugins_url( 'assets/editor.js', LDMI_FILE ),
			array( 'wp-block-editor', 'wp-components', 'wp-compose', 'wp-core-data', 'wp-data', 'wp-element', 'wp-hooks', 'wp-plugins' ),
			LDMI_VERSION,
			true
		);
		wp_localize_script(
			'ldmi-editor',
			'LDMI_EDITOR',
			array_merge(
				self::labels(),
				array(
					'metaKey'   => LDMI_Output::META,
					'postTypes' => array_values( get_post_types_by_support( 'thumbnail' ) ),
				)
			)
		);
	}

	/**
	 * Meta box for the classic editor. The block editor uses the document panel from editor.js instead.
	 *
	 * @param string $post_type Current post type.
	 */
	public static function add_meta_box( $post_type ) {
		if ( ! post_type_supports( $post_type, 'thumbnail' ) ) {
			return;
		}
		$labels = self::labels();
		add_meta_box(
			'ldmi-featured',
			$labels['featuredTitle'],
			array( __CLASS__, 'render_meta_box' ),
			$post_type,
			'side',
			'low',
			array( '__back_compat_meta_box' => true )
		);
	}

	/**
	 * Meta box content.
	 *
	 * @param WP_Post $post Current post.
	 */
	public static function render_meta_box( $post ) {
		$labels = self::labels();
		$id     = (int) get_post_meta( $post->ID, LDMI_Output::META, true );
		wp_nonce_field( 'ldmi_save', 'ldmi_nonce' );
		echo '<div class="ldmi-box">';
		echo '<div class="ldmi-preview">';
		if ( LDMI_Output::is_image( $id ) ) {
			echo wp_get_attachment_image( $id, 'medium', false, array( 'style' => 'max-width:100%;height:auto;display:block;margin-bottom:8px' ) );
		}
		echo '</div>';
		printf( '<input type="hidden" name="ldmi_alt_image_id" class="ldmi-id" value="%d">', (int) $id );
		printf( '<button type="button" class="button ldmi-choose">%s</button> ', esc_html( $id ? $labels['replace'] : $labels['choose'] ) );
		printf( '<button type="button" class="button-link button-link-delete ldmi-remove"%s>%s</button>', $id ? '' : ' hidden', esc_html( $labels['remove'] ) );
		echo '<p class="description">' . esc_html( $labels['help'] ) . '</p>';
		echo '</div>';
	}

	/**
	 * Save the meta box value.
	 *
	 * @param int     $post_id Post ID.
	 * @param WP_Post $post    Post object.
	 */
	public static function save_meta_box( $post_id, $post ) {
		if ( ! isset( $_POST['ldmi_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['ldmi_nonce'] ) ), 'ldmi_save' ) ) {
			return;
		}
		if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || wp_is_post_revision( $post_id ) || ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}
		if ( ! post_type_supports( $post->post_type, 'thumbnail' ) ) {
			return;
		}
		$id = isset( $_POST['ldmi_alt_image_id'] ) ? absint( wp_unslash( $_POST['ldmi_alt_image_id'] ) ) : 0;
		if ( $id && LDMI_Output::is_image( $id ) ) {
			update_post_meta( $post_id, LDMI_Output::META, $id );
		} else {
			delete_post_meta( $post_id, LDMI_Output::META );
		}
	}

	/**
	 * Media frame for the classic meta box.
	 *
	 * @param string $hook Current screen.
	 */
	public static function meta_box_assets( $hook ) {
		if ( 'post.php' !== $hook && 'post-new.php' !== $hook ) {
			return;
		}
		$screen = get_current_screen();
		if ( ! $screen || $screen->is_block_editor() || ! post_type_supports( $screen->post_type, 'thumbnail' ) ) {
			return;
		}
		wp_enqueue_media();
		wp_enqueue_script( 'ldmi-metabox', plugins_url( 'assets/metabox.js', LDMI_FILE ), array( 'jquery' ), LDMI_VERSION, true );
		wp_localize_script( 'ldmi-metabox', 'LDMI_BOX', self::labels() );
	}
}
