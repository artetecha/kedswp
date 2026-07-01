<?php

use LearnPress\Models\CourseModel;
use LearnPress\Models\UserItems\UserItemModel;
use LearnPress\Models\UserItems\UserCourseModel;
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'LP_PMS_Background_Single_Course' ) ) {
	/**
	 * Class LP_PMS_Background_Single_Course
	 *
	 * Single to run not schedule, run one time and done when be call
	 *
	 * @version 1.0.2
	 * @since 4.0.2
	 * @author minhpd
	 */
	class LP_PMS_Background_Single_Course extends LP_Async_Request {
		protected $prefix = 'lp_pms';
		protected $action = 'create_lp_order_when_change_membership';
		protected static $instance;

		protected function handle() {
			ini_set( 'max_execution_time', 0 );
			try {
				$params = array(
					'level_id' => LP_Request::get_param( 'level_id', 0, 'int', 'post' ),
				);

				if ( ! empty( $_POST['level_course_ids'] ) ) {
					$params['lp_orders']        = LP_Request::get_param( 'lp_orders', [], 'text', 'post' );
					$params['level_course_ids'] = LP_Request::get_param( 'level_course_ids', [], 'text', 'post' );

					$this->handleLevelChangeCourses( $params );
				} else {
					$params['user_id']     = LP_Request::get_param( 'user_id', 0, 'int', 'post' );
					$params['lp_order_id'] = LP_Request::get_param( 'lp_order_id', 0, 'int', 'post' );

					$this->handleAddItemsToLpOrder( $params );
				}
			} catch ( Throwable $e ) {
				LP_Debug::error_log( $e );
			}
			ini_set( 'max_execution_time', LearnPress::$time_limit_default_of_sever );
		}

		/**
		 * handle add course to lp_order
		 *
		 * @param array $params
		 *
		 * @throws Exception
		 * @version 1.0.1
		 * @since 4.0.0
		 */
		protected function handleAddItemsToLpOrder( array $params ) {
			$lp_order_id  = $params['lp_order_id'] ?? 0;
			$pms_level_id = $params['level_id'] ?? 0;
			if ( empty( $lp_order_id ) || empty( $pms_level_id ) ) {
				return;
			}

			$lp_order = learn_press_get_order( $lp_order_id );
			if ( ! $lp_order ) {
				return;
			}

			$courses    = LP_PMS_DB::getInstance()->getCoursesByLevel( $pms_level_id );
			$course_ids = LP_Database::get_values_by_key( $courses );

			foreach ( $course_ids as $course_id ) {
				$course = CourseModel::find( $course_id, true );
				if ( ! $course ) {
					continue;
				}

				$item = array(
					'item_id'         => $course_id,
					'order_item_name' => $course->get_title(),
				);

				if ( ! $course->is_in_stock() && ! $course->has_no_enroll_requirement() ) {
					$course_out_stock[] = $course_id;
					$item['quantity']   = 0;
				}

				$lp_order->add_item( $item );
			}

			$lp_order->set_status( LP_ORDER_COMPLETED );
			$lp_order->save();

			$value_course_out_stock = ! empty( $course_out_stock ) ? implode( ',', $course_out_stock ) : '';
			update_post_meta( $lp_order_id, '_lp_course_out_stock', $value_course_out_stock );
		}

		/**
		 * Change courses on level
		 *
		 * @param array $params
		 *
		 * @throws Exception
		 * @since 4.0.2
		 * @version 1.0.4
		 */
		protected function handleLevelChangeCourses( array $params ) {
			$level_course_ids = $params['level_course_ids'];
			$lp_orders        = $params['lp_orders'];

			foreach ( $lp_orders as $lp_order_data ) {
				$order_id = absint( $lp_order_data['order_id'] ?? 0 );
				$user_id  = absint( $lp_order_data['user_id'] ?? 0 );
				if ( empty( $order_id ) || empty( $user_id ) ) {
					continue;
				}

				$lp_order = learn_press_get_order( $order_id );
				if ( ! $lp_order ) {
					continue;
				}

				// Get course ids on LP order
				$order_courses_rs = LP_PMS_DB::getInstance()->getCourseIdsOnLpOrder( $order_id );
				$order_courses    = [];
				foreach ( $order_courses_rs as $order_course ) {
					$order_courses[ $order_course->item_id ] = $order_course->order_item_id;
				}

				$order_course_ids  = array_keys( $order_courses );
				$remove_course_ids = array_diff( $order_course_ids, $level_course_ids );
				$add_course_ids    = array_diff( $level_course_ids, $order_course_ids );

				if ( count( $remove_course_ids ) > 0 ) {
					foreach ( $remove_course_ids as $course_id ) {
						// Delete courses on learnpress_order_items
						$lp_order->remove_item( $order_courses[ $course_id ] ?? 0 );

						// Delete course item on learnpress_user_items if exists
						$userCourseItem = UserItemModel::find_user_item(
							$user_id,
							$course_id,
							LP_COURSE_CPT,
							$order_id,
							LP_ORDER_CPT,
							true
						);
						if ( $userCourseItem instanceof UserItemModel ) {
							$userCourseItem->delete();
						}
					}
				}

				// Add courses to Order
				if ( count( $add_course_ids ) > 0 ) {
					foreach ( $add_course_ids as $course_id ) {
						$course_id = absint( $course_id );
						$course    = CourseModel::find( $course_id, true );
						if ( ! $course ) {
							continue;
						}

						$item = array(
							'item_id'         => $course_id,
							'order_item_name' => $course->get_title(),
						);

						// Add course item on learnpress_order_items
						$lp_order->add_item( $item );

						/**
						 * Add course item on learnpress_user_items
						 *
						 * If exists user_item then skip
						 * else create new user_item
						 */
						if ( $lp_order->get_status() === LP_ORDER_COMPLETED ) {
							$userCourseModel = UserCourseModel::find( $user_id, $course_id, true );
							if ( $userCourseModel instanceof UserCourseModel ) {
								return;
							}

							// Check option auto enroll enabled
							$auto_enroll = LP_Settings::is_auto_start_course();
							$status      = UserItemModel::STATUS_ENROLLED;
							if ( ! $auto_enroll ) {
								$status = UserItemModel::STATUS_PURCHASED;
							}

							$user_item_data = [
								'user_id'    => $user_id,
								'item_id'    => $course_id,
								'ref_id'     => $order_id,
								'status'     => $status,
								'start_time' => gmdate( LP_Datetime::$format, time() ),
								'graduation' => UserItemModel::GRADUATION_IN_PROGRESS,
							];
							$userCourseNew  = new UserCourseModel( $user_item_data );
							$userCourseNew->save();
							do_action( 'learnpress/user/course-enrolled', $order_id, $user_item_data['item_id'], $user_item_data['user_id'] );
						}
					}
				}
			}
		}

		/**
		 * @return LP_PMS_Background_Single_Course
		 */
		public static function instance(): self {
			if ( is_null( self::$instance ) ) {
				self::$instance = new self();
			}

			return self::$instance;
		}
	}

	// Must run instance to register ajax.
	LP_PMS_Background_Single_Course::instance();
}
