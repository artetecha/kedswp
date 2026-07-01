<?php

namespace LearnPress\WooPayment\Background;

use Exception;
use LearnPress;
use LearnPress\WooPayment\LPWooOrderHandler;
use LP_Async_Request;
use LP_Request;
use Throwable;

defined( 'ABSPATH' ) || exit;

/**
 * Class LP_Background_Single_Course
 *
 * Single to run not schedule, run one time and done when be call
 *
 * @since 4.1.2
 * @author tungnx
 */
class LPWooBackground extends LP_Async_Request {
	protected $prefix = 'lp_woo_payment';
	protected $action = 'create_lp_order_when_payment_woocommerce';
	protected static $instance;

	/**
	 * @throws Exception
	 */
	protected function handle() {
		$params = array(
			'lp_order_id'    => LP_Request::get_param( 'lp_order_id', 0, 'int' ),
			'lp_order_items' => LP_Request::get_param( 'lp_order_items', array(), 'int' ),
			'wc_order_id'    => LP_Request::get_param( 'wc_order_id', 0, 'int' ),
		);

		$this->handleAddItemsToLpOrderBackground( $params );
	}

	/**
	 * handle add course to lp_order
	 *
	 * @param array $params
	 *
	 * @throws Exception
	 */
	protected function handleAddItemsToLpOrderBackground( array $params ) {
		ini_set( 'max_execution_time', HOUR_IN_SECONDS );
		try {
			$order_id       = $params['lp_order_id'] ?? 0;
			$wc_order_id    = $params['wc_order_id'] ?? 0;
			$lp_order_items = (array) $params['lp_order_items'] ?? array();
			$lp_order       = learn_press_get_order( $order_id );
			if ( ! $lp_order ) {
				throw new Exception( __( 'LP order is invalid!', 'learnpress-woo-payment' ) );
			}

			$lp_woo_order = new LPWooOrderHandler( $order_id, $wc_order_id );
			$lp_woo_order->add_item_to_order( $lp_order_items );
		} catch ( Throwable $e ) {
			error_log( __FUNCTION__ . ': ' . $e->getMessage() );
		}
		ini_set( 'max_execution_time', LearnPress::$time_limit_default_of_sever );
	}

	/**
	 * @return LPWooBackground
	 */
	public static function instance(): self {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}
}
