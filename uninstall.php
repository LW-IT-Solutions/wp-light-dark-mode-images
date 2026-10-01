<?php
/**
 * Removes the plugin's option and the second featured image references.
 * The image files themselves stay in the media library.
 *
 * @package LightDarkModeImages
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

delete_option( 'ldmi_settings' );
delete_post_meta_by_key( '_ldmi_alt_image_id' );
