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

	/** Section intro. */
	public static function section() {
		echo '<p>' . esc_html__( 'Each post and each Image block can carry a second image for the other color scheme. Visitors whose system or browser is set to that scheme see the second image; everyone else sees the regular one.', 'light-dark-mode-images' ) . '</p>';
	}

	/** Field: scheme of the regular images. */
	public static function field_base() {
		$s = self::get();
		echo '<fieldset>';
		foreach ( array(
			'light' => __( 'Light mode (the second image is shown in dark mode)', 'light-dark-mode-images' ),
			'dark'  => __( 'Dark mode (the second image is shown in light mode)', 'light-dark-mode-images' ),
		) as $value => $label ) {
			printf(
				'<label><input type="radio" name="%1$s[base]" value="%2$s" %3$s> %4$s</label><br>',
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
		self::selector_field( 'dark_selector', 'html.dark, [data-theme="dark"]' );
		echo '<p class="description">' . esc_html__( 'Only needed if the theme has its own light/dark switch. Enter the CSS selector that matches while the visitor has chosen dark mode. Leave empty to follow the system setting only.', 'light-dark-mode-images' ) . '</p>';
	}

	/** Field: selector for a chosen light mode. */
	public static function field_light() {
		self::selector_field( 'light_selector', 'html.light, [data-theme="light"]' );
		echo '<p class="description">' . esc_html__( 'The matching selector for a chosen light mode. When neither selector matches, the system setting decides.', 'light-dark-mode-images' ) . '</p>';
	}

	/**
	 * Text input for a selector.
	 *
	 * @param string $key         Option key.
	 * @param string $placeholder Example.
	 */
	private static function selector_field( $key, $placeholder ) {
		$s = self::get();
		printf(
			'<input type="text" class="regular-text code" id="ldmi_%1$s" name="%2$s[%1$s]" value="%3$s" placeholder="%4$s" maxlength="200">',
			esc_attr( $key ),
			esc_attr( self::OPTION ),
			esc_attr( $s[ $key ] ),
			esc_attr( $placeholder )
		);
	}

	/**
	 * Settings link on the Plugins screen.
	 *
	 * @param array $links Existing links.
	 * @return array
	 */
	public static function action_link( $links ) {
		$links[] = '<a href="' . esc_url( admin_url( 'options-media.php#ldmi_dark_selector' ) ) . '">' . esc_html__( 'Settings', 'light-dark-mode-images' ) . '</a>';
		return $links;
	}
}
