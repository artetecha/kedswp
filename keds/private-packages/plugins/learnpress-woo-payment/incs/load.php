<?php

use LearnPress\WooPayment\Background\LPWooBackground;
use LearnPress\WooPayment\WooGateway;
use LearnPress\WooPayment\LPWooSubscription;
use LearnPress\WooPayment\Upsell\LP_WC_Package_Ajax;
use LearnPress\WooPayment\Upsell\LP_WC_Upsell;

/**
 * Class LP_Addon_Woo_Payment
 */
class LP_Addon_Woo_Payment extends LP_Addon {
	/**
	 * @var string
	 */
	public $version = LP_ADDON_WOO_PAYMENT_VER;

	/**
	 * @var string
	 */
	public $require_version = LP_ADDON_WOO_PAYMENT_REQUIRE_VER;

	/**
	 * @var string
	 */
	public $plugin_file = LP_ADDON_WOO_PAYMENT_FILE;

	/**
	 * @var LP_Addon_Woo_Payment|null
	 *
	 * Hold the singleton of LP_Woo_Payment_Preload object
	 */
	protected static $_instance = null;

	/**
	 * LP_Woo_Payment_Preload constructor.
	 */

	public function __construct() {
		parent::__construct();
		$this->includes();
	}

	/**
	 * @return self
	 */
	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	/**
	 * Include files needed
	 */
	protected function includes() {
		add_filter( 'learn-press/payment-methods', array( $this, 'lp_woo_settings' ) );
		add_filter(
			'learn-press/frontend/localize-data-global',
			function ( $data ) {
				$data['lp_woo_version'] = $this->version;

				return $data;
			}
		);

		if ( ! WooGateway::is_option_enabled() ) {
			return;
		}

		include_once LP_ADDON_WOO_PAYMENT_PATH . '/incs/class-lp-woo-ajax.php';
		LPWooBackground::instance();

		if ( WooGateway::is_by_courses_via_product() ) {
			require_once LP_ADDON_WOO_PAYMENT_PATH . '/incs/class-lp-woo-assign-course-to-product.php';
		} else {
			// Create type WC_Order_Item_LP_Course for wc order
			include_once LP_ADDON_WOO_PAYMENT_PATH . '/incs/class-wc-order-item-course.php';

			// Create type WC_Product_LP_Course for wc product
			require_once LP_ADDON_WOO_PAYMENT_PATH . '/incs/class-wc-product-lp-course.php';
		}

		// Hooks
		require_once LP_ADDON_WOO_PAYMENT_PATH . '/incs/class-lp-wc-hooks.php';
		LP_WC_Hooks::instance();

		// Compatible with LP Upsell
		if ( class_exists( 'LP_Addon_Upsell_Preload' ) ) {
			LP_WC_Upsell::instance();

			if ( ! WooGateway::is_by_courses_via_product() ) {
				LP_WC_Package_Ajax::instance();
			}
		}

		// Plugin WC subscription
		if ( class_exists( 'WC_Subscriptions' ) ) {
			LPWooSubscription::instance();
		}
	}

	/**
	 * Show lp woo settings
	 *
	 * @param array $methods
	 *
	 * @return array
	 */
	public function lp_woo_settings( array $methods ): array {
		$methods['woo-payment'] = new WooGateway();

		return $methods;
	}
}
