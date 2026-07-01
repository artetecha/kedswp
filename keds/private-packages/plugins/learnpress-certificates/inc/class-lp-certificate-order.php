<?php

use LearnPress\Certificate\Models\UserCertificateModel;
use LearnPress\Models\UserItems\UserCourseModel;
use LearnPress\Models\UserItems\UserItemModel;

/**
 * Class LP_Certificate_Order
 *
 * @author  tungnx
 * @version 1.0
 * @since   3.1.4
 */
class LP_Certificate_Order {

	protected static $_instance;

	public static function getInstance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	protected function __construct() {
		add_action( 'lp/order-completed/update/user-item', array( $this, 'lp_user_cer_update' ), 10, 3 );
		add_action( 'lp/order-pending/update/user-item', array( $this, 'lp_user_cer_update' ), 10, 3 );
		// add_action( 'learn-press/checkout/oder_item_name', array( $this, 'lp_order_cert_item_name' ), 11, 3 );
		// add order meta_data is not course
		add_action( 'learn-press/added-order-item-data', array( $this, 'lp_cert_add_order_meta' ), 10, 3 );

		add_filter( 'learn-press/order-item-not-course', array( $this, 'lp_order_cert_item' ), 10, 1 );
		add_filter(
			'learn-press/order-item-not-course-id',
			array(
				$this,
				'lp_order_cert_item_link_not_course_id',
			),
			10,
			2
		);
		add_filter( 'learn_press/order_detail_item_link', array( $this, 'lp_order_cert_item_link' ), 10, 2 );
		add_filter( 'learn-press/order-item-link', array( $this, 'lp_order_cert_item_link' ), 10, 2 );
		add_filter( 'learn-press/order-received-item-link', array( $this, 'lp_order_cert_item_link' ), 10, 2 );
		add_filter( 'learn-press/order/item-visible', array( $this, 'lp_cert_frontend_item_visible' ), 10, 2 );

		// edit: minhpd : 15-1-2022;
		// add type item can purchase
		add_filter(
			'learn-press/purchase/item-types/can-purchase',
			array(
				$this,
				'lp_cert_add_item_can_purchase',
			),
			10,
			1
		);

		// add item meta order with item is not course
		add_filter( 'learnpress/order/add-item/item_type_lp_cert', array( $this, 'lp_cert_order_add_item' ), 10, 1 );

		// Delete user certificate when delete order has certificate
		add_action( 'learn-press/order/before-delete', [ $this, 'delete_user_certificate' ], 10, 2 );
	}

	/**
	 * @param array $items
	 */
	public function lp_cert_add_item_can_purchase( $items ) {

		$items[] = LP_ADDON_CERTIFICATES_CERT_CPT;

		return $items;
	}

	/**
	 * @param array $items : item meta order
	 */
	public function lp_cert_order_add_item( $item ) {
		if ( get_post_type( $item['item_id'] ) == LP_ADDON_CERTIFICATES_CERT_CPT ) {
			$price_cert              = get_post_meta( $item['item_id'], '_lp_certificate_price', true );
			$item['quantity']        = absint( $item['quantity'] );
			$subtotal                = $price_cert * absint( $item['quantity'] );
			$item['item_type']       = get_post_type( $item['item_id'] );
			$item['subtotal']        = $subtotal;
			$item['total']           = $subtotal;
			$item['order_item_name'] = sprintf( '%s %s', __( 'Certificate:', 'learnpress-certificates' ), get_the_title( $item['item_id'] ) );
		}

		return $item;
	}

	/**
	 * @param int $item_id
	 * @param array $item
	 * @param int $order_id
	 *
	 * @return void
	 */
	public function lp_cert_add_order_meta( $item_id = 0, $item = array(), $order_id = 0 ) {
		if ( get_post_type( $item['item_id'] ) == LP_ADDON_CERTIFICATES_CERT_CPT ) {
			if ( isset( $item['course_id'] ) ) {
				learn_press_add_order_item_meta( $item_id, '_lp_cert_id', $item['item_id'] );
				learn_press_add_order_item_meta( $item_id, '_lp_course_id_of_cert', $item['course_id'] );
			}
		}
	}

	/**
	 * Add info certificate to table learnpress_user_items && learnpress_user_itemmeta
	 * Hook use from LP v4.2.6.5
	 *
	 * @param array $item
	 * @param LP_Order $lp_order
	 * @param LP_User $user
	 *
	 * @since 4.0.9
	 * @version 1.0.1
	 */
	public function lp_user_cer_update( array $item, $lp_order, $user ) {
		try {
			$certificate_id = $item['_lp_cert_id'] ?? 0;
			if ( empty( $certificate_id ) ) {
				return;
			}

			$course_id = $item['_lp_course_id_of_cert'] ?? 0;
			if ( empty( $course_id ) ) {
				return;
			}

			$user_id         = $user->get_id();
			$userCourseModel = UserCourseModel::find( $user->get_id(), $course_id, true );
			if ( ! $userCourseModel || $user_id === 0 ) {
				return;
			}

			// Check exists certificate bought
			$userCertificateModel = UserCertificateModel::find(
				$user->get_id(),
				$certificate_id,
				$userCourseModel,
				true
			);

			if ( ! $userCertificateModel ) {
				// Insert data to table learnpress_user_items
				$userCertificateNew            = new UserCertificateModel();
				$userCertificateNew->user_id   = $user->get_id();
				$userCertificateNew->item_id   = $certificate_id;
				$userCertificateNew->status    = $lp_order->get_status();
				$userCertificateNew->ref_id    = $lp_order->get_id();
				$userCertificateNew->ref_type  = LP_ORDER_CPT;
				$userCertificateNew->parent_id = $userCourseModel->get_user_item_id();
				$userCertificateNew->save();
			} else {
				// If exists certificate bought before, update status and lp order id new
				if ( $lp_order->get_id() !== $userCertificateModel->ref_id
					&& $userCertificateModel->ref_type === LP_ORDER_CPT ) {
					$userCertificateModel->ref_id = $lp_order->get_id();
				}

				$userCertificateModel->status = $lp_order->get_status();
				$userCertificateModel->save();
			}
		} catch ( Throwable $e ) {
			error_log( __METHOD__ . ': ' . $e->getMessage() );
		}
	}

	/**
	 * Template order certificate item
	 *
	 * @param array $item
	 *
	 * @return void
	 */
	public function lp_order_cert_item( $item = array() ) {
		extract( array( 'item' => $item ) );
		include_once LP_ADDON_CERTIFICATES_PATH . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR . 'order' . DIRECTORY_SEPARATOR . 'order-item.php';
	}

	/**
	 * Link certificate item LP Order if not meta_key _course_id
	 *
	 * @param string $link
	 * @param array $item
	 *
	 * @return string
	 */
	public function lp_order_cert_item_link_not_course_id( $link, $item ) {
		$user = wp_get_current_user();

		if ( ! $user ) {
			return $link;
		}

		if ( isset( $item['_lp_cert_id'] ) && isset( $item['_lp_course_id_of_cert'] ) ) {
			$edit_post_link = get_edit_post_link( $item['_lp_cert_id'] );
			$cert_title     = get_the_title( $item['_lp_cert_id'] );
			$course_title   = get_the_title( $item['_lp_course_id_of_cert'] );

			if ( empty( $edit_post_link ) ) {
				$edit_post_link = '#';
			}

			if ( ! is_admin() ) {
				$edit_post_link = get_permalink( $item['_lp_course_id_of_cert'] );

				if ( empty( $edit_post_link ) ) {
					$edit_post_link = '#';
				}
			}

			$title = sprintf( '%s: %s - %s', __( 'Certificate', 'learnpress-certificates' ), $cert_title, $course_title );
			$link  = '<a href="' . $edit_post_link . '">' . $title . '</a>';
		}

		// For old version < 3.1.4
		if ( isset( $item['learnpress_certificate_bought'] ) && get_post_type( $item['learnpress_certificate_bought'] ) === LP_ADDON_CERTIFICATES_CERT_CPT ) {
			$edit_post_link = get_edit_post_link( $item['learnpress_certificate_bought'] );
			$title          = sprintf( '%s: %s', __( 'Certificate', 'learnpress-certificates' ), get_the_title( $item['learnpress_certificate_bought'] ) );
			$link           = '<a href="' . $edit_post_link . '">' . $title . '</a>';
		}

		return $link;
	}

	/**
	 * Link certificate item LP Order
	 *
	 * @param string $link
	 * @param array $item
	 *
	 * @return string
	 */
	public function lp_order_cert_item_link( $link, $item ) {
		if ( isset( $item['_lp_cert_id'] ) ) {
			$edit_post_link = get_edit_post_link( $item['_lp_cert_id'] );
			$cert_title     = get_the_title( $item['_lp_cert_id'] );
			$course_title   = get_the_title( $item['course_id'] );
			$title          = sprintf( '%s %s - %s %s', __( 'Certificate:', 'learnpress-certificates' ), $cert_title, __( 'Course:', 'learnpress-certificates' ), $course_title );
			$link           = '<a href="' . $edit_post_link . '">' . $title . '</a>';
		}

		// For old version < 3.1.4
		if ( isset( $item['learnpress_certificate_bought'] ) && get_post_type( $item['learnpress_certificate_bought'] ) === LP_ADDON_CERTIFICATES_CERT_CPT ) {
			$edit_post_link = get_edit_post_link( $item['learnpress_certificate_bought'] );
			$title          = sprintf( '%s %s - %s %s', __( 'Certificate:', 'learnpress-certificates' ), get_the_title( $item['learnpress_certificate_bought'] ), __( 'Course:', 'learnpress-certificates' ), get_the_title( $item['course_id'] ) );
			$link           = '<a href="' . $edit_post_link . '">' . $title . '</a>';
		}

		return $link;
	}

	public function lp_cert_frontend_item_visible( $return, $item ) {
		if ( isset( $item['learnpress_certificate_bought'] ) && get_post_type( $item['learnpress_certificate_bought'] ) === LP_ADDON_CERTIFICATES_CERT_CPT ) {
			return false;
		}

		return $return;
	}

	/**
	 * Delete user item certificate when delete lp order has certificate
	 *
	 * @param LP_Order $lp_order
	 * @param $user_id
	 *
	 * @return void
	 * @since 4.0.9
	 * @version 1.0.0
	 */
	public function delete_user_certificate( $lp_order, $user_id ) {
		try {
			$items = $lp_order->get_items();
			foreach ( $items as $item ) {
				if ( isset( $item['_lp_cert_id'] ) ) {
					$cert_id = $item['_lp_cert_id'];

					$lp_db                     = LP_Database::getInstance();
					$filter_delete             = new LP_User_Items_Filter();
					$filter_delete->collection = $lp_db->tb_lp_user_items;
					$filter_delete->where[]    = $lp_db->wpdb->prepare(
						'AND `item_id` = %d
						AND `item_type` = %s
						AND `user_id` = %d
						AND `ref_id` = %d
						AND `ref_type` = %s',
						$cert_id,
						'lp_certificate',
						$user_id,
						$lp_order->get_id(),
						LP_ORDER_CPT
					);

					LP_Database::getInstance()->delete_execute( $filter_delete );
				}
			}
		} catch ( Throwable $e ) {
			error_log( __METHOD__ . ': ' . $e->getMessage() );
		}
	}
}

LP_Certificate_Order::getInstance();
