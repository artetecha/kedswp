<?php
/**
 * Plugin Name: KEDS Hide Read-Only Filesystem Notices
 * Description: Removes thim-core permission warnings about wp-content/plugins not being writable. The code tree is read-only by design on Upsun; plugins are managed through Composer.
 * Version: 1.0.0
 * Author: KEDS
 * Author URI: https://kingsdivinity.org
 */

defined( 'ABSPATH' ) || exit;

// Both notices are registered on thim_core_dashboard_init at the default
// priority, so unhooking at priority 0 runs before either of them fires.
add_action(
	'thim_core_dashboard_init',
	function () {
		if ( class_exists( 'Thim_Core_Admin' ) ) {
			remove_action( 'thim_core_dashboard_init', [ Thim_Core_Admin::instance(), 'notice_permission_uploads' ] );
		}

		if ( class_exists( 'Thim_Plugins_Manager' ) ) {
			remove_action( 'thim_core_dashboard_init', [ Thim_Plugins_Manager::instance(), 'notification' ] );
		}
	},
	0
);
