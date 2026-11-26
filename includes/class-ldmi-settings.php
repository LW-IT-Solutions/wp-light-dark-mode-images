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
			)
		);
	}

	/** Section intro: what happens, shown as two small screens. */
	public static function section() {
		$s     = self::get();
		$modes = array(
			'light' => __( 'Light mode', 'light-dark-mode-images' ),
			'dark'  => __( 'Dark mode', 'light-dark-mode-images' ),
		);
		echo '<div class="ldmi-intro" id="ldmi-settings">';
		echo '<p>' . esc_html__( 'Each post and each Image block can carry a second image for the other color scheme. Visitors whose system or browser is set to that scheme see the second image; everyone else sees the regular one.', 'light-dark-mode-images' ) . '</p>';
		echo '<div class="ldmi-demo">';
		foreach ( $modes as $mode => $label ) {
			$which = $mode === $s['base'] ? 'regular' : 'second';
			printf(
				'<figure class="ldmi-demo-screen is-%1$s" data-mode="%1$s"><span class="ldmi-demo-art" aria-hidden="true"><span class="ldmi-demo-bar"></span><span class="ldmi-demo-image is-%2$s"><span></span><span></span><span></span></span><span class="ldmi-demo-lines"></span></span><figcaption><strong>%3$s</strong> <span class="ldmi-demo-which">%4$s</span></figcaption></figure>',
				esc_attr( $mode ),
				esc_attr( $mode ),
				esc_html( $label ),
				esc_html( 'regular' === $which ? __( 'regular image', 'light-dark-mode-images' ) : __( 'second image', 'light-dark-mode-images' ) )
			);
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
