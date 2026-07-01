<?php
namespace LearnPress\Upsell\Coupon\API;

use LearnPress\Upsell\Coupon\Core_Functions;
use LearnPress\Upsell\Coupon\Coupon;
use LP_Addon_Upsell_Preload;
use LP_REST_Response;

class Frontend {

	protected static $instance = null;

	const NAMESPACE = 'learnpress-coupon/v1/frontend';

	public function __construct() {
		add_action( 'rest_api_init', array( $this, 'register_rest_api' ) );
	}

	public function register_rest_api() {
		register_rest_route(
			self::NAMESPACE,
			'/apply-coupon',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'apply_coupon' ),
				'permission_callback' => '__return_true',
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/remove-coupon',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'remove_coupon' ),
				'permission_callback' => '__return_true',
			)
		);
	}

	public function apply_coupon( $request ) {
		$response    = new LP_REST_Response();
		$coupon_code = ! empty( 'coupon_code' ) ? sanitize_text_field( wp_unslash( $request['coupon_code'] ) ) : '';

		try {
			if ( empty( $coupon_code ) ) {
				throw new \Exception( __( 'Coupon code is empty', 'learnpress-upsell' ) );
			}

			// sanitize.
			$coupon_code = Core_Functions::instance()->format_coupon_code( $coupon_code );

			if ( ! Core_Functions::instance()->is_coupons_enabled() ) {
				throw new \Exception( __( 'Coupons are disabled', 'learnpress-upsell' ) );
			}

			$coupon = new Coupon( $coupon_code );
			if ( ! Core_Functions::instance()->validate_coupon_exists( $coupon ) ) {
				throw new \Exception( __( 'Coupon does not exist', 'learnpress-upsell' ) );
			}

			if ( ! Core_Functions::instance()->validate_coupon_usage_limit( $coupon ) ) {
				throw new \Exception( __( 'Coupon usage limit has been reached', 'learnpress-upsell' ) );
			}

			if ( ! Core_Functions::instance()->validate_coupon_user_usage_limit( $coupon ) ) {
				throw new \Exception( __( 'Coupon usage limit per user has been reached', 'learnpress-upsell' ) );
			}

			if ( ! Core_Functions::instance()->validate_coupon_expiry_date( $coupon ) ) {
				throw new \Exception( __( 'Coupon is invalid on date', 'learnpress-upsell' ) );
			}

			if ( ! Core_Functions::instance()->validate_coupon_packages_ids( $coupon ) ) {
				throw new \Exception( __( 'Coupon is not valid for this package', 'learnpress-upsell' ) );
			}

			if ( ! Core_Functions::instance()->validate_coupon_course_ids( $coupon ) ) {
				throw new \Exception( __( 'Coupon is not valid for this course', 'learnpress-upsell' ) );
			}

			if ( ! Core_Functions::instance()->validate_coupon_course_categories( $coupon ) ) {
				throw new \Exception( __( 'Coupon is not valid for this course category', 'learnpress-upsell' ) );
			}

			if ( ! Core_Functions::instance()->validate_coupon_excluded_items( $coupon ) ) {
				throw new \Exception( __( 'Coupon is not valid for this item', 'learnpress-upsell' ) );
			}

			if ( ! Core_Functions::instance()->validate_coupon_excluded_course_category_ids( $coupon ) ) {
				throw new \Exception( __( 'Coupon is not valid for this course category', 'learnpress-upsell' ) );
			}

			// Check if coupon is already applied.
			if ( Core_Functions::instance()->is_has_discounted( $coupon ) ) {
				throw new \Exception( __( 'Coupon code already applied!', 'learnpress-upsell' ) );
			}

			Core_Functions::instance()->set_applied_coupons( $coupon );

			do_action( 'learnpress_coupon/apply_coupon', $coupon );

			$cart = LP()->cart;
			if ( ! $cart ) {
				throw new \Exception( __( 'Cart is empty', 'learnpress-upsell' ) );
			}

				$cart_items = $cart->get_items();
				$core_total = $cart->calculate_totals();
				$total      = $core_total->total ?? 0;
				$subtotal   = $core_total->subtotal ?? 0;

			foreach ( $cart_items as $cart_item ) {
				$coupon_codes = $cart_item['applied_coupons'] ?? [];

				if ( empty( $coupon_codes ) ) {
					continue;
				}

				foreach ( $coupon_codes as $coupon_code ) {
					$coupon         = new Coupon( $coupon_code );
					$discount       = Core_Functions::instance()->calculate_discount_amount( $coupon, $subtotal );
					$price_discount = learn_press_format_price( $discount );

					ob_start();
					LP_Addon_Upsell_Preload::$addon->get_template(
						'coupon/info_coupon_checkout.php',
						compact( 'coupon_code', 'price_discount' )
					);
					$html                   = ob_get_clean();
					$response->data->output = html_entity_decode( $html );
				}
			}

				$total                 = max( 0, $total );
				$response->data->total = html_entity_decode( learn_press_format_price( $total ) );
				$response->status      = 'success';
				$response->message     = __( 'Coupon code applied successfully!', 'learnpress-upsell' );
			return $response;
		} catch ( \Throwable $th ) {
			$response->message = $th->getMessage();
			return $response;
		}
	}

	public function remove_coupon( $request ) {
		$response    = new LP_REST_Response();
		$coupon_code = ! empty( 'coupon_code' ) ? sanitize_text_field( wp_unslash( $request['coupon_code'] ) ) : '';

		try {
			$coupon_code = Core_Functions::instance()->format_coupon_code( $coupon_code );

			Core_Functions::instance()->remove_applied_coupons( $coupon_code );

			$cart  = LP()->cart;
			$total = 0;
			if ( $cart ) {
				$core_total = $cart->calculate_totals();
				$total      = $core_total->total ?? 0;
			}

			$response->status      = 'success';
			$response->message     = __( 'Coupon code removed successfully!', 'learnpress-upsell' );
			$response->data->total = learn_press_format_price( $total );

			return $response;
		} catch ( \Throwable $th ) {
			$response->status  = 'error';
			$response->message = $th->getMessage();

			return $response;
		}
	}

	public static function instance() {
		if ( ! self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}
}
Frontend::instance();
