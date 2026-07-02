<?php
/**
 * Plugin Name: KEDS Admin Performance
 * Description: Removes costly admin dashboard widgets that trigger slow background requests.
 * Version: 1.0.0
 * Author: KEDS
 * Author URI: https://kingsdivinity.org
 */

defined( 'ABSPATH' ) || exit;

add_action(
	'wp_dashboard_setup',
	function () {
		$widgets = [
			'dashboard_primary',
			'learn_press_dashboard_order_statuses',
			'learn_press_dashboard_plugin_status',
			'woocommerce_dashboard_recent_reviews',
			'woocommerce_dashboard_status',
		];

		$contexts = [
			'normal',
			'side',
			'column3',
			'column4',
		];

		foreach ( $widgets as $widget ) {
			foreach ( $contexts as $context ) {
				remove_meta_box( $widget, 'dashboard', $context );
			}
		}
	},
	999
);
