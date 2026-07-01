<?php

namespace LP_PMS;

use LearnPress\Helpers\Singleton;
use LearnPress\Helpers\Template;
use LearnPress\Models\CourseModel;
use LearnPress\Models\UserItems\UserCourseModel;
use LearnPress\Models\UserItems\UserItemModel;
use LearnPress\Models\UserModel;
use LP_Addon_Paid_Memberships_Pro_Preload;
use LP_Settings;

/**
 * Class Hook
 *
 * Handle hook of plugin.
 * @since 4.0.7
 * @version 1.0.1
 */
class Hook {
	use Singleton;

	public function init() {
		// Hook display button buy via pms
		add_filter( 'learn-press/course/html-button-purchase', array( $this, 'html_btn_buy_member' ), 11, 3 );
		add_filter( 'learn-press/course/html-button-enroll', array( $this, 'html_btn_buy_member' ), 11, 3 );

		add_filter( 'learn_press/order_item_name', array( $this, 'name_course_order' ), 10, 3 );
		add_filter( 'learn_pres_pmpro_course_header_level', array( $this, 'name_course_membership_checkout' ), 10, 3 );
		add_filter( 'learn-press/order/user-course-data', array( $this, 'check_auto_enroll_course' ), 10, 4 );
	}

	/**
	 * Get html button buy membership.
	 *
	 * @param array $section
	 * @param CourseModel $course
	 * @param UserModel|false $user
	 *
	 * @return array
	 * @since 4.0.7
	 * @version 1.0.1
	 */
	public function html_btn_buy_member( $section, $course, $user ) {
		$lp_addon = LP_Addon_Paid_Memberships_Pro_Preload::$addon;
		$user_id  = 0;
		if ( $user instanceof UserModel ) {
			$user_id = $user->get_id();
		}

		// Check course has membership level
		$levels = $lp_addon->get_levels_of_course( $course );
		if ( empty( $levels ) || $levels[0] === '' ) {
			return $section;
		}

		// Check user has membership level
		$user_is_member = pmpro_hasMembershipLevel( $levels, $user_id );
		if ( $user_is_member ) {
			return $section;
		}

		/**
		 * Check course has price and purchased
		 * For case button enroll display, same case with button course free.
		 */
		if ( ! $course->is_free() ) {
			$userCourseModel = UserCourseModel::find( $user_id, $course->get_id(), true );
			if ( $user_id > 0 && $userCourseModel && $userCourseModel->has_purchased() ) {
				return $section;
			}
		}

		$levels_page_id = $lp_addon->get_pms_levels_page_id();
		$link_pms       = add_query_arg(
			'course_id',
			$course->get_id(),
			get_the_permalink( $levels_page_id )
		);

		if ( $lp_addon->is_only_buy_via_pms() ) {
			$section['btn'] = '';
		}

		$html_btn_pms = sprintf(
			'<a class="btn-buy-via-member-ship" href="%s">%s</a>',
			$link_pms,
			sprintf(
				'<button type="button" class="lp-button">%s</button>',
				__( 'Buy Membership', 'learnpress-paid-membership-pro' )
			)
		);

		return Template::insert_value_to_position_array( $section, 'after', 'btn', 'btn_pms', $html_btn_pms );
	}

	/**
	 * Display label course full students.
	 *
	 * @param $name
	 * @param $item
	 * @param $order
	 *
	 * @return mixed|string
	 * @since 4.0.6
	 * @version 1.0.0
	 */
	public function name_course_order( $name, $item, $order ) {
		$course_id                   = $item['course_id'] ?? '';
		$order_id                    = $order->get_id();
		$course_ids_out_of_stock     = [];
		$key                         = '_lp_course_out_stock';
		$course_ids_out_of_stock_str = get_post_meta( $order_id, $key, true );
		if ( ! empty( $course_ids_out_of_stock_str ) ) {
			$course_ids_out_of_stock = explode( ',', $course_ids_out_of_stock_str );
		}

		if ( empty( $course_ids_out_of_stock ) ) {
			return $name;
		}

		if ( in_array( $course_id, $course_ids_out_of_stock ) ) {
			$name = sprintf( '%s - %s', $name, esc_html__( 'The course is full of students.', 'learnpress' ) );
		}

		return $name;
	}

	/**
	 * Show out of stock course in PMS checkout page.
	 *
	 * @param $link
	 * @param $course_item
	 * @param $key
	 *
	 * @return mixed|string
	 * @since 4.0.6
	 * @version 1.0.1
	 */
	public function name_course_membership_checkout( $link, $course_item, $key ) {
		$course_id = $key['id'];
		$course    = CourseModel::find( $course_id, true );
		$user_id   = get_current_user_id();

		$userCourse = UserCourseModel::find( $user_id, $course_id, true );
		if ( ! $userCourse && ! $course->is_in_stock() && ! $course->has_no_enroll_requirement() ) {
			$link = sprintf(
				'<td class="list-main item-td">%s - %s</td>',
				wp_kses_post( $course_item ),
				esc_html__( 'The course is full of students.', 'learnpress' )
			);
		}

		return $link;
	}

	/**
	 * Check if course is auto enroll.
	 *
	 * @param $user_course_data
	 * @param $order
	 * @param $item
	 * @param $courseModel
	 *
	 * @return array
	 * @since 4.0.9
	 * @version 1.0.0
	 */
	public function check_auto_enroll_course( $user_course_data, $order, $item, $courseModel ): array {
		$lp_addon    = LP_Addon_Paid_Memberships_Pro_Preload::$addon;
		$auto_enroll = LP_Settings::is_auto_start_course();
		if ( $auto_enroll
			|| ( $courseModel->is_free() && ! $lp_addon->is_only_buy_via_pms() ) ) {
			$user_course_data['status']     = UserItemModel::STATUS_ENROLLED;
			$user_course_data['graduation'] = UserItemModel::GRADUATION_IN_PROGRESS;
		} else {
			$user_course_data['status']     = UserItemModel::STATUS_PURCHASED;
			$user_course_data['graduation'] = '';
		}

		return $user_course_data;
	}
}
