<?php
/**
 * Plugin load class.
 *
 * @author   ThimPress
 * @package  LearnPress/2Checkout/Classes
 * @version  3.0.0
 */

// Prevent loading this file directly
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'LP_Addon_2Checkout_Payment' ) ) {
	/**
	 * Class LP_Addon_2Checkout_Payment
	 */
	class LP_Addon_2Checkout_Payment extends LP_Addon {

		/**
		 * @var string
		 */
		public $version = LP_ADDON_2CHECKOUT_VER;

		/**
		 * @var string
		 */
		public $require_version = LP_ADDON_2CHECKOUT_REQUIRE_VER;

		/**
		 * Path file addon.
		 *
		 * @var string
		 */
		public $plugin_file = LP_ADDON_2CHECKOUT_FILE;

		/**
		 * LP_Addon_2Checkout_Payment constructor.
		 */
		public function __construct() {
			parent::__construct();
			add_filter( 'learn-press/payment-methods', array( $this, 'add_payment' ) );
			add_filter( 'learn-press/frontend-default-scripts', array( $this, 'enqueue_script' ) );
			if ( ! is_admin() ) {
				$this->listen_webhook_callback();
			}
		}

		/**
		 * Define Learnpress 2Checkout payment constants.
		 *
		 * @since 3.0.0
		 */
		protected function _define_constants() {
			define( 'LP_ADDON_2CHECKOUT_PAYMENT_PATH', dirname( LP_ADDON_2CHECKOUT_FILE ) );
			define( 'LP_ADDON_2CHECKOUT_PAYMENT_INC', LP_ADDON_2CHECKOUT_PAYMENT_PATH . '/inc/' );
			define( 'LP_ADDON_2CHECKOUT_PAYMENT_TEMPLATE', LP_ADDON_2CHECKOUT_PAYMENT_PATH . '/templates/' );
		}

		/**
		 * Include required core files used in admin and on the frontend.
		 *
		 * @since 3.0.0
		 */
		protected function _includes() {
			include_once LP_ADDON_2CHECKOUT_PAYMENT_INC . 'class-lp-gateway-2checkout.php';
		}

		/**
		 * Init hooks.
		 */
		protected function _init_hooks() {}

		/**
		 * Add 2Checkout to payment system.
		 *
		 * @param $methods
		 *
		 * @return mixed
		 */
		public function add_payment( $methods ) {
			$methods['2checkout'] = LP_Gateway_2Checkout::instance();

			return $methods;
		}

		/**
		 * Enqueue script
		 *
		 * @param $scripts
		 *
		 * @return mixed
		 */
		public function enqueue_script( $scripts ) {
			$min = LP_Assets::$_min_assets;

			$scripts['lp-2checkout'] = new LP_Asset_Key(
				$this->get_plugin_url( "assets/2checkout{$min}.js" ),
				array( 'jquery' ),
				array( LP_PAGE_CHECKOUT ),
				0,
				1
			);

			return $scripts;
		}

		/**
		 * List webhook callback.
		 *
		 * @return void
		 * @since 4.0.2
		 * @version 1.0.0
		 */
		public function listen_webhook_callback() {
			$lp_two_checkout_payment = LP_Request::get_param( 'learn_press_2checkout' );
			if ( empty( $lp_two_checkout_payment ) ) {
				return;
			}
			$request = LP_Helper::sanitize_params_submitted( $_REQUEST );
			LP_Gateway_2Checkout::instance()->web_hook_process_2checkout( $request );
			ob_start();
			echo "\n===============================================================\n<br />";
			printf( __( 'LearnPress webhook %s process completed', 'learnpress-2checkout-payment' ), '2checkout' );
			echo "\n<pre>";
			print_r( $request );
			echo "</pre>\n===============================================================\n";
			$output = ob_get_clean();
			wp_die( $output, __( 'The LearnPress webhook process is complete', 'learnpress-2checkout-payment' ), array( 'response' => 200 ) );
		}
	}
}
