<?php
namespace LearnPress\Upsell\Coupon;

define( 'LP_ADDON_UPSELL_COUPON_PATH', LP_ADDON_UPSELL_PATH . '/inc/coupon/' );

class Init {

	protected static $instance = null;

	public function __construct() {
		$this->includes();

		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
	}

	public function includes() {
		require_once LP_ADDON_UPSELL_COUPON_PATH . 'capability.php';
		require_once LP_ADDON_UPSELL_COUPON_PATH . 'post-type.php';
		require_once LP_ADDON_UPSELL_COUPON_PATH . 'core-functions.php';
		require_once LP_ADDON_UPSELL_COUPON_PATH . 'hooks.php';
		require_once LP_ADDON_UPSELL_COUPON_PATH . 'coupon.php';
		require_once LP_ADDON_UPSELL_COUPON_PATH . 'TemplateHooks/ListCouponsTemplate.php';

		// API.
		require_once LP_ADDON_UPSELL_COUPON_PATH . 'api/admin.php';
		require_once LP_ADDON_UPSELL_COUPON_PATH . 'api/frontend.php';
	}

	public function enqueue_scripts() {
		$info = include LP_ADDON_UPSELL_PATH . '/build/coupon.asset.php';

		wp_register_script(
			'learnpress-package-coupon',
			LP_ADDON_UPSELL_URL . 'build/coupon.js',
			$info['dependencies'],
			$info['version'],
			[ 'strategy' => 'defer' ]
		);

		// Check is learnpress checkout page.
		if ( function_exists( 'learn_press_is_checkout' ) && learn_press_is_checkout() ) {
			wp_enqueue_style(
				'toastify',
				LP_ADDON_UPSELL_URL . 'public/toastify.min.css'
			);
			wp_enqueue_script( 'learnpress-package-coupon' );
		}
	}

	// Instance.
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}
}
Init::instance();
