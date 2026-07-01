<?php

use LearnPress\Models\CourseModel;
use LearnPress\Models\UserItems\UserCourseModel;
use LearnPress\Models\UserItems\UserItemModel;

/**
 * Class LP_PMS_Order
 */
class LP_PMS_Order {
	public static $_instance;
	public static $_payment_method       = 'paid-memberships-pro';
	public static $_payment_method_title = '';
	public static $_key_lp_pmpro_level   = '_lp_pmpro_level'; // meta key for lp_order
	public static $_key_lp_pmpro_levels  = '_lp_pmpro_levels'; // meta key for lp_course
	public static $_loaded               = false;
	public static $_LIMIT_COURSES;
	public static $_LIMIT_ORDERS;
	public static $_CALL_CRON_JOB_AFTER_SECOND;
	public $_MBS_USER_OLD_LEVELS = array();

	public $redirect    = false;
	public $user_orders = array();

	public function __construct() {
		self::$_LIMIT_COURSES              = apply_filters( 'learn-press/pmspro/limit-courses', 10 );
		self::$_LIMIT_ORDERS               = apply_filters( 'learn-press/pmspro/limit-orders', 10 );
		self::$_CALL_CRON_JOB_AFTER_SECOND = apply_filters(
			'learn-press/pmspro/time-call-cron-job-after-second',
			5
		);

		add_filter( 'learn_press_display_payment_method_title', array( $this, 'display_payment_method_title' ), 10, 2 );

		// creat order membership success
		add_action(
			'pmpro_membership_post_membership_expiry',
			array( $this, 'membership_expire' ),
			10,
			2
		);
		add_action(
			'pmpro_before_change_membership_level',
			array( $this, 'before_update_lp_orders_when_change_membership_level' ),
			11,
			4
		);
		add_action(
			'pmpro_after_change_membership_level',
			array( $this, 'update_lp_orders_when_change_membership_level' ),
			11,
			3
		);
		add_filter(
			'learn-press/order/completed/update-user-item/another-case/bool',
			[ $this, 'check_need_handle_user_item' ],
			10,
			6
		);
		add_filter(
			'learn-press/order/completed/update-user-item/another-case',
			[ $this, 'handle_user_item' ],
			10,
			6
		);
	}


	public function display_payment_method_title( $title, $payment_method ) {
		if ( self::$_payment_method == $payment_method ) {
			return $this->get_payment_method_title();
		}

		return $title;
	}

	/**
	 * Get payment method title
	 *
	 * @return string
	 */
	public function get_payment_method_title(): string {
		if ( ! self::$_payment_method_title ) {
			self::$_payment_method_title = __(
				'Pay via <strong>Paid Memberships Pro</strong>',
				'learnpress-paid-membership-pro'
			);
		}

		return self::$_payment_method_title;
	}

	/**
	 * Create Lp order
	 *
	 * @param int $user_id
	 * @param int $level_id
	 *
	 * @return bool|int|WP_Error
	 */
	public function create_lp_order( $user_id = 0, $level_id = 0 ) {
		global $action;

		$action = 'no_editpost'; // learnpress\inc\custom-post-types\order.php search editpost
		$user   = learn_press_get_user( $user_id );

		// Check user, if Admin or User same is valid
		if ( current_user_can( 'administrator' ) ) {
			// Check user exists
			if ( ! $user ) {
				return new WP_Error( 'lp_pms_create_order', 'User not exists' );
			}
		}
		//Todo: Check why something didn't get current user_id
		/*elseif ( get_current_user_id() != $user_id ) { // Temporary not check
			return new WP_Error( 'lp_pms_create_order', 'Invalid User! You can\'t create LP Order' );
		}*/

		// create order
		$level      = pmpro_getLevel( $level_id );
		$level_cost = learn_press_pmpro_getLevelCost( $level, $user_id );
		$post_title = sprintf(
			__( 'Order on %s', 'learnpress-paid-membership-pro' ),
			current_time( 'l jS F Y h:i:s A' )
		);
		$order_data = array(
			'post_author' => $user_id,
			'post_parent' => '0',
			'post_type'   => LP_ORDER_CPT,
			'post_status' => LP_ORDER_PENDING_DB,
			'ping_status' => 'closed',
			'post_title'  => $post_title,
			'meta_input'  => array(
				'_user_id'                 => $user_id,
				'_created_via'             => 'lp_pms',
				'_payment_method'          => self::$_payment_method,
				'_payment_method_title'    => __( 'Memberships', 'learnpress-paid-membership-pro' ),
				'_order_total'             => $level_cost,
				'_checkout_email'          => $user->get_email(),
				self::$_key_lp_pmpro_level => $level_id,
			),
		);

		if ( isset( $_SESSION['wc_order_change_completed'] ) ) {
			/**
			 * @var WC_Order $_wc_order
			 */
			$_wc_order = $_SESSION['wc_order_change_completed'];

			$order_data['meta_input']['_woo_order_id']         = $_wc_order->get_id();
			$order_data['meta_input']['_payment_method']       = $_wc_order->get_payment_method();
			$order_data['meta_input']['_payment_method_title'] = 'Woocommerce: ' . $_wc_order->get_payment_method_title();

			unset( $_SESSION['wc_order_change_completed'] );
		}

		$order_id = wp_insert_post( $order_data );
		// End create order

		if ( $order_id instanceof WP_Error ) {
			return $order_id;
		}

		$params = array(
			'lp_order_id' => $order_id,
			'level_id'    => $level_id,
			'user_id'     => $user_id,
		);

		$this->handleAddItemsToLpOrderRemotePost( $params );

		return $order_id;
	}

	/**
	 * Run wp_remote_post add course to lp_order
	 *
	 * @param array $params
	 *
	 * @return void
	 * @author minhpd
	 * @version 1.0.0
	 * @sicne 4.0.2
	 */
	protected function handleAddItemsToLpOrderRemotePost( array $params ) {
		$bg = LP_PMS_Background_Single_Course::instance();
		$bg->data( $params )->dispatch();
	}

	/**
	 * Get total courses by MemberShip level
	 *
	 * @param int $level_id
	 *
	 * @return array|object|null
	 */
	public function get_total_courses_by_level( int $level_id = 0 ) {
		global $wpdb;
		$tabel_post     = $wpdb->posts;
		$tabel_postmeta = $wpdb->postmeta;

		$sql = $wpdb->prepare(
			"SELECT COUNT(p.ID) as total
			FROM $tabel_post AS p
			INNER JOIN $tabel_postmeta AS pm
			ON p.ID = pm.post_id
			WHERE pm.meta_key = %s
			AND pm.meta_value = %s
			AND p.post_type = %s
			AND p.post_status = 'publish'",
			self::$_key_lp_pmpro_levels,
			$level_id,
			LP_COURSE_CPT
		);

		$result = $wpdb->get_row( $sql );

		return $result;
	}

	/**
	 * Handle when membership expire
	 *
	 * @param int $user_id
	 * @param int $membership_id
	 *
	 * @since
	 * @author tungnx
	 */
	public function membership_expire( $user_id, $membership_id ) {
		// Get last order of user has level
		$result = LP_PMS_DB::getInstance()->getLastOrderMembershipOfUser( $user_id, $membership_id );

		if ( $result ) {
			$order_id = $result->post_id;
			$lp_order = learn_press_get_order( $order_id );
			if ( $lp_order ) {
				$lp_order->update_status( LP_ORDER_CANCELLED );
			}
		}
	}

	public function before_update_lp_orders_when_change_membership_level(
		$level_id = 0,
		$user_id = 0,
		$old_levels = array(),
		$cancel_level = 0
	) {
		if ( ! empty( $old_levels ) ) {
			$this->_MBS_USER_OLD_LEVELS = $old_levels;
		}
	}

	/**
	 * 1. Create LP order when subscription level membership (or via Woo) and delete old level if exits
	 * 2. Cancel level will delete LP order
	 *
	 * @param int $level_id
	 * @param int $user_id
	 * @param int $cancel_level
	 *
	 * @since 4.0.0
	 * @version 1.0.1
	 */
	public function update_lp_orders_when_change_membership_level( $level_id = 0, $user_id = 0, $cancel_level = 0 ) {
		ini_set( 'max_execution_time', HOUR_IN_SECONDS );
		if ( ! empty( $cancel_level ) ) { // Cancel level
			$this->cancel_lp_order( $user_id, $cancel_level );
		} elseif ( ! empty( $level_id ) && ! empty( $this->_MBS_USER_OLD_LEVELS ) && ! empty( $user_id ) ) {
			$group_id_of_level_new     = pmpro_get_group_id_for_level( $level_id );
			$group_of_level_new        = pmpro_get_level_group( $group_id_of_level_new );
			$allow_multiple_selections = (int) $group_of_level_new->allow_multiple_selections ?? 0;

			// Cancel orders old
			foreach ( $this->_MBS_USER_OLD_LEVELS as $membership_user_level ) {
				$group_id_of_level = pmpro_get_group_id_for_level( $membership_user_level->ID );

				// If level not in same group, skip
				if ( $group_id_of_level_new != $group_id_of_level ) {
					continue;
				} elseif ( $allow_multiple_selections ) {
					// If level same group and enable multiple levels in group, skip
					continue;
				}

				// Cancel lp order of user in level
				if ( isset( $membership_user_level->ID ) ) {
					$this->cancel_lp_order( $user_id, $membership_user_level->ID );
				}
			}

			// Create LP order and add items
			$order_id = $this->create_lp_order( $user_id, $level_id );
			if ( $order_id instanceof WP_Error ) {
				error_log( $order_id->get_error_message() );
			}
		} elseif ( ! empty( $level_id ) && ! empty( $user_id ) ) { // Create LP order and add items
			$order_id = $this->create_lp_order( $user_id, $level_id );

			if ( $order_id instanceof WP_Error ) {
				error_log( $order_id->get_error_message() );
			}
		} elseif ( ! empty( $user_id ) && ! empty( $this->_MBS_USER_OLD_LEVELS ) ) {
			// Admin cancel level of user
			if ( isset( $_REQUEST['membership_level'] )
				&& ( $_REQUEST['membership_level'] === '0' || $_REQUEST['membership_level'] == '' ) ) {
				foreach ( $this->_MBS_USER_OLD_LEVELS as $membership_user_level ) {
					if ( isset( $membership_user_level->ID ) ) {
						$this->cancel_lp_order( $user_id, $membership_user_level->ID );
					}
				}
			}
		}
		ini_set( 'max_execution_time', LearnPress::$time_limit_default_of_sever );
	}

	/**
	 * Set cancel lp order of user in level
	 *
	 * @param $user_id
	 * @param $cancel_level_id
	 *
	 * @return void
	 */
	public function cancel_lp_order( $user_id, $cancel_level_id ) {
		if ( empty( $user_id ) || empty( $cancel_level_id ) ) {
			return;
		}

		$lpOrderLast = LP_PMS_DB::getInstance()->getLastOrderMembershipOfUser( $user_id, $cancel_level_id );
		$lp_order    = learn_press_get_order( $lpOrderLast->post_id ?? 0 );
		if ( ! $lp_order ) {
			return;
		}

		$lp_order->update_status( LP_ORDER_CANCELLED );
	}

	/**
	 * Update list courses on Orders has level when save level
	 *
	 * @param array $lp_orders
	 * @param array $level_course_ids | get value from $_POST['_lp_pmpro_courses']
	 * @param int $level_id
	 */
	public function handleLevelChangeCourses( $lp_orders = [], $level_course_ids = [], $level_id = 0 ) {
		$params = array(
			'level_id'         => $level_id,
			'level_course_ids' => $level_course_ids,
			'lp_orders'        => array_values( $lp_orders ),
		);

		$this->handleAddItemsToLpOrderRemotePost( $params );
	}

	/**
	 * Check need handle user item when order status change to completed buy via PMS
	 *
	 * @param bool $bool
	 * @param $user_item_data
	 * @param LP_Order $order
	 * @param $item
	 * @param $courseModel
	 * @param $user_id
	 *
	 * @return boolean
	 * @since 4.1.2
	 * @version 1.0.0
	 */
	public function check_need_handle_user_item(
		$bool,
		$user_item_data,
		$order,
		$item,
		$courseModel,
		$user_id
	) {
		// Check order buy via PMS
		if ( $order->get_data( 'payment_method' ) === self::$_payment_method ) {
			$bool = true;
		}

		return $bool;
	}

	/**
	 * Handle user item when order status change to completed buy via PMS
	 *
	 * @param null $userCourseResponse
	 * @param $user_item_data
	 * @param LP_Order $order
	 * @param $item
	 * @param CourseModel $courseModel
	 * @param $user_id
	 *
	 * @return UserCourseModel|null
	 * @throws Exception
	 * @since 4.1.2
	 * @version 1.0.0
	 */
	public function handle_user_item(
		$userCourseResponse,
		$user_item_data,
		$order,
		$item,
		$courseModel,
		$user_id
	) {
		$option_keep_progress = LP_Settings::get_option(
			'pmpro_keep_course_progress',
			'keep'
		);
		$userCourseModel      = UserCourseModel::find( $user_id, $courseModel->get_id(), true );
		$user_item_data       = [
			'user_id'    => $user_id,
			'item_id'    => $courseModel->get_id(),
			'ref_id'     => $order->get_id(),
			'start_time' => gmdate( LP_Datetime::$format, time() ),
			'graduation' => UserItemModel::GRADUATION_IN_PROGRESS,
			'status'     => UserItemModel::STATUS_ENROLLED,
		];

		// Case user not enrolled course yet
		if ( ! $userCourseModel ) {
			// Create new
			$userCourseNew      = new UserCourseModel( $user_item_data );
			$userCourseResponse = $userCourseNew->save();
		} else { // Case user attend course
			// Reset progress
			if ( 'reset' === $option_keep_progress ) {
				$userCourseModel->delete();

				// Create new
				$userCourseNew      = new UserCourseModel( $user_item_data );
				$userCourseResponse = $userCourseNew->save();
			} elseif ( 'keep' === $option_keep_progress ) { // Keep progress
				$userCourseResponse = $userCourseModel;

				if ( $userCourseResponse->status === UserItemModel::STATUS_CANCEL ) {
					$userCourseResponse->ref_id     = $order->get_id();
					$userCourseResponse->start_time = $user_item_data['start_time'];
					$userCourseResponse->status     = UserItemModel::STATUS_ENROLLED;
					$userCourseResponse->graduation = UserItemModel::GRADUATION_IN_PROGRESS;
					$userCourseResponse->end_time   = null;
					$userCourseResponse->save();
				}
			}
		}

		return $userCourseResponse;
	}

	public static function getInstance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}
}

LP_PMS_Order::getInstance();
