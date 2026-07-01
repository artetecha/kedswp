<?php

namespace LearnPress\WooPayment;

use LearnPress\Helpers\Singleton;
use Exception;
use WC_Order;
use WC_Subscription;

defined( 'ABSPATH' ) || exit();

/**
 * Class LPWooSubscription
 *
 * Handle for WooCommerce Subscription plugin
 * https://woo.com/products/woocommerce-subscriptions/
 *
 * Require: enable option "Buy courses via Product"
 *
 * @since 4.1.4
 * @version 1.0.1
 */
class LPWooSubscription {
	use Singleton;

	public function init() {
		$this->hooks();
	}

	protected function hooks() {
		add_action( 'woocommerce_subscription_status_changed', array( $this, 'handle_status_changed' ), 10, 4 );
	}

	/**
	 * WC Order Subscription status changed
	 * Logic:
	 * - Get parent order of subscription
	 * - Get parent order of resubscribed if exists
	 * - Get LP order id from parent order
	 * - Change status of LP order
	 *
	 * @param int             $wc_subscription_id
	 * @param string          $old_status
	 * @param string          $new_status
	 * @param WC_Subscription $wc_subscription
	 *
	 * @return void
	 * @throws Exception
	 * @since 4.1.7
	 * @version 1.0.0
	 */
	public function handle_status_changed( $wc_subscription_id, $old_status, $new_status, $wc_subscription ) {
		/**
		 * @var $wc_parent_order WC_Order|bool
		 */
		$wc_parent_order = $wc_subscription->get_parent();
		if ( ! $wc_parent_order ) {
			return;
		}
		$first_lp_order_id = $wc_parent_order->get_meta( LPWooOrderHandler::$KEY_META_ID );
		// check wcs parent order contains LP order
		if ( ! $first_lp_order_id ) {
			return;
		}
		$wcs_last_order_id = $wc_subscription->get_last_order();
		if ( ! $wcs_last_order_id ) {
			return;
		}

		$wcs_related_orders = $wc_subscription->get_related_orders( 'all' );
		$lp_order_ids       = array();
		// find all LP order ids
		foreach ( $wcs_related_orders as $order ) {
			$lp_order_id = intval( $order->get_meta( LPWooOrderHandler::$KEY_META_ID ) );
			if ( $lp_order_id ) {
				$lp_order_ids[] = $lp_order_id;
			}
		}
		$last_lp_order_id = ! empty( $lp_order_ids ) ? $lp_order_ids[0] : $first_lp_order_id;
		if ( empty( $last_lp_order_id ) ) {
			return;
		}

		$lp_order = learn_press_get_order( $last_lp_order_id );
		if ( ! $lp_order ) {
			return;
		}

		$arr_status_subscription_can_learn_course = array(
			'active',
			'completed',
			'pending-cancel',
		);

		if ( in_array( $new_status, $arr_status_subscription_can_learn_course ) ) {
			$this->set_lp_order_status( $lp_order, LP_ORDER_COMPLETED );
		} else {
			$this->set_lp_order_status( $lp_order, LP_ORDER_CANCELLED );
		}
	}
	/**
	 * Set new status for LP Order when order status is changed
	 *
	 * @param LP_Order $lp_order
	 * @param string   $status
	 */
	public function set_lp_order_status( $lp_order, $status ) {
		$lp_order->set_status( $status );
		$lp_order->save();
	}
}
