<?php
/**
 * Settings under Settings > Media.
 *
 * @package LightDarkModeImages
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Stores which color scheme the regular images are made for and, optionally,
 * the CSS selectors a theme toggle sets.
 */
class LDMI_Settings {

	const OPTION = 'ldmi_settings';

	/** Hook into WordPress. */
	public static function init() {
		add_action( 'admin_init', array( __CLASS__, 'register' ) );
		add_filter( 'plugin_action_links_' . plugin_basename( LDMI_FILE ), array( __CLASS__, 'action_link' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'assets' ) );
	}

	/**
	 * Current settings with defaults.
	 *
	 * @return array{base: string, dark_selector: string, light_selector: string}
	 */
	public static function get() {
		$saved = get_option( self::OPTION, array() );
		$saved = is_array( $saved ) ? $saved : array();
		return array(
			'base'           => ( isset( $saved['base'] ) && 'dark' === $saved['base'] ) ? 'dark' : 'light',
			'dark_selector'  => isset( $saved['dark_selector'] ) ? (string) $saved['dark_selector'] : '',
			'light_selector' => isset( $saved['light_selector'] ) ? (string) $saved['light_selector'] : '',
		);
	}

	/**
	 * Color scheme of the second image: the opposite of the regular one.
	 *
	 * @return string 'dark' or 'light'.
	 */
	public static function alt_scheme() {
		$s = self::get();
		return 'dark' === $s['base'] ? 'light' : 'dark';
	}

	/** Register the option and its fields on the Media settings screen. */
	public static function register() {
		register_setting(
			'media',
			self::OPTION,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( __CLASS__, 'sanitize' ),
				'default'           => array(),
			)
		);
		add_settings_section( 'ldmi', __( 'Light and dark mode images', 'light-dark-mode-images' ), array( __CLASS__, 'section' ), 'media' );
		add_settings_field( 'ldmi_base', __( 'Regular images are made for', 'light-dark-mode-images' ), array( __CLASS__, 'field_base' ), 'media', 'ldmi' );
		add_settings_field( 'ldmi_dark_selector', __( 'Selector when dark mode is chosen', 'light-dark-mode-images' ), array( __CLASS__, 'field_dark' ), 'media', 'ldmi', array( 'label_for' => 'ldmi_dark_selector' ) );
		add_settings_field( 'ldmi_light_selector', __( 'Selector when light mode is chosen', 'light-dark-mode-images' ), array( __CLASS__, 'field_light' ), 'media', 'ldmi', array( 'label_for' => 'ldmi_light_selector' ) );
	}

	/**
	 * Clean the submitted values.
	 *
	 * @param mixed $input Submitted value.
	 * @return array
	 */
	public static function sanitize( $input ) {
		$input = is_array( $input ) ? $input : array();
		return array(
			'base'           => ( isset( $input['base'] ) && 'dark' === $input['base'] ) ? 'dark' : 'light',
			'dark_selector'  => self::sanitize_selector( isset( $input['dark_selector'] ) ? $input['dark_selector'] : '' ),
			'light_selector' => self::sanitize_selector( isset( $input['light_selector'] ) ? $input['light_selector'] : '' ),
		);
	}

	/**
	 * A CSS selector as plain text, without characters that could end the attribute or script it is printed into.
	 *
	 * @param mixed $value Submitted selector.
	 * @return string
	 */
	public static function sanitize_selector( $value ) {
		$value = sanitize_text_field( (string) $value );
		$value = str_replace( array( '<', '>', '{', '}', ';', '\\' ), '', $value );
		return substr( trim( $value ), 0, 200 );
	}

	/**
	 * Stylesheet and script of the settings section, only on Settings > Media.
	 *
	 * @param string $hook Current screen.
	 */
	public static function assets( $hook ) {
		if ( 'options-media.php' !== $hook ) {
			return;
		}
		wp_enqueue_style( 'ldmi-settings', plugins_url( 'assets/settings.css', LDMI_FILE ), array(), LDMI_VERSION );
		wp_enqueue_script( 'ldmi-settings', plugins_url( 'assets/settings.js', LDMI_FILE ), array(), LDMI_VERSION, true );
		wp_localize_script(
			'ldmi-settings',
			'LDMI_SETTINGS',
			array(
				'regular' => __( 'regular image', 'light-dark-mode-images' ),
				'second'  => __( 'second image', 'light-dark-mode-images' ),
				'valid'   => __( 'Valid selector.', 'light-dark-mode-images' ),
				'invalid' => __( 'This is not a valid CSS selector. The theme switch is ignored until it is fixed.', 'light-dark-mode-images' ),
				/* translators: %s: CSS selector. */
				'add'     => __( 'Add %s', 'light-dark-mode-images' ),
				'base'    => self::get()['base'],
				'sees'    => array(
					'light' => array(
						'regular' => __( 'Visitors in light mode see the regular image.', 'light-dark-mode-images' ),
						'second'  => __( 'Visitors in light mode see the second image.', 'light-dark-mode-images' ),
					),
					'dark'  => array(
						'regular' => __( 'Visitors in dark mode see the regular image.', 'light-dark-mode-images' ),
						'second'  => __( 'Visitors in dark mode see the second image.', 'light-dark-mode-images' ),
					),
				),
			)
		);
	}

	/**
	 * Which of the latest published posts carry a second image: featured images and Image blocks,
	 * the posts that still lack one, and one real pair for the preview. Read only.
	 *
	 * @return array{featured: int, featured_alt: int, blocks: int, blocks_alt: int, posts: int, missing: array, pair: array|null}
	 */
	private static function inventory() {
		$types = array_values(
			array_filter(
				get_post_types_by_support( 'thumbnail' ),
				function ( $type ) {
					return 'attachment' !== $type && 0 !== strpos( $type, 'attachment:' ) && post_type_exists( $type );
				}
			)
		);
		$out   = array(
			'featured'     => 0,
			'featured_alt' => 0,
			'blocks'       => 0,
			'blocks_alt'   => 0,
			'posts'        => 0,
			'missing'      => array(),
			'pair'         => null,
		);
		if ( ! $types ) {
			return $out;
		}
		$query = new WP_Query(
			array(
				'post_type'              => $types,
				'post_status'            => 'publish',
				'posts_per_page'         => 200,
				'orderby'                => 'date',
				'order'                  => 'DESC',
				'no_found_rows'          => true,
				'ignore_sticky_posts'    => true,
				'update_post_term_cache' => false,
			)
		);
		foreach ( $query->posts as $post ) {
			++$out['posts'];
			$thumb  = (int) get_post_thumbnail_id( $post );
			$alt    = (int) get_post_meta( $post->ID, LDMI_Output::META, true );
			$no_alt = 0;
			$images = array();
			if ( $thumb ) {
				++$out['featured'];
				if ( LDMI_Output::is_image( $alt ) ) {
					++$out['featured_alt'];
					if ( ! $out['pair'] ) {
						$out['pair'] = array( $thumb, $alt, $post->ID );
					}
				}
			}
			if ( has_blocks( $post->post_content ) ) {
				self::image_blocks( parse_blocks( $post->post_content ), $images );
			}
			foreach ( $images as $img ) {
				++$out['blocks'];
				if ( $img[1] && LDMI_Output::is_image( $img[1] ) ) {
					++$out['blocks_alt'];
					if ( ! $out['pair'] && $img[0] && LDMI_Output::is_image( $img[0] ) ) {
						$out['pair'] = array( $img[0], $img[1], $post->ID );
					}
				} else {
					++$no_alt;
				}
			}
			$featured_missing = $thumb && ! LDMI_Output::is_image( $alt );
			if ( ( $featured_missing || $no_alt ) && count( $out['missing'] ) < 8 && current_user_can( 'edit_post', $post->ID ) ) {
				$out['missing'][] = array(
					'id'       => $post->ID,
					'thumb'    => $thumb ? $thumb : ( $images && $images[0][0] ? $images[0][0] : 0 ),
					'featured' => $featured_missing,
					'blocks'   => $no_alt,
				);
			}
		}
		return $out;
	}

	/**
	 * Collect the Image blocks of a block tree, nested blocks included.
	 *
	 * @param array $blocks Parsed blocks.
	 * @param array $found  List of [attachment ID, second image ID], filled by reference.
	 */
	private static function image_blocks( $blocks, &$found ) {
		foreach ( $blocks as $block ) {
			if ( 'core/image' === $block['blockName'] ) {
				$found[] = array(
					isset( $block['attrs']['id'] ) ? (int) $block['attrs']['id'] : 0,
					isset( $block['attrs']['ldmiAltId'] ) ? (int) $block['attrs']['ldmiAltId'] : 0,
				);
			}
			if ( ! empty( $block['innerBlocks'] ) ) {
				self::image_blocks( $block['innerBlocks'], $found );
			}
		}
	}

	/**
	 * One coverage figure: ring, count and label.
	 *
	 * @param int    $part  Images with a second image.
	 * @param int    $total All images.
	 * @param string $label What is counted.
	 */
	private static function cover_item( $part, $total, $label ) {
		$pct = $total ? (int) round( $part / $total * 100 ) : 0;
		printf(
			'<div class="ldmi-cover-item%1$s"><span class="ldmi-cover-ring" style="%2$s" aria-hidden="true"><span>%3$s</span></span><span class="ldmi-cover-text"><strong>%4$s</strong> %5$s</span></div>',
			$total && $part === $total ? ' is-complete' : '',
			esc_attr( '--ldmi-share:' . $pct . '%' ),
			esc_html( $total ? $pct . ' %' : '–' ),
			/* translators: 1: images with a second image, 2: all images. */
			esc_html( sprintf( __( '%1$d of %2$d', 'light-dark-mode-images' ), $part, $total ) ),
			esc_html( $label )
		);
	}

	/** Section intro: what happens, shown as two small screens, then the coverage of the latest posts. */
	public static function section() {
		$s     = self::get();
		$inv   = self::inventory();
		$modes = array(
			'light' => __( 'Light mode', 'light-dark-mode-images' ),
			'dark'  => __( 'Dark mode', 'light-dark-mode-images' ),
		);
		// Images of a real pair for the preview: the regular one in the base scheme, the second in the other.
		$photos = array();
		if ( $inv['pair'] ) {
			$photos[ $s['base'] ]                              = wp_get_attachment_image_url( $inv['pair'][0], 'medium' );
			$photos[ 'dark' === $s['base'] ? 'light' : 'dark' ] = wp_get_attachment_image_url( $inv['pair'][1], 'medium' );
		}
		echo '<div class="ldmi-intro" id="ldmi-settings">';
		echo '<p>' . esc_html__( 'Each post and each Image block can carry a second image for the other color scheme. Visitors whose system or browser is set to that scheme see the second image; everyone else sees the regular one.', 'light-dark-mode-images' ) . '</p>';
		echo '<div class="ldmi-preview-switch" role="group" aria-label="' . esc_attr__( 'Preview as', 'light-dark-mode-images' ) . '" hidden>';
		echo '<span class="ldmi-preview-label" aria-hidden="true">' . esc_html__( 'Preview as', 'light-dark-mode-images' ) . '</span>';
		foreach ( array(
			'system' => __( 'This device', 'light-dark-mode-images' ),
			'light'  => __( 'Light mode', 'light-dark-mode-images' ),
			'dark'   => __( 'Dark mode', 'light-dark-mode-images' ),
		) as $mode => $label ) {
			printf( '<button type="button" class="ldmi-preview-mode is-%1$s" data-mode="%1$s" aria-pressed="false"><span class="ldmi-preview-icon" aria-hidden="true"></span>%2$s</button>', esc_attr( $mode ), esc_html( $label ) );
		}
		echo '</div>';
		echo '<div class="ldmi-demo">';
		foreach ( $modes as $mode => $label ) {
			$which = $mode === $s['base'] ? 'regular' : 'second';
			echo '<figure class="ldmi-demo-screen is-' . esc_attr( $mode ) . '" data-mode="' . esc_attr( $mode ) . '"><span class="ldmi-demo-art" aria-hidden="true"><span class="ldmi-demo-bar"></span>';
			if ( empty( $photos[ $mode ] ) ) {
				echo '<span class="ldmi-demo-image is-' . esc_attr( $mode ) . '"><span></span><span></span><span></span></span>';
			} else {
				echo '<span class="ldmi-demo-image has-photo is-' . esc_attr( $mode ) . '"><img src="' . esc_url( $photos[ $mode ] ) . '" alt="" loading="lazy" decoding="async"></span>';
			}
			printf(
				'<span class="ldmi-demo-lines"></span></span><figcaption><strong>%1$s</strong> <span class="ldmi-demo-which">%2$s</span></figcaption></figure>',
				esc_html( $label ),
				esc_html( 'regular' === $which ? __( 'regular image', 'light-dark-mode-images' ) : __( 'second image', 'light-dark-mode-images' ) )
			);
		}
		echo '</div>';
		echo '<p class="ldmi-preview-status" aria-live="polite"></p>';
		if ( $inv['pair'] ) {
			/* translators: %s: post title. */
			echo '<p class="ldmi-demo-source">' . esc_html( sprintf( __( 'Images from: %s', 'light-dark-mode-images' ), get_the_title( $inv['pair'][2] ) ) ) . '</p>';
		}

		echo '<div class="ldmi-cover">';
		echo '<h3 class="ldmi-cover-title">' . esc_html__( 'Coverage', 'light-dark-mode-images' ) . '</h3>';
		if ( ! $inv['featured'] && ! $inv['blocks'] ) {
			echo '<p class="ldmi-cover-none">' . esc_html__( 'No published post or page has a featured image or an Image block yet.', 'light-dark-mode-images' ) . '</p>';
		} else {
			echo '<div class="ldmi-cover-grid">';
			self::cover_item( $inv['featured_alt'], $inv['featured'], __( 'featured images have a second image', 'light-dark-mode-images' ) );
			self::cover_item( $inv['blocks_alt'], $inv['blocks'], __( 'Image blocks have a second image', 'light-dark-mode-images' ) );
			echo '</div>';
			/* translators: %d: number of posts. */
			echo '<p class="ldmi-cover-note">' . esc_html( sprintf( _n( 'Counted in the latest %d published post or page.', 'Counted in the latest %d published posts and pages.', $inv['posts'], 'light-dark-mode-images' ), $inv['posts'] ) ) . '</p>';
			if ( $inv['missing'] ) {
				echo '<h4 class="ldmi-todo-title">' . esc_html__( 'Still without a second image', 'light-dark-mode-images' ) . '</h4>';
				echo '<ul class="ldmi-todo">';
				foreach ( $inv['missing'] as $item ) {
					$thumb = $item['thumb'] ? wp_get_attachment_image_url( $item['thumb'], 'thumbnail' ) : '';
					$what  = array();
					if ( $item['featured'] ) {
						$what[] = __( 'featured image', 'light-dark-mode-images' );
					}
					if ( $item['blocks'] ) {
						/* translators: %d: number of Image blocks. */
						$what[] = sprintf( _n( '%d Image block', '%d Image blocks', $item['blocks'], 'light-dark-mode-images' ), $item['blocks'] );
					}
					echo '<li class="ldmi-todo-item">';
					echo $thumb ? '<img class="ldmi-todo-thumb" src="' . esc_url( $thumb ) . '" alt="" loading="lazy" decoding="async">' : '<span class="ldmi-todo-thumb" aria-hidden="true"></span>';
					echo '<span class="ldmi-todo-text"><a href="' . esc_url( get_edit_post_link( $item['id'] ) ) . '">' . esc_html( get_the_title( $item['id'] ) ? get_the_title( $item['id'] ) : __( '(no title)', 'light-dark-mode-images' ) ) . '</a>';
					echo '<span class="ldmi-todo-chips">';
					foreach ( $what as $w ) {
						echo '<span class="ldmi-todo-chip">' . esc_html( $w ) . '</span>';
					}
					echo '</span></span></li>';
				}
				echo '</ul>';
			} else {
				echo '<p class="ldmi-cover-done"><svg viewBox="0 0 24 24" width="22" height="22" aria-hidden="true" focusable="false"><circle cx="12" cy="12" r="11" fill="#00a32a"/><path d="M7 12.5l3.2 3.2L17 9" fill="none" stroke="#fff" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"/></svg>' . esc_html__( 'Every featured image and Image block in these posts has a second image.', 'light-dark-mode-images' ) . '</p>';
			}
		}
		echo '</div>';
		echo '</div>';
	}

	/** Field: scheme of the regular images. */
	public static function field_base() {
		$s = self::get();
		echo '<fieldset class="ldmi-base"><legend class="screen-reader-text">' . esc_html__( 'Regular images are made for', 'light-dark-mode-images' ) . '</legend>';
		foreach ( array(
			'light' => __( 'Light mode (the second image is shown in dark mode)', 'light-dark-mode-images' ),
			'dark'  => __( 'Dark mode (the second image is shown in light mode)', 'light-dark-mode-images' ),
		) as $value => $label ) {
			printf(
				'<label class="ldmi-base-option is-%2$s"><input type="radio" name="%1$s[base]" value="%2$s" %3$s> <span class="ldmi-base-icon" aria-hidden="true"></span><span>%4$s</span></label>',
				esc_attr( self::OPTION ),
				esc_attr( $value ),
				checked( $s['base'], $value, false ),
				esc_html( $label )
			);
		}
		echo '</fieldset>';
	}

	/** Field: selector for a chosen dark mode. */
	public static function field_dark() {
		self::selector_field( 'dark_selector', 'html.dark, [data-theme="dark"]', array( 'html.dark', '[data-theme="dark"]', 'body.dark-mode', '.is-dark-theme' ) );
		echo '<p class="description">' . esc_html__( 'Only needed if the theme has its own light/dark switch. Enter the CSS selector that matches while the visitor has chosen dark mode. Leave empty to follow the system setting only.', 'light-dark-mode-images' ) . '</p>';
	}

	/** Field: selector for a chosen light mode. */
	public static function field_light() {
		self::selector_field( 'light_selector', 'html.light, [data-theme="light"]', array( 'html.light', '[data-theme="light"]', 'body.light-mode', '.is-light-theme' ) );
		echo '<p class="description">' . esc_html__( 'The matching selector for a chosen light mode. When neither selector matches, the system setting decides.', 'light-dark-mode-images' ) . '</p>';
	}

	/**
	 * Text input for a selector, with example selectors that common theme switches set.
	 *
	 * @param string   $key         Option key.
	 * @param string   $placeholder Example.
	 * @param string[] $examples    Selectors offered as one-click examples.
	 */
	private static function selector_field( $key, $placeholder, $examples ) {
		$s = self::get();
		printf(
			'<input type="text" class="regular-text code ldmi-selector" id="ldmi_%1$s" name="%2$s[%1$s]" value="%3$s" placeholder="%4$s" maxlength="200" aria-describedby="ldmi_%1$s_check">',
			esc_attr( $key ),
			esc_attr( self::OPTION ),
			esc_attr( $s[ $key ] ),
			esc_attr( $placeholder )
		);
		printf( '<span class="ldmi-check" id="ldmi_%s_check" aria-live="polite"></span>', esc_attr( $key ) );
		echo '<span class="ldmi-examples" hidden><span class="ldmi-examples-label">' . esc_html__( 'Common selectors:', 'light-dark-mode-images' ) . '</span>';
		foreach ( $examples as $example ) {
			printf(
				'<button type="button" class="button button-small ldmi-example" data-target="ldmi_%1$s" data-value="%2$s"><code>%3$s</code></button>',
				esc_attr( $key ),
				esc_attr( $example ),
				esc_html( $example )
			);
		}
		echo '</span>';
	}

	/**
	 * Settings link on the Plugins screen.
	 *
	 * @param array $links Existing links.
	 * @return array
	 */
	public static function action_link( $links ) {
		$links[] = '<a href="' . esc_url( admin_url( 'options-media.php#ldmi-settings' ) ) . '">' . esc_html__( 'Settings', 'light-dark-mode-images' ) . '</a>';
		return $links;
	}
}
