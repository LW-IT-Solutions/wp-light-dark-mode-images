<?php
/**
 * Plugin Name: Light and Dark Mode Images
 * Description: Add a second version of the featured image and of Image blocks for the other color scheme. Visitors get the version that matches their light or dark mode.
 * Version: 0.1.0
 * Requires at least: 6.5
 * Requires PHP: 7.4
 * Author: LW IT Solutions
 * Author URI: https://www.lukaswojcik.com/
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: light-dark-mode-images
 *
 * @package LightDarkModeImages
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'LDMI_VERSION', '0.1.0' );
define( 'LDMI_FILE', __FILE__ );

require_once __DIR__ . '/includes/class-ldmi-settings.php';
require_once __DIR__ . '/includes/class-ldmi-output.php';
require_once __DIR__ . '/includes/class-ldmi-editor.php';

LDMI_Settings::init();
LDMI_Output::init();
LDMI_Editor::init();
