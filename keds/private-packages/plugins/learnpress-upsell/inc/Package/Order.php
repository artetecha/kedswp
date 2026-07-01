<?php

namespace LearnPress\Upsell\Package;
use Exception;
use LearnPress\Models\UserItems\UserCourseModel;
use LP_User_Factory;

defined( 'ABSPATH' ) || exit;

/**
 * Class Order
 * Handle order for package
 *
 * @since 4.0.5
 * @version 1.0.1
 */
class Order extends LP_User_Factory {
	/**
	 * Init hooks.
	 * Hook 'lp/order-completed/update/user-item' need run before Payment call Order completed
	 * Ex: @uses LP_Addon_Stripe_Payment::listen_callback_stripe_return_payment_intent,
	 * @uses LP_Addon_Stripe_Payment::listen_callback_stripe_page
	 *
	 */
	public static function init() {
		add_action( 'lp/order-completed/update/user-item', array( __CLASS__, 'lp_order_has_just_completed' ), 10, 3 );
		add_action( 'lp/order-pending/update/user-item', array( __CLASS__, 'lp_order_has_just_pending' ), 10, 3 );
	}

	/**
	 * Handle package when order is completed.
	 *
	 * @param $item
	 * @param $order
	 * @param $user
	 *
	 * @return void
	 * @throws Exception
	 */
	public static function lp_order_has_just_completed( $item, $order, $user ) {
		if ( ! isset( $item['item_type'] ) || $item['item_type'] != LP_PACKAGE_CPT ) {
			return;
		}

		$package_id = $item['item_id'] ?? 0;
		if ( ! $package_id ) {
			return;
		}

		$user_id    = $user->get_id();
		$package    = new Package( $package_id );
		$course_ids = $package->get_course_list();

		foreach ( $course_ids as $course_id ) {
			$item_course = [ 'item_id' => $course_id ];

			// Check order_id of user_item current must < new order_id
			$userCourse = UserCourseModel::find( $user_id, $course_id, true );
			if ( $user_id && $userCourse && $userCourse->ref_id > $order->get_id() ) {
				continue;
			} elseif ( ! $user_id ) {
				$userCourseGuest = self::get_user_course_guest( $course_id, $order->get_user_email() );
				if ( $userCourseGuest && $userCourseGuest->ref_id > $order->get_id() ) {
					continue;
				}
			}

			if ( $order->is_manual() ) {
				self::handle_item_manual_order_completed( $order, $user, $item_course );
			} else {
				self::handle_item_order_completed( $order, $user, $item_course );
			}
		}
	}

	/**
	 * Handle package when order is pending.
	 *
	 * @param $item
	 * @param $order
	 * @param $user
	 *
	 * @return void
	 * @throws Exception
	 */
	public static function lp_order_has_just_pending( $item, $order, $user ) {
		if ( ! isset( $item['item_type'] ) || $item['item_type'] != LP_PACKAGE_CPT ) {
			return;
		}

		$user_id = 0;
		if ( $user ) {
			$user_id = $user->get_id();
		}

		$package_id = $item['item_id'] ?? 0;
		if ( ! $package_id ) {
			return;
		}

		$package    = new Package( $package_id );
		$course_ids = $package->get_course_list();

		foreach ( $course_ids as $course_id ) {
			$userCourse = UserCourseModel::find( $user_id, $course_id, true );
			if ( ! $userCourse || $userCourse->ref_id != $order->get_id() ) {
				continue;
			}

			$userCourse->status = LP_USER_COURSE_CANCEL;
			$userCourse->save();
		}
	}
}
