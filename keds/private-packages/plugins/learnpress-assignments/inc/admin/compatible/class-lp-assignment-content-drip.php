<?php
/**
 * Class LP_Assignment_Content_Drip.
 *
 * @author  ThimPress
 * @package LearnPress/Assignments/Classes
 * @version 3.0.0
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit();

if ( ! class_exists( 'LP_Assignment_Content_Drip' ) ) {

	/**
	 * Class LP_Assignment_Content_Drip
	 */
	class LP_Assignment_Content_Drip {

		/**
		 * LP_Assignment_Content_Drip constructor.
		 */
		public function __construct() {

			if ( ! class_exists( 'LP_Addon_Content_Drip_Preload' ) ) {
				return;
			}

			add_action( 'learn-press/content-drip/maybe-restrict-content', array( $this, 'restrict_content' ) );
		}

		/**
		 * Restrict assignment content.
		 */
		public function restrict_content() {
			global $wp_filter;

			foreach ( array( '', 'before-', 'after-' ) as $prefix ) {
				if ( isset( $wp_filter[ "learn-press/{$prefix}content-item-summary/lp_assignment" ] ) ) {
					unset( $wp_filter[ "learn-press/{$prefix}content-item-summary/lp_assignment" ] );
				}
			}

			$load = LP_Addon_Content_Drip::instance();
			add_action( 'learn-press/content-item-summary/lp_assignment', array( $load, 'filter_item_content' ), - 10 );
		}
	}
}

new LP_Assignment_Content_Drip();
