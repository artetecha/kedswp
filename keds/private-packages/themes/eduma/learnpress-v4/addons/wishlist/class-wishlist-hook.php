<?php

use LearnPress\Models\CourseModel;
use LearnPress\Wishlist\TemplateHooks\CourseWishlistTemplate;

/**
 * Class LP_Wishlist_Hook
 *
 * Handle display wishlist with theme Eduma.
 * Version wishlist from 4.0.9 and later.
 *
 * @version 1.0.0
 * @since 5.8.3
 */
class LP_Wishlist_Hook {
	public static function instance() {
		static $instance = null;
		if ( is_null( $instance ) ) {
			$instance = new self();
		}

		return $instance;
	}

	private function __construct() {
		add_action( 'thim_after_course_info', array( $this, 'wishlist_button_on_single_course' ) );
		add_action( 'thim_inner_thumbnail_course', array( $this, 'wishlist_button_on_list_course' ) );
	}

	public function wishlist_button_on_single_course() {
		$courseModel = CourseModel::find( get_the_ID(), true );
		if ( $courseModel instanceof CourseModel ) {
			echo CourseWishlistTemplate::instance()->html_button_action( $courseModel, [ 'layout' => 'classic' ] );
		}
	}

	public function wishlist_button_on_list_course() {
		$courseModel = CourseModel::find( get_the_ID(), true );
		if ( $courseModel instanceof CourseModel ) {
			echo CourseWishlistTemplate::instance()->html_button_action( $courseModel, [ 'layout' => 'icon-only' ] );
		}
	}
}
