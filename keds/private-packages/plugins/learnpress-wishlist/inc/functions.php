<?php
/**
 * LearnPress Wishlist Functions
 *
 * Define common functions for both front-end and back-end
 *
 * @author   ThimPress
 * @package  LearnPress/Wishlist/Functions
 * @version  3.0.0
 */

// Prevent loading this file directly
defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'learn_press_course_wishlist_template' ) ) {
	/**
	 * Get wishlist template.
	 *
	 * @param string $name
	 * @param array $args
	 * @deprecated 4.0.5
	 */
	function learn_press_course_wishlist_template( $name, $args = [] ) {
		_deprecated_function( __FUNCTION__, '4.0.5' );
		return;
		//_deprecated_function( __FUNCTION__, '4.0.5', 'LP_Addon_Wishlist_Preload::$addon->get_template' );
		//LP_Addon_Wishlist_Preload::$addon->get_template( $name, $args );
		//learn_press_get_template( $name, $args, learn_press_template_path() . '/addons/wishlist/', LP_ADDON_WISHLIST_TEMPLATE );
	}
}

if ( ! function_exists( 'learn_press_wishlist_get_template' ) ) {
	/**
	 * Get template.
	 *
	 * @param      $name
	 * @param null $args
	 * @deprecated 4.0.5
	 */
	function learn_press_wishlist_get_template( $name, $args = null ) {
		_deprecated_function( __FUNCTION__, '4.0.5' );
		return;
		//_deprecated_function( __FUNCTION__, '4.0.5', 'LP_Addon_Wishlist_Preload::$addon->get_template' );
		//learn_press_get_template( $name, $args, learn_press_template_path() . '/addons/wishlist/', LP_ADDON_WISHLIST_PATH . '/templates/' );
	}
}

// Theme ivy-school is using this hook, override file user-wishlist.php
add_action( 'learn_press_wishlist_loop_item_title', 'learn_press_wishlist_loop_item_title', 5 );

if ( ! function_exists( 'learn_press_wishlist_loop_item_title' ) ) {
	/**
	 * Loop item title.
	 *
	 * Theme ivy-school is using this function.
	 */
	function learn_press_wishlist_loop_item_title() {
		LP_Addon_Wishlist_Preload::$addon->get_template( 'loop/title.php' );
	}
}

if ( ! function_exists( 'learn_press_user_wishlist_has_course' ) ) {
	/**
	 * Check user has course in wishlist.
	 *
	 * @param null $course_id
	 * @param null $user_id
	 *
	 * @return bool
	 * @using theme coaching is using
	 */
	function learn_press_user_wishlist_has_course( $course_id = null, $user_id = null ) {
		if ( ! $course_id ) {
			$course_id = get_the_ID();
		}

		if ( ! $user_id ) {
			$user_id = get_current_user_id();
		}

		$wish_list = LP_Addon_Wishlist::get_courses_wishlist( $user_id );

		return in_array( $course_id, $wish_list );
	}
}
