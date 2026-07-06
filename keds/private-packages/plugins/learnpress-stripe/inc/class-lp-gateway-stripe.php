<?php
/**
 * Stripe payment gateway class.
 *
 * @author   ThimPress
 * @package  LearnPress/Stripe/Classes
 * @version  4.0.1
 * @since    3.0.0
 */

use LearnPress\Helpers\Singleton;
use LearnPress\Helpers\Template;
use Stripe\Checkout\Session;
use Stripe\Exception\ApiErrorException;
use Stripe\Price;
use Stripe\Product;
use Stripe\StripeClient;
use Stripe\StripeObject;
use Stripe\Webhook;

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'LP_Gateway_Stripe' ) ) {
	/**
	 * Class LP_Gateway_Stripe
	 */
	class LP_Gateway_Stripe extends LP_Gateway_Abstract {
		use Singleton;

		/**
		 * @var string Payment method ID.
		 */
		public $id = 'stripe';
		/**
		 * @var object|null
		 */
		protected $settings = null;
		/**
		 * @var null
		 */
		public $test_mode;
		/**
		 * @var null
		 */
		public $publish_key;
		/**
		 * @var null
		 */
		protected $secret_key;
		/**
		 * @var null|string
		 */
		protected $webhook_secret;
		/**
		 * @var null|string
		 */
		public $live_webhook_secret;
		/**
		 * @var null|string
		 */
		public $test_webhook_secret;
		/**
		 * @var string
		 */
		public $enable_subscriptions = 'no';
		/**
		 * @var string|null
		 */
		public $test_publish_key;
		/**
		 * @return string|null
		 */
		public $test_secret_key;
		/**
		 * @var null
		 */
		protected $client_secret;

		public function init() {}

		/**
		 * LP_Gateway_Stripe constructor.
		 */
		public function __construct() {
			$this->method_title       = 'Stripe';
			$this->method_description = esc_html__( 'Make a payment with Stripe.', 'learnpress-stripe' );
			$this->icon               = LP_ADDON_STRIPE_PAYMENT_URL . 'assets/images/stripe.svg';

			parent::__construct();

			// Get settings.
			$this->title       = $this->settings->get( 'title' ) ?? $this->method_title;
			$this->description = $this->settings->get( 'description' );

			// Add default values for fresh installs.
			if ( $this->is_enabled() ) {
				$this->test_mode            = $this->settings->get( 'test_mode', 'no' );
				$this->enable_subscriptions = $this->settings->get( 'enable_subscriptions', 'no' );
				$this->test_publish_key     = $this->settings->get( 'test_publish_key', '' );
				$this->test_secret_key      = $this->settings->get( 'test_secret_key', '' );
				$this->test_webhook_secret  = $this->settings->get( 'test_webhook_secret', '' );
				$this->publish_key          = $this->settings->get( 'live_publish_key', '' );
				$this->live_webhook_secret  = $this->settings->get( 'live_webhook_secret', '' );
				if ( $this->is_test_mode() ) {
					$this->publish_key = $this->test_publish_key;
				}
				$this->secret_key = $this->settings->get( 'live_secret_key', '' );
				if ( $this->is_test_mode() ) {
					$this->secret_key = $this->test_secret_key;
				}
				$this->webhook_secret = $this->is_test_mode() ? $this->test_webhook_secret : $this->live_webhook_secret;
			}
			// check payment gateway enable.
			add_filter(
				'learn-press/payment-gateway/' . $this->id . '/available',
				array(
					$this,
					'stripe_available',
				),
				10,
				2
			);

			add_filter( 'learn-press/profile-order-actions', array( $this, 'add_manage_subscription_order_action' ), 10, 2 );
			add_action( 'learn-press/ready', array( $this, 'maybe_redirect_to_billing_portal' ) );
		}
		/**
		 * Admin payment settings.
		 *
		 * @return array
		 */
		public function get_settings() {
			return include_once LP_ADDON_STRIPE_PAYMENT_PATH . '/config/settings.php';
		}

		/**
		 * Check is enable option Direct payment on Stripe
		 *
		 * @return bool
		 * @since 4.0.2
		 * @version 1.0.0
		 */
		public function is_direct_pay_on_stripe_page(): bool {

			return $this->settings->get( 'direct_payment_on_stripe_page' ) === 'yes';
		}

		/**
		 * Check if Stripe subscriptions are enabled.
		 *
		 * @return bool
		 */
		public function is_subscription_enabled(): bool {

			return $this->enable_subscriptions === 'yes';
		}
		/**
		 * Payment form.
		 */
		public function get_payment_form() {
			$description                  = wpautop( wp_kses_post( $this->get_description() ) );
			$mode                         = $this->is_test_mode() ? 'test' : 'live';
			$is_direct_pay_on_stripe_page = $this->is_direct_pay_on_stripe_page();

			$html_direct_pay_on_stripe_page = '';
			if ( $is_direct_pay_on_stripe_page ) {
				$html_direct_pay_on_stripe_page = sprintf(
					'<p>%s</p>',
					__( 'You will be redirected to Stripe to complete your payment.', 'learnpress-stripe' )
				);
			}

			$html_test_mode_message = '';
			if ( $mode === 'test' && ! $is_direct_pay_on_stripe_page ) {
				ob_start();
				Template::print_message(
					esc_html__(
						'Test mode is enabled. You can use the card number 4242424242424242 with any CVC and a valid expiration date for testing purpose.',
						'learnpress-stripe'
					),
					'info'
				);
				$html_test_mode_message = ob_get_clean();
			}

			$section = array(
				'description'               => sprintf( '<p>%s</p>', $description ),
				'direct_pay_on_stripe_page' => $html_direct_pay_on_stripe_page,
				'live'                      => '<div id="lp-stripe-payment-form"></div>',
				'test_mode_message'         => $html_test_mode_message,
			);

			// LP_Addon_Stripe_Payment_Preload::$addon->get_template( 'form.php', $data );

			return Template::combine_components( $section );
		}

		/**
		 * Check gateway available.
		 *
		 * @return bool
		 */
		public function stripe_available() {
			if ( ! $this->is_enabled() ) {
				return false;
			}

			if ( $this->is_test_mode() ) {
				if ( empty( $this->test_publish_key ) || empty( $this->test_secret_key ) ) {
					return false;
				}
			} elseif ( empty( $this->publish_key ) || empty( $this->secret_key ) ) {
					return false;
			}

			return true;
		}

		/**
		 * @return bool
		 */
		public function is_test_mode(): bool {
			return $this->test_mode === 'yes';
		}

		/**
		 * Stripe payment process.
		 *
		 * @param int $order_id
		 *
		 * @return array
		 * @throws string
		 * @throws ApiErrorException
		 * @throws Exception
		 */
		public function process_payment( $order_id ) {
			$result = array(
				'result'   => 'fail',
				'message'  => '',
				'redirect' => '',
			);

			$lp_order = learn_press_get_order( $order_id );
			if ( ! $lp_order ) {
				throw new Exception( __( 'Order not found!', 'learnpress-stripe' ) );
			}

			$direct_pay_on_stripe_page = self::instance()->is_direct_pay_on_stripe_page();

			$subscription_data = $this->is_data_for_payment_subscription( $lp_order );
			if ( ! empty( $subscription_data ) ) {
				/**
				 * Subscriptions are always paid on the Stripe-hosted page (Checkout
				 * Session), regardless of the "direct payment on Stripe page" setting.
				 * The in-page (Payment Element) subscription flow is planned for 4.0.7.
				 */
				$subscription_result = $this->pay_via_subscription( $lp_order, $subscription_data );
				$redirect_url        = $subscription_result['redirect_url'] ?? '';
				if ( empty( $redirect_url ) ) {
					throw new Exception( __( 'Invalid Stripe subscription checkout response.', 'learnpress-stripe' ) );
				}

				return array_merge(
					$result,
					array(
						'result'   => 'success',
						'message'  => esc_html__( 'Redirecting to Stripe.', 'learnpress-stripe' ),
						'redirect' => esc_url_raw( $redirect_url ),
					)
				);
			}

			if ( $direct_pay_on_stripe_page ) {
				$stripe_checkout_url = $this->get_url_payment_on_stripe_page( $lp_order );
				$result              = array_merge(
					$result,
					array(
						'result'   => 'success',
						'message'  => esc_html__( 'Redirecting to Stripe.', 'learnpress-stripe' ),
						'redirect' => esc_url( $stripe_checkout_url ),
					)
				);
			} else {
				$stripe_pi = LearnPress::instance()->session->get( 'stripe_awaiting_payment_intent', 0 );
				$pi_id     = $stripe_pi->id;
				$this->update_payment_intent( $pi_id, $order_id );

				$result = array_merge(
					$result,
					array(
						/**
						 * Don't set success on here,
						 * because one step left confirm payment intent status,
						 * if status is succeeded, then set success on method stripe_retrieve_payment_intent.
						 */
						'result'   => LP_ORDER_PROCESSING,
						'message'  => esc_html__( 'The payment is processing.', 'learnpress-stripe' ),
						'redirect' => add_query_arg( 'lp-stripe-confirm-payment', 1, $this->get_return_url( $lp_order ) ),
					)
				);
			}

			return $result;
		}

		/**
		 * Get stripe checkout url.
		 * via create a checkout Session
		 * https://stripe.com/docs/api/checkout/sessions/create?lang=php
		 *
		 * @param LP_Order $order
		 *
		 * @return string|null
		 * @throws ApiErrorException
		 * @throws Exception
		 * @version 1.0.0
		 * @since 4.0.2
		 */
		public function get_url_payment_on_stripe_page( LP_Order $order ) {

			$stripe                  = new StripeClient( $this->secret_key );
			$success_url             = $this->get_return_url( $order );
			$cancel_url              = learn_press_get_page_link( 'checkout' ); // $order->get_cancel_order_url();
			$stripe_checkout_session = $stripe->checkout->sessions->create(
				array(
					'line_items'  => array(
						array(
							'price_data' => array(
								'currency'     => strtolower( learn_press_get_currency() ),
								'product_data' => array(
									'name' => sprintf( __( 'Order %s', 'learnpress-stripe' ), $order->get_order_number() ),
								),
								'unit_amount'  => $this->calculate_order_amount( $order->get_total() ),
							),
							'quantity'   => 1,
						),
					),
					'mode'        => 'payment',
					'success_url' => add_query_arg( 'lp_stripe_session_id', '{CHECKOUT_SESSION_ID}', $success_url ),
					'cancel_url'  => $cancel_url,
					'metadata'    => array( 'lp_order_id' => $order->get_id() ),
				)
			);

			return $stripe_checkout_session->url;
		}

		/**
		 * Create a Stripe Checkout subscription session.
		 *
		 * @param LP_Order $lp_order
		 * @param array    $data
		 *
		 * @return array
		 * @throws ApiErrorException
		 * @throws Exception
		 */
		public function pay_via_subscription( LP_Order $lp_order, array $data ): array {
			if ( ! $this->is_subscription_enabled() ) {
				throw new Exception( __( 'Stripe subscriptions are disabled.', 'learnpress-stripe' ) );
			}

			if ( empty( $this->secret_key ) ) {
				throw new Exception( __( 'Stripe secret key is missing.', 'learnpress-stripe' ) );
			}

			if ( empty( $this->webhook_secret ) ) {
				throw new Exception( __( 'Stripe webhook signing secret is missing.', 'learnpress-stripe' ) );
			}

			/*if ( strpos( (string) $this->webhook_secret, 'whsec_' ) !== 0 ) {
				throw new Exception( __( 'Stripe webhook signing secret is invalid.', 'learnpress-stripe' ) );
			}*/

			$plan_id = sanitize_text_field( (string) ( $data['plan_id'] ?? '' ) );
			if ( empty( $plan_id ) ) {
				throw new Exception( __( 'Stripe subscription price ID is invalid.', 'learnpress-stripe' ) );
			}

			$lp_order_id = $lp_order->get_id();
			$quantity    = max(
				1,
				absint(
					$data['quantity'] ??
					get_post_meta( $lp_order_id, self::META_SUBSCRIPTION_QUANTITY, true )
				)
			);
			$success_url = ! empty( $data['success_url'] ) ? $data['success_url'] : $this->get_return_url( $lp_order );
			$cancel_url  = ! empty( $data['cancel_url'] ) ? $data['cancel_url'] : learn_press_get_page_link( 'checkout' );
			$setup_fee   = max( 0, (float) ( $data['setup_fee'] ?? 0 ) );

			$metadata = array(
				'lp_order_id' => (string) $lp_order_id,
			);
			if ( ! empty( $data['metadata'] ) && is_array( $data['metadata'] ) ) {
				foreach ( $data['metadata'] as $key => $value ) {
					if ( is_scalar( $value ) ) {
						$metadata[ sanitize_key( (string) $key ) ] = sanitize_text_field( (string) $value );
					}
				}
			}

			$params = array(
				'line_items'          => array(
					array(
						'price'    => $plan_id,
						'quantity' => $quantity,
					),
				),
				'mode'                => 'subscription',
				'success_url'         => add_query_arg( 'lp_stripe_subscription_session_id', '{CHECKOUT_SESSION_ID}', $success_url ),
				'cancel_url'          => $cancel_url,
				'client_reference_id' => (string) $lp_order_id,
				'metadata'            => $metadata,
				'subscription_data'   => array(
					'metadata' => $metadata,
				),
			);

			// For setup fee
			if ( $setup_fee > 0 ) {
				$params['line_items'][] = array(
					'price_data' => array(
						'currency'     => strtolower( learn_press_get_currency() ),
						'product_data' => array(
							'name' => $data['setup_fee_name'] ?? '',
						),
						'unit_amount'  => $this->calculate_order_amount( $setup_fee ),
					),
					'quantity'   => 1,
				);
			}

			// For trial period
			$trial_period_days = absint( ( $data['trial_days'] ?? 0 ) );
			if ( $trial_period_days > 0 ) {
				$params['subscription_data']['trial_period_days'] = $trial_period_days;
			}

			$checkout_email = $lp_order->get_checkout_email();
			if ( is_email( $checkout_email ) ) {
				$params['customer_email'] = $checkout_email;
			}

			$stripe  = new StripeClient( $this->secret_key );
			$session = $stripe->checkout->sessions->create( $params );

			$session_data = $session->toArray();
			$redirect_url = esc_url_raw( $session_data['url'] ?? '' );
			if ( empty( $redirect_url ) ) {
				throw new Exception( __( 'Invalid Stripe subscription checkout response.', 'learnpress-stripe' ) );
			}

			update_post_meta( $lp_order_id, self::META_SUBSCRIPTION_PLAN_ID, $plan_id );
			update_post_meta( $lp_order_id, '_lp_stripe_checkout_session_id', sanitize_text_field( $session_data['id'] ?? '' ) );

			$session_data['redirect_url'] = $redirect_url;

			return $session_data;
		}

		/**
		 * Whether the current cart checks out as a Stripe subscription.
		 *
		 * Subscriptions are paid on the Stripe-hosted page (Checkout Session), so when
		 * this is true the in-page Payment Element is skipped at checkout. Integrations
		 * (e.g. Membership) answer via the filter.
		 *
		 * @return bool
		 */
		public function cart_needs_subscription_checkout(): bool {
			return (bool) apply_filters( 'learn-press/stripe/cart-needs-subscription-checkout', false, $this );
		}

		/**
		 * Create a Stripe product and recurring price for membership subscriptions.
		 *
		 * @param array $data Required: name, amount, currency, interval, interval_count.
		 * Optional: description, product_id/product, metadata.
		 *
		 * @return array
		 * @throws ApiErrorException
		 * @throws Exception
		 * @since 4.0.6
		 * @version 1.0.0
		 */
		public function create_plan( array $data ): array {
			if ( ! $this->is_subscription_enabled() ) {
				throw new Exception( __( 'Stripe subscriptions are disabled.', 'learnpress-stripe' ) );
			}

			if ( empty( $this->secret_key ) ) {
				throw new Exception( __( 'Stripe secret key is missing.', 'learnpress-stripe' ) );
			}

			$data        = $this->validate_data_plan_payload( $data );
			$metadata    = LP_Helper::sanitize_params_submitted( $data['metadata'] ?? array() );
			$description = esc_html( $data['description'] ?? '' );
			$stripe      = new StripeClient( $this->secret_key );

			// Create Product
			$product_payload = array(
				'name'     => $data['name'],
				'metadata' => $metadata,
			);
			if ( ! empty( $description ) ) {
				$product_payload['description'] = $description;
			}

			/** @var Product $stripeProduct */
			$stripeProduct = $stripe->products->create( $product_payload );
			$product_data  = $stripeProduct->toArray();
			$product_id    = $stripeProduct->id;

			// Create price subscription with Product ID
			$price_payload = array(
				'currency'    => strtolower( (string) $data['currency'] ),
				'unit_amount' => absint(
					round(
						$this->calculate_stripe_amount_for_currency(
							(float) $data['amount'],
							(string) $data['currency']
						)
					)
				),
				'product'     => $product_id,
				'recurring'   => array(
					'interval'       => $data['interval'],
					'interval_count' => max( 1, absint( $data['interval_count'] ) ),
				),
				'metadata'    => $metadata,
				'nickname'    => $data['name'],
			);

			/** @var Price $stripePrice */
			$stripePrice = $stripe->prices->create( $price_payload );
			$price_data  = $stripePrice->toArray();
			if ( empty( $price_data['id'] ) ) {
				throw new Exception( __( 'Invalid Stripe price response.', 'learnpress-stripe' ) );
			}

			return array(
				'status'  => 'success',
				'product' => $product_data,
				'plan'    => $price_data,
				'message' => __( 'Stripe subscription price created.', 'learnpress-stripe' ),
			);
		}

		/**
		 * Fetch Stripe price details for membership verification.
		 *
		 * @param string $plan_id Stripe price id.
		 *
		 * @return array
		 * @throws ApiErrorException
		 * @throws Exception
		 */
		public function get_plan( string $plan_id ): array {
			if ( ! $this->is_subscription_enabled() ) {
				throw new Exception( __( 'Stripe subscriptions are disabled.', 'learnpress-stripe' ) );
			}

			if ( empty( $this->secret_key ) ) {
				throw new Exception( __( 'Stripe secret key is missing.', 'learnpress-stripe' ) );
			}

			$stripeClient = new StripeClient( $this->secret_key );
			$stripePrice  = $stripeClient->prices->retrieve(
				$plan_id,
				array(
					'expand' => array( 'product' ),
				)
			);

			$price_data = $stripePrice->toArray();

			return array(
				'status'  => 'success',
				'price'   => $price_data,
				'plan'    => $price_data,
				'summary' => $this->build_stripe_price_summary( $price_data ),
				'message' => __( 'Stripe subscription price fetched.', 'learnpress-stripe' ),
			);
		}

		/**
		 * Update Stripe recurring price details.
		 *
		 * Stripe Prices cannot safely change amount/currency/interval in-place. If
		 * any of those fields change, create a replacement Price on the same
		 * Product and archive the old Price for new purchases.
		 *
		 * @param string $plan_id Stripe price id.
		 * @param array  $data Plan payload.
		 *
		 * @return array
		 * @throws ApiErrorException
		 * @throws Exception
		 */
		public function update_plan( string $plan_id, array $data ): array {
			if ( ! $this->is_subscription_enabled() ) {
				throw new Exception( __( 'Stripe subscriptions are disabled.', 'learnpress-stripe' ) );
			}

			if ( empty( $this->secret_key ) ) {
				throw new Exception( __( 'Stripe secret key is missing.', 'learnpress-stripe' ) );
			}

			$plan_id = sanitize_text_field( $plan_id );
			if ( empty( $plan_id ) ) {
				throw new Exception( __( 'Missing Stripe price ID.', 'learnpress-stripe' ) );
			}

			$current         = $this->get_plan( $plan_id );
			$current_price   = $current['price'] ?? array();
			$current_summary = $current['summary'] ?? array();
			$product_id      = $this->extract_stripe_id( $current_price['product'] ?? '' );

			$product_id_input = $data['product'] ?? ( $data['product_id'] ?? '' );
			$data             = $this->validate_data_plan_payload( $data );
			if ( ! empty( $product_id_input ) ) {
				$product_id = sanitize_text_field( (string) $product_id_input );
			}

			if ( empty( $product_id ) ) {
				throw new Exception( __( 'Missing Stripe product ID.', 'learnpress-stripe' ) );
			}

			$metadata               = $this->sanitize_stripe_metadata( $data['metadata'] ?? array() );
			$stripe                 = new StripeClient( $this->secret_key );
			$amount_changed         = abs( (float) ( $current_summary['amount'] ?? 0 ) - (float) $data['amount'] ) > 0.000001;
			$currency_changed       = strtoupper( (string) ( $current_summary['currency'] ?? '' ) ) !== strtoupper( (string) $data['currency'] );
			$interval_changed       = (string) ( $current_summary['interval'] ?? '' ) !== (string) $data['interval'];
			$interval_count_changed = (int) ( $current_summary['interval_count'] ?? 1 ) !== (int) $data['interval_count'];
			$requires_replacement   = $amount_changed || $currency_changed || $interval_changed || $interval_count_changed;

			if ( $requires_replacement ) {
				$price_payload = array(
					'currency'    => strtolower( $data['currency'] ?? '' ),
					'unit_amount' => absint(
						round(
							$this->calculate_stripe_amount_for_currency(
								(float) $data['amount'] ?? 0,
								(string) $data['currency'] ?? ''
							)
						)
					),
					'product'     => $product_id,
					'recurring'   => array(
						'interval'       => $data['interval'],
						'interval_count' => max( 1, absint( $data['interval_count'] ?? 0 ) ),
					),
					'metadata'    => $metadata,
					'nickname'    => $data['name'] ?? '',
				);

				$new_price      = $stripe->prices->create( $price_payload );
				$new_price_data = $new_price->toArray();
				if ( empty( $new_price_data['id'] ) ) {
					throw new Exception( __( 'Invalid Stripe price response.', 'learnpress-stripe' ) );
				}

				$archived_price      = $stripe->prices->update( $plan_id, array( 'active' => false ) );
				$archived_price_data = $archived_price->toArray();

				return array(
					'status'    => 'success',
					'action'    => 'replaced',
					'old_price' => $archived_price_data,
					'price'     => $new_price_data,
					'plan'      => $new_price_data,
					'price_id'  => $new_price_data['id'],
					'plan_id'   => $new_price_data['id'],
					'summary'   => $this->build_stripe_price_summary( $new_price_data ),
					'message'   => __( 'Stripe subscription price replaced.', 'learnpress-stripe' ),
				);
			}

			$update_payload = array(
				'metadata' => $metadata,
				'nickname' => $data['name'],
			);
			if ( isset( $data['status'] ) && '' !== (string) $data['status'] ) {
					$status = strtoupper( sanitize_text_field( (string) $data['status'] ) );
				if ( ! in_array( $status, array( 'ACTIVE', 'INACTIVE' ), true ) ) {
					throw new Exception( __( 'Invalid Stripe price status.', 'learnpress-stripe' ) );
				}
				$update_payload['active'] = 'ACTIVE' === $status;
			}

			$price      = $stripe->prices->update( $plan_id, $update_payload );
			$price_data = $price->toArray();

			return array(
				'status'   => 'success',
				'action'   => 'updated',
				'price'    => $price_data,
				'plan'     => $price_data,
				'price_id' => $price_data['id'] ?? $plan_id,
				'plan_id'  => $price_data['id'] ?? $plan_id,
				'summary'  => $this->build_stripe_price_summary( $price_data ),
				'message'  => __( 'Stripe subscription price updated.', 'learnpress-stripe' ),
			);
		}

		/**
		 * Archive a Stripe recurring price.
		 *
		 * Stripe Prices are archived by setting active=false. Existing
		 * subscriptions can keep using the historical Price; new checkouts cannot.
		 *
		 * @param string $plan_id Stripe price id.
		 *
		 * @return array
		 * @throws ApiErrorException
		 * @throws Exception
		 */
		public function delete_plan( string $plan_id ): array {

			if ( ! $this->is_subscription_enabled() ) {
				throw new Exception( __( 'Stripe subscriptions are disabled.', 'learnpress-stripe' ) );
			}

			if ( empty( $this->secret_key ) ) {
				throw new Exception( __( 'Stripe secret key is missing.', 'learnpress-stripe' ) );
			}

			$plan_id = sanitize_text_field( $plan_id );
			if ( empty( $plan_id ) ) {
				throw new Exception( __( 'Missing Stripe price ID.', 'learnpress-stripe' ) );
			}

			$stripe     = new StripeClient( $this->secret_key );
			$price      = $stripe->prices->update( $plan_id, array( 'active' => false ) );
			$price_data = $price->toArray();

			return array(
				'status'   => 'success',
				'action'   => 'archived',
				'price'    => $price_data,
				'plan'     => $price_data,
				'price_id' => $price_data['id'] ?? $plan_id,
				'plan_id'  => $price_data['id'] ?? $plan_id,
				'summary'  => $this->build_stripe_price_summary( $price_data ),
				'message'  => __( 'Stripe subscription price archived.', 'learnpress-stripe' ),
			);
		}

		/**
		 * Verify and capture Stripe subscription webhooks.
		 *
		 * @param WP_REST_Request $request
		 *
		 * @return void
		 * @throws Exception
		 * @since 4.0.6
		 * @version 1.0.0
		 */
		public function capture_subscription_webhook( WP_REST_Request $request ) {
			$payload   = $request->get_body();
			$signature = $request->get_header( 'stripe-signature' );
			if ( '' === (string) $payload || empty( $signature ) ) {
				throw new Exception( __( 'Invalid Stripe webhook request.', 'learnpress-stripe' ), 400 );
			}

			$event      = Webhook::constructEvent( $payload, $signature, $this->webhook_secret );
			$event_data = $event->toArray();

			LP_Debug::log_to_comment( 'Webhook payload: ' . json_encode( $event_data, JSON_UNESCAPED_UNICODE ) );

			$event_id   = sanitize_text_field( (string) ( $event_data['id'] ?? '' ) );
			$event_type = sanitize_text_field( (string) ( $event_data['type'] ?? '' ) );
			$created    = absint( $event_data['created'] ?? 0 );
			$object     = (array) ( $event_data['data']['object'] ?? array() );

			switch ( $event_type ) {
				case 'invoice.paid':
					$this->handle_invoice_paid( $object, $event_id, $created );
					break;
				case 'invoice.payment_failed':
					$this->handle_invoice_payment_failed( $object, $event_id, $created );
					break;
				case 'customer.subscription.updated':
					$this->handle_subscription_updated( $object, $event_id, $created );
					break;
				case 'customer.subscription.deleted':
					$this->handle_subscription_deleted( $object, $event_id, $created );
					break;
				default:
					do_action( 'learnpress/stripe/webhook-subscription/' . $event_type, $object, $event_id, $created );
					break;
			}
		}

		/**
		 * Handle successful initial or renewal invoices.
		 *
		 * @param array  $invoice
		 * @param string $event_id
		 * @param int    $created
		 *
		 * @return void
		 * @throws Exception
		 */
		protected function handle_invoice_paid( array $invoice, string $event_id, int $created ) {

			$subscription_id = $this->extract_invoice_subscription_id( $invoice );
			$subscription    = $subscription_id ? $this->retrieve_stripe_subscription_data( $subscription_id ) : array();
			$lp_order        = $this->resolve_order_from_stripe_data( $invoice, $subscription_id );
			if ( ! $lp_order ) {
				return;
			}

			$invoice_id         = sanitize_text_field( (string) ( $invoice['id'] ?? '' ) );
			$renewal_key        = $invoice_id ? 'stripe_invoice_' . $invoice_id : '';
			$status             = sanitize_key( (string) ( $subscription['status'] ?? 'active' ) );
			$billing_reason     = sanitize_key( (string) ( $invoice['billing_reason'] ?? '' ) );
			$is_initial_invoice = in_array( $billing_reason, array( 'subscription_create', 'subscription_update' ), true );
			$lp_status          = ( $lp_order->is_completed() && ! $is_initial_invoice ) ? LP_Subscription_Manager::STATUS_RENEWED : $this->map_stripe_subscription_status_to_lp( $status );
			$price_id           = $this->extract_invoice_price_id( $invoice );
			if ( empty( $price_id ) ) {
				$price_id = $this->extract_subscription_price_id( $subscription );
			}

			$webhook_data = $this->build_webhook_data(
				$lp_order,
				$subscription_id,
				$price_id,
				$lp_status,
				array(
					'event_id'       => $event_id,
					'event_type'     => 'invoice.paid',
					'customer_id'    => $this->extract_stripe_id( $invoice['customer'] ?? ( $subscription['customer'] ?? '' ) ),
					'created'        => $created,
					'next_billing'   => absint( $subscription['current_period_end'] ?? 0 ),
					'amount'         => $this->convert_stripe_amount( $invoice['amount_paid'] ?? 0, $invoice['currency'] ?? '' ),
					'currency'       => strtoupper( (string) ( $invoice['currency'] ?? '' ) ),
					'invoice_id'     => $invoice_id,
					'renewal_key'    => $renewal_key,
					'stripe_status'  => $status,
					'billing_reason' => $billing_reason,
				)
			);

			$this->sync_stripe_subscription_meta( $lp_order, $webhook_data );

			if ( $lp_order->is_completed() && $is_initial_invoice ) {
				$this->mark_parent_event_processed( $lp_order->get_id(), $event_id );
				return;
			}

			if ( LP_Subscription_Manager::STATUS_RENEWED === $lp_status ) {
				if ( $this->is_duplicate_renewal( $lp_order->get_id(), $event_id, $renewal_key ) ) {
					return;
				}

				$this->dispatch_subscription_status( $lp_order, $lp_status, $webhook_data );
					$this->mark_latest_renewal_order( $lp_order->get_id(), $event_id, $renewal_key, $subscription_id );
				return;
			}

			if ( in_array( $lp_status, array( LP_Subscription_Manager::STATUS_TRIAL, LP_Subscription_Manager::STATUS_ACTIVATED ), true ) ) {
					$this->dispatch_subscription_status( $lp_order, $lp_status, $webhook_data );
			}
		}

		/**
		 * Handle failed subscription invoices.
		 *
		 * @param array  $invoice
		 * @param string $event_id
		 * @param int    $created
		 *
		 * @return void
		 * @throws Exception
		 */
		protected function handle_invoice_payment_failed( array $invoice, string $event_id, int $created ) {

			$subscription_id = $this->extract_invoice_subscription_id( $invoice );
			$subscription    = $subscription_id ? $this->retrieve_stripe_subscription_data( $subscription_id ) : array();
			$lp_order        = $this->resolve_order_from_stripe_data( $invoice, $subscription_id );
			if ( ! $lp_order || $this->is_duplicate_parent_event( $lp_order->get_id(), $event_id ) ) {
				return;
			}

			$invoice_id   = sanitize_text_field( (string) ( $invoice['id'] ?? '' ) );
			$webhook_data = $this->build_webhook_data(
				$lp_order,
				$subscription_id,
				$this->extract_subscription_price_id( $subscription ),
				LP_Subscription_Manager::STATUS_SUSPENDED,
				array(
					'event_id'      => $event_id,
					'event_type'    => 'invoice.payment_failed',
					'customer_id'   => $this->extract_stripe_id( $invoice['customer'] ?? ( $subscription['customer'] ?? '' ) ),
					'created'       => $created,
					'next_billing'  => absint( $subscription['current_period_end'] ?? 0 ),
					'amount'        => $this->convert_stripe_amount( $invoice['amount_due'] ?? 0, $invoice['currency'] ?? '' ),
					'currency'      => strtoupper( (string) ( $invoice['currency'] ?? '' ) ),
					'invoice_id'    => $invoice_id,
					'stripe_status' => sanitize_key( (string) ( $subscription['status'] ?? 'past_due' ) ),
				)
			);

			update_post_meta( $lp_order->get_id(), self::META_SUBSCRIPTION_STATUS, LP_Subscription_Manager::STATUS_SUSPENDED );
			$this->sync_stripe_subscription_meta( $lp_order, $webhook_data );
			$this->dispatch_subscription_status( $lp_order, LP_Subscription_Manager::STATUS_SUSPENDED, $webhook_data );
		}

		/**
		 * Handle Stripe subscription updates.
		 *
		 * @param array  $subscription
		 * @param string $event_id
		 * @param int    $created
		 *
		 * @return void
		 * @throws Exception
		 */
		protected function handle_subscription_updated( array $subscription, string $event_id, int $created ) {

			$this->handle_subscription_status_event( $subscription, $event_id, $created, 'customer.subscription.updated' );
		}

		/**
		 * Handle Stripe subscription deletions.
		 *
		 * @param array  $subscription
		 * @param string $event_id
		 * @param int    $created
		 *
		 * @return void
		 * @throws Exception
		 */
		protected function handle_subscription_deleted( array $subscription, string $event_id, int $created ) {

			$subscription['status'] = 'canceled';
			$this->handle_subscription_status_event( $subscription, $event_id, $created, 'customer.subscription.deleted' );
		}

		/**
		 * Shared subscription status event handler.
		 *
		 * @param array  $subscription
		 * @param string $event_id
		 * @param int    $created
		 * @param string $event_type
		 *
		 * @return void
		 * @throws Exception
		 */
		protected function handle_subscription_status_event( array $subscription, string $event_id, int $created, string $event_type ) {

			$subscription_id = $this->extract_stripe_id( $subscription['id'] ?? '' );
			$lp_order        = $this->resolve_order_from_stripe_data( $subscription, $subscription_id );
			if ( ! $lp_order || $this->is_duplicate_parent_event( $lp_order->get_id(), $event_id ) ) {
				return;
			}

			$stripe_status = sanitize_key( (string) ( $subscription['status'] ?? '' ) );
			$lp_status     = $this->map_stripe_subscription_status_to_lp( $stripe_status );
			if ( empty( $lp_status ) ) {
				$this->mark_parent_event_processed( $lp_order->get_id(), $event_id );
				return;
			}

			$webhook_data = $this->build_webhook_data(
				$lp_order,
				$subscription_id,
				$this->extract_subscription_price_id( $subscription ),
				$lp_status,
				array(
					'event_id'      => $event_id,
					'event_type'    => $event_type,
					'customer_id'   => $this->extract_stripe_id( $subscription['customer'] ?? '' ),
					'created'       => $created,
					'next_billing'  => absint( $subscription['current_period_end'] ?? 0 ),
					'stripe_status' => $stripe_status,
				)
			);

			$this->sync_stripe_subscription_meta( $lp_order, $webhook_data );

			if ( in_array( $lp_status, array( LP_Subscription_Manager::STATUS_TRIAL, LP_Subscription_Manager::STATUS_ACTIVATED ), true ) && $lp_order->is_completed() ) {
				update_post_meta( $lp_order->get_id(), self::META_SUBSCRIPTION_STATUS, $lp_status );
					$this->mark_parent_event_processed( $lp_order->get_id(), $event_id );
				return;
			}

			if ( in_array( $lp_status, array( LP_Subscription_Manager::STATUS_CANCELLED, LP_Subscription_Manager::STATUS_EXPIRED, LP_Subscription_Manager::STATUS_SUSPENDED ), true ) ) {
				update_post_meta( $lp_order->get_id(), self::META_SUBSCRIPTION_STATUS, $lp_status );
			}

			$this->dispatch_subscription_status( $lp_order, $lp_status, $webhook_data );
		}

		/**
		 * Dispatch normalized status into LearnPress shared subscription handler.
		 *
		 * @param LP_Order $lp_order
		 * @param string   $lp_status
		 * @param array    $webhook_data
		 *
		 * @return void
		 * @throws Exception
		 */
		protected function dispatch_subscription_status( LP_Order $lp_order, string $lp_status, array $webhook_data ) {

			$event_id = sanitize_text_field( (string) ( $webhook_data['stripe_event_id'] ?? '' ) );
			if ( $this->is_duplicate_parent_event( $lp_order->get_id(), $event_id ) ) {
				return;
			}

			$lp_order->set_data( 'payment_method', $this->id );
			$lp_order->set_data( 'payment_method_title', $this->method_title );
			$this->normalize_subscription_data( $webhook_data );
			$this->process_subscription_by_status( $lp_order, $lp_status, $webhook_data );
			$this->mark_parent_event_processed( $lp_order->get_id(), $event_id );
		}

		/**
		 * Build LearnPress webhook data from Stripe fields.
		 *
		 * @param LP_Order $lp_order
		 * @param string   $subscription_id
		 * @param string   $price_id
		 * @param string   $lp_status
		 * @param array    $data
		 *
		 * @return array
		 */
		protected function build_webhook_data( LP_Order $lp_order, string $subscription_id, string $price_id, string $lp_status, array $data = array() ): array {

			return array(
				'lp_order_id'                => $lp_order->get_id(),
				'lp_plan_id'                 => $price_id,
				'lp_subscription_id'         => $subscription_id,
				'lp_subscription_status'     => $lp_status,
				'lp_subscription_amount'     => $data['amount'] ?? 0,
				'lp_subscription_currency'   => $data['currency'] ?? '',
				'create_time'                => $this->format_stripe_timestamp( absint( $data['created'] ?? 0 ) ),
				'next_billing_time'          => $this->format_stripe_timestamp( absint( $data['next_billing'] ?? 0 ) ),
				'stripe_event_id'            => sanitize_text_field( (string) ( $data['event_id'] ?? '' ) ),
				'stripe_event_type'          => sanitize_text_field( (string) ( $data['event_type'] ?? '' ) ),
				'stripe_customer_id'         => sanitize_text_field( (string) ( $data['customer_id'] ?? '' ) ),
				'stripe_invoice_id'          => sanitize_text_field( (string) ( $data['invoice_id'] ?? '' ) ),
				'stripe_renewal_key'         => sanitize_text_field( (string) ( $data['renewal_key'] ?? '' ) ),
				'stripe_subscription_status' => sanitize_key( (string) ( $data['stripe_status'] ?? '' ) ),
			);
		}

		/**
		 * Persist Stripe identifiers and the latest normalized payload.
		 *
		 * @param LP_Order $lp_order
		 * @param array    $webhook_data
		 *
		 * @return void
		 */
		protected function sync_stripe_subscription_meta( LP_Order $lp_order, array $webhook_data ) {

			$order_id = $lp_order->get_id();
			if ( ! empty( $webhook_data['lp_subscription_id'] ) ) {
				update_post_meta( $order_id, self::META_SUBSCRIPTION_ID, sanitize_text_field( (string) $webhook_data['lp_subscription_id'] ) );
			}
			if ( ! empty( $webhook_data['stripe_customer_id'] ) ) {
				update_post_meta( $order_id, self::META_SUBSCRIPTION_CUSTOMER_ID, sanitize_text_field( (string) $webhook_data['stripe_customer_id'] ) );
			}
			if ( ! empty( $webhook_data['lp_plan_id'] ) ) {
				update_post_meta( $order_id, self::META_SUBSCRIPTION_PLAN_ID, sanitize_text_field( (string) $webhook_data['lp_plan_id'] ) );
			}
			if ( ! empty( $webhook_data['stripe_subscription_status'] ) ) {
				update_post_meta( $order_id, '_lp_stripe_subscription_status', sanitize_key( (string) $webhook_data['stripe_subscription_status'] ) );
			}
			if ( ! empty( $webhook_data['stripe_invoice_id'] ) ) {
				update_post_meta( $order_id, '_lp_stripe_latest_invoice_id', sanitize_text_field( (string) $webhook_data['stripe_invoice_id'] ) );
			}

			update_post_meta( $order_id, self::META_SUBSCRIPTION_DATA_RECEIVER, wp_json_encode( $webhook_data, JSON_UNESCAPED_UNICODE ) );
		}

		/**
		 * Resolve an LP order from Stripe metadata or saved subscription id.
		 *
		 * @param array  $stripe_data
		 * @param string $subscription_id
		 *
		 * @return LP_Order|false
		 */
		protected function resolve_order_from_stripe_data( array $stripe_data, string $subscription_id = '' ) {

			$order_id = absint( $stripe_data['client_reference_id'] ?? 0 );
			if ( empty( $order_id ) ) {
				$metadata = $this->extract_metadata( $stripe_data );
				$order_id = absint( $metadata['lp_order_id'] ?? 0 );
			}

			if ( empty( $order_id ) && ! empty( $stripe_data['parent']['subscription_details']['metadata'] ) ) {
				$metadata = (array) ( $stripe_data['parent']['subscription_details']['metadata'] ?? array() );
				$order_id = absint( $metadata['lp_order_id'] ?? 0 );
			}

			if ( empty( $order_id ) && ! empty( $stripe_data['subscription_details']['metadata'] ) ) {
				$metadata = (array) ( $stripe_data['subscription_details']['metadata'] ?? array() );
				$order_id = absint( $metadata['lp_order_id'] ?? 0 );
			}

			if ( ! empty( $order_id ) ) {
				$order = learn_press_get_order( $order_id );
				if ( $order instanceof LP_Order ) {
					return $order;
				}
			}

			if ( ! empty( $subscription_id ) ) {
				return $this->find_order_by_subscription_id( $subscription_id );
			}

			return false;
		}

		/**
		 * Find parent order by saved Stripe subscription id.
		 *
		 * @param string $subscription_id
		 *
		 * @return LP_Order|false
		 */
		protected function find_order_by_subscription_id( string $subscription_id ) {

			$order_ids = get_posts(
				array(
					'post_type'      => LP_ORDER_CPT,
					'post_status'    => 'any',
					'posts_per_page' => 1,
					'fields'         => 'ids',
					'meta_query'     => array(
						array(
							'key'   => self::META_SUBSCRIPTION_ID,
							'value' => sanitize_text_field( $subscription_id ),
						),
					),
				)
			);

			if ( empty( $order_ids ) ) {
				return false;
			}

			return learn_press_get_order( absint( $order_ids[0] ) );
		}

		/**
		 * Retrieve Stripe subscription as array.
		 *
		 * @param string $subscription_id
		 *
		 * @return array
		 * @throws ApiErrorException
		 */
		protected function retrieve_stripe_subscription_data( string $subscription_id ): array {

			if ( empty( $subscription_id ) ) {
				return array();
			}

			$stripe       = new StripeClient( $this->secret_key );
			$subscription = $stripe->subscriptions->retrieve(
				$subscription_id,
				array(
					'expand' => array( 'latest_invoice' ),
				)
			);

			return $subscription->toArray();
		}

		/**
		 * Sanitize metadata before sending to Stripe.
		 *
		 * @param array $metadata
		 *
		 * @return array
		 */
		protected function sanitize_stripe_metadata( array $metadata ): array {

			$sanitized = array();
			foreach ( $metadata as $key => $value ) {
				if ( ! is_scalar( $value ) ) {
					continue;
				}

				$key = sanitize_key( (string) $key );
				if ( '' === $key ) {
					continue;
				}

				$sanitized[ $key ] = sanitize_text_field( (string) $value );
			}

			return $sanitized;
		}

		/**
		 * Build a normalized plan summary from a Stripe Price.
		 *
		 * @param array $price
		 *
		 * @return array
		 */
		protected function build_stripe_price_summary( array $price ): array {
			$recurring  = $price['recurring'] ?? array();
			$product_id = $price['product']['id'] ?? '';
			$currency   = strtoupper( $price['currency'] ?? '' );
			$amount     = $this->convert_stripe_amount( $price['unit_amount'] ?? 0, $currency );
			$active     = $price['active'] ?? false;

			return array(
				'id'                => $price['id'] ?? '',
				'product_id'        => $product_id,
				'amount'            => $amount,
				'currency'          => $currency,
				'interval'          => $recurring['interval'] ?? '',
				'interval_count'    => max( 1, absint( $recurring['interval_count'] ?? 1 ) ),
				'trial_period_days' => absint( $recurring['trial_period_days'] ?? 0 ),
				'setup_fee'         => 0.0,
				'status'            => $active ? 'ACTIVE' : 'INACTIVE',
				'type'              => sanitize_key( (string) ( $price['type'] ?? '' ) ),
			);
		}

		/**
		 * Extract a Stripe id from a string or object.
		 *
		 * @param mixed $value
		 *
		 * @return string
		 */
		protected function extract_stripe_id( $value ): string {

			if ( is_string( $value ) ) {
				return sanitize_text_field( $value );
			}

			// Arrays and Stripe objects (StripeObject implements ArrayAccess) both expose ['id'].
			if ( is_array( $value ) || $value instanceof StripeObject ) {
				return sanitize_text_field( (string) ( $value['id'] ?? '' ) );
			}

			return '';
		}

		/**
		 * Extract metadata as a plain array.
		 *
		 * @param array $stripe_data
		 *
		 * @return array
		 */
		protected function extract_metadata( array $stripe_data ): array {

			$metadata = $stripe_data['metadata'] ?? array();
			if ( empty( $metadata ) ) {
				return array();
			}

			if ( $metadata instanceof StripeObject ) {
				$metadata = $metadata->toArray();
			}

			return is_array( $metadata ) ? $metadata : array();
		}

		/**
		 * Extract subscription id from invoice shapes across Stripe API versions.
		 *
		 * @param array $invoice
		 *
		 * @return string
		 */
		protected function extract_invoice_subscription_id( array $invoice ): string {

			$subscription_id = $this->extract_stripe_id( $invoice['subscription'] ?? '' );
			if ( ! empty( $subscription_id ) ) {
					return $subscription_id;
			}

			return $this->extract_stripe_id( $invoice['parent']['subscription_details']['subscription'] ?? '' );
		}

		/**
		 * Extract price id from a subscription object.
		 *
		 * @param array $subscription
		 *
		 * @return string
		 */
		protected function extract_subscription_price_id( array $subscription ): string {

			return sanitize_text_field(
				(string) (
				$subscription['items']['data'][0]['price']['id'] ??
					$subscription['plan']['id'] ??
				''
				)
			);
		}

		/**
		 * Extract price id from an invoice object.
		 *
		 * @param array $invoice
		 *
		 * @return string
		 */
		protected function extract_invoice_price_id( array $invoice ): string {

			return sanitize_text_field(
				(string) (
				$invoice['lines']['data'][0]['price']['id'] ??
				$invoice['lines']['data'][0]['plan']['id'] ??
					''
				)
			);
		}

		/**
		 * Map Stripe subscription status to LearnPress status.
		 *
		 * @param string $stripe_status
		 *
		 * @return string
		 */
		protected function map_stripe_subscription_status_to_lp( string $stripe_status ): string {

			switch ( $stripe_status ) {
				case 'trialing':
					return LP_Subscription_Manager::STATUS_TRIAL;
				case 'active':
					return LP_Subscription_Manager::STATUS_ACTIVATED;
				case 'canceled':
					return LP_Subscription_Manager::STATUS_CANCELLED;
				case 'incomplete_expired':
					return LP_Subscription_Manager::STATUS_EXPIRED;
				case 'past_due':
				case 'paused':
				case 'unpaid':
					return LP_Subscription_Manager::STATUS_SUSPENDED;
				default:
					return '';
			}
		}

		/**
		 * Convert Stripe minor units to LearnPress order amount units.
		 *
		 * @param int|float $amount
		 * @param string    $currency
		 *
		 * @return float|int
		 */
		protected function convert_stripe_amount( $amount, string $currency = '' ) {

			$amount = (float) $amount;
			if ( $amount <= 0 ) {
				return 0;
			}

			$stripe_support_rules = $this->get_stripe_currency_rules();
			$currency             = strtoupper( $currency );
			if ( in_array( $currency, $stripe_support_rules['zero-decimal'], true ) ) {
				return $amount;
			}

			if ( in_array( $currency, $stripe_support_rules['three-decimal'], true ) ) {
				return $amount / 1000;
			}

			return $amount / 100;
		}

		/**
		 * Format a Stripe timestamp for LearnPress order notes.
		 *
		 * @param int $timestamp
		 *
		 * @return string
		 */
		protected function format_stripe_timestamp( int $timestamp ): string {

			if ( empty( $timestamp ) ) {
					return '';
			}

			return gmdate( 'c', $timestamp );
		}

		/**
		 * Check duplicate parent-level event.
		 *
		 * @param int    $order_id
		 * @param string $event_id
		 *
		 * @return bool
		 */
		protected function is_duplicate_parent_event( int $order_id, string $event_id ): bool {

			if ( empty( $event_id ) ) {
				return false;
			}

			$last_event_id = get_post_meta( $order_id, self::META_SUBSCRIPTION_LAST_EVENT_ID, true );
			return $last_event_id === $event_id;
		}

		/**
		 * Mark parent event processed.
		 *
		 * @param int    $order_id
		 * @param string $event_id
		 *
		 * @return void
		 */
		protected function mark_parent_event_processed( int $order_id, string $event_id ) {

			if ( ! empty( $event_id ) ) {
				update_post_meta( $order_id, self::META_SUBSCRIPTION_LAST_EVENT_ID, sanitize_text_field( $event_id ) );
			}
		}

		/**
		 * Check duplicate renewal by event id or invoice-derived key.
		 *
		 * @param int    $parent_order_id
		 * @param string $event_id
		 * @param string $renewal_key
		 *
		 * @return bool
		 */
		protected function is_duplicate_renewal( int $parent_order_id, string $event_id, string $renewal_key ): bool {

			if ( $this->is_duplicate_parent_event( $parent_order_id, $event_id ) ) {
					return true;
			}

			if ( empty( $renewal_key ) ) {
				return false;
			}

			$order_ids = get_posts(
				array(
					'post_type'      => LP_ORDER_CPT,
					'post_status'    => 'any',
					'post_parent'    => $parent_order_id,
					'posts_per_page' => 1,
					'fields'         => 'ids',
					'meta_query'     => array(
						array(
							'key'   => self::META_SUBSCRIPTION_RENEWAL_KEY,
							'value' => sanitize_text_field( $renewal_key ),
						),
					),
				)
			);

			return ! empty( $order_ids );
		}

		/**
		 * Store dedupe data on the latest renewal order created by shared handler.
		 *
		 * @param int    $parent_order_id
		 * @param string $event_id
		 * @param string $renewal_key
		 * @param string $subscription_id
		 *
		 * @return void
		 */
		protected function mark_latest_renewal_order( int $parent_order_id, string $event_id, string $renewal_key, string $subscription_id ) {

			$order_ids = get_posts(
				array(
					'post_type'      => LP_ORDER_CPT,
					'post_status'    => 'any',
					'post_parent'    => $parent_order_id,
					'posts_per_page' => 1,
					'fields'         => 'ids',
					'orderby'        => 'ID',
					'order'          => 'DESC',
				)
			);

			if ( empty( $order_ids ) ) {
				return;
			}

			$renewal_order_id = absint( $order_ids[0] );
			if ( ! empty( $event_id ) ) {
				update_post_meta( $renewal_order_id, self::META_SUBSCRIPTION_EVENT_ID, sanitize_text_field( $event_id ) );
			}
			if ( ! empty( $renewal_key ) ) {
				update_post_meta( $renewal_order_id, self::META_SUBSCRIPTION_RENEWAL_KEY, sanitize_text_field( $renewal_key ) );
			}
			if ( ! empty( $subscription_id ) ) {
				update_post_meta( $renewal_order_id, self::META_SUBSCRIPTION_ID, sanitize_text_field( $subscription_id ) );
			}
		}

		/**
		 * Retrieve stripe session.
		 * Check status payment by checkout session id.
		 *
		 * @throws ApiErrorException
		 * @version 1.0.0
		 * @since 4.0.2
		 */
		public function retrieve_stripe_session( string $checkout_session_id ) {

			$stripe   = new StripeClient( $this->secret_key );
			$retrieve = $stripe->checkout->sessions->retrieve( $checkout_session_id );
			if ( ( $retrieve->mode ?? '' ) === 'subscription' ) {
				return;
			}

			if ( $retrieve->payment_status === Session::PAYMENT_STATUS_PAID ) {
				$lp_order_id = $retrieve->metadata->lp_order_id ?? 0;
				$lp_order    = learn_press_get_order( $lp_order_id );
				if ( $lp_order->is_completed() ) {
					return;
				}
				$lp_order->payment_complete();
			}
		}

		/**
		 * Create Stripe payment intent.
		 *
		 * @return Stripe\PaymentIntent|null
		 * @version 1.0.1
		 * @since 4.0.2
		 */
		public function create_payment_intent() {
			$cart                  = LearnPress::instance()->cart;
			$stripe_payment_intent = null;

			try {
				if ( ! $cart || $cart->is_empty() ) {
					throw new Exception( __( 'Cart is empty.', 'learnpress-stripe' ) );
				}

				$cart_total = $cart->calculate_totals();
				if ( $cart_total->total <= 0 ) {
					throw new Exception( __( 'Total amount must be greater than 0.', 'learnpress-stripe' ) );
				}

				$payment_intent_exist = LearnPress::instance()->session->get( 'stripe_awaiting_payment_intent', '' );
				// if ( empty( $payment_intent_exist ) ) {
				$stripe                = new StripeClient( $this->secret_key );
				$stripe_payment_intent = $stripe->paymentIntents->create(
					array(
						'amount'                    => $this->calculate_order_amount( $cart_total->total ),
						'currency'                  => strtolower( learn_press_get_currency() ),
						'automatic_payment_methods' => array( 'enabled' => true ),
					)
				);
				LearnPress::instance()->session->set( 'stripe_awaiting_payment_intent', $stripe_payment_intent, true );
				/*
				} else {
					$stripe_payment_intent = $payment_intent_exist;
				}*/
			} catch ( Throwable $e ) {
				$stripe_payment_intent = new WP_Error( 'stripe_payment_intent_error', $e->getMessage() );
				// error_log( __METHOD__ . $e->getMessage() );
			}

			return $stripe_payment_intent;
		}

		/**
		 * Update payment intent Stripe
		 *
		 * @throws ApiErrorException
		 * @version 1.0.0
		 * @since 4.0.2
		 */
		public function update_payment_intent( string $pi, int $order_id ) {
			$stripe     = new StripeClient( $this->secret_key );
			$cart       = LearnPress::instance()->cart;
			$cart_total = $cart->calculate_totals();
			$amount     = $this->calculate_order_amount( $cart_total->total );
			$stripe->paymentIntents->update(
				$pi,
				array(
					'amount'   => $amount,
					'metadata' => array(
						'lp_order_id' => $order_id,
					),
				)
			);
		}

		/**
		 * Check Stripe payment intent.
		 *
		 * @throws Exception
		 * @version 1.0.0
		 * @since 4.0.2
		 */
		public function stripe_retrieve_payment_intent( $payment_intent ) {

			$stripe                  = new StripeClient( $this->secret_key );
			$payment_intent_retrieve = $stripe->paymentIntents->retrieve( $payment_intent, array() );
			if ( $payment_intent_retrieve->status === 'succeeded' ) {
				$order_id = $payment_intent_retrieve->metadata->lp_order_id ?? 0;
				$lp_order = learn_press_get_order( $order_id );
				if ( $lp_order->is_completed() ) {
					return;
				}
				$lp_order->set_data( 'payment_method', $this->id );
				$lp_order->set_data( 'payment_method_title', $this->method_title );
				$lp_order->payment_complete();

				LearnPress::instance()->cart->empty_cart();
				LearnPress::instance()->session->remove( 'stripe_awaiting_payment_intent', true );
			} else {
				throw new Exception( $payment_intent_retrieve->status );
			}
		}

		/**
		 * Build a signed local URL that creates a Stripe Billing Portal session on demand.
		 *
		 * @param LP_Order $order
		 *
		 * @return string
		 */
		public function get_manage_subscription_url( LP_Order $order ): string {

			$customer_id = get_post_meta( $order->get_id(), self::META_SUBSCRIPTION_CUSTOMER_ID, true );
			if ( empty( $customer_id ) ) {
				return parent::get_manage_subscription_url( $order );
			}

			$url = add_query_arg(
				array(
					'lp-stripe-manage-subscription' => 1,
					'order_id'                      => $order->get_id(),
				),
				home_url( '/' )
			);

			return wp_nonce_url( $url, 'lp_stripe_manage_subscription_' . $order->get_id() );
		}

		/**
		 * Add manage subscription action for LearnPress subscription statuses used by core processor.
		 *
		 * @param array $actions
		 * @param int   $order_id
		 *
		 * @return array
		 */
		public function add_manage_subscription_order_action( array $actions, int $order_id ): array {

			$order = learn_press_get_order( $order_id );
			if ( ! $order instanceof LP_Order ) {
				return $actions;
			}

			$payment_method = sanitize_key( (string) get_post_meta( $order_id, '_payment_method', true ) );
			if ( $payment_method !== $this->id ) {
				return $actions;
			}

			$subscription_status = sanitize_key( (string) get_post_meta( $order_id, self::META_SUBSCRIPTION_STATUS, true ) );
			if ( ! in_array(
				$subscription_status,
				array(
					LP_Subscription_Manager::STATUS_ACTIVATED,
					LP_Subscription_Manager::STATUS_TRIAL,
					LP_Subscription_Manager::STATUS_SUSPENDED,
				),
				true
			) ) {
				return $actions;
			}

			$manage_url = $this->get_manage_subscription_url( $order );
			if ( empty( $manage_url ) ) {
				return $actions;
			}

			$actions['manage-subscription'] = array(
				'url'  => esc_url_raw( $manage_url ),
				'text' => __( 'Manage subscription', 'learnpress-stripe' ),
			);

			return $actions;
		}

		/**
		 * Redirect authorized users to Stripe Billing Portal.
		 *
		 * @return void
		 */
		public function maybe_redirect_to_billing_portal() {

			if ( is_admin() || ! LP_Request::get_param( 'lp-stripe-manage-subscription', 0, 'int', 'get' ) ) {
				return;
			}

			$order_id = absint( LP_Request::get_param( 'order_id', 0, 'int', 'get' ) );
			$nonce    = LP_Request::get_param( '_wpnonce', '', 'text', 'get' );
			if ( empty( $order_id ) || ! wp_verify_nonce( $nonce, 'lp_stripe_manage_subscription_' . $order_id ) ) {
				wp_die( esc_html__( 'Invalid subscription management request.', 'learnpress-stripe' ) );
			}

			$order = learn_press_get_order( $order_id );
			if ( ! $order instanceof LP_Order || ! $this->user_can_manage_subscription_order( $order ) ) {
				wp_die( esc_html__( 'You are not allowed to manage this subscription.', 'learnpress-stripe' ) );
			}

			$customer_id = sanitize_text_field( (string) get_post_meta( $order_id, self::META_SUBSCRIPTION_CUSTOMER_ID, true ) );
			if ( empty( $customer_id ) ) {
				wp_die( esc_html__( 'Stripe customer is missing for this subscription.', 'learnpress-stripe' ) );
			}

			try {
				$return_url = $order->get_view_order_url();
				if ( empty( $return_url ) ) {
					$return_url = home_url( '/' );
				}

				$stripe  = new StripeClient( $this->secret_key );
				$session = $stripe->billingPortal->sessions->create(
					array(
						'customer'   => $customer_id,
						'return_url' => $return_url,
					)
				);

					$portal_url = $session->url ?? '';
				if ( ! $this->is_safe_stripe_url( $portal_url ) ) {
						throw new Exception( __( 'Invalid Stripe Billing Portal URL.', 'learnpress-stripe' ) );
				}

				// External redirect is deliberate after validating the host returned by Stripe.
					wp_redirect( esc_url_raw( $portal_url ) );
				exit;
			} catch ( Throwable $e ) {
				error_log( $e->getMessage() );
				wp_die( esc_html__( 'Unable to open Stripe Billing Portal.', 'learnpress-stripe' ) );
			}
		}

		/**
		 * Check whether current user may manage an order subscription.
		 *
		 * @param LP_Order $order
		 *
		 * @return bool
		 */
		protected function user_can_manage_subscription_order( LP_Order $order ): bool {

			$current_user_id = get_current_user_id();
			if ( ! empty( $current_user_id ) && (int) $order->get_user_id() === (int) $current_user_id ) {
				return true;
			}

			return current_user_can( 'manage_options' ) || current_user_can( 'edit_post', $order->get_id() );
		}

		/**
		 * Validate Stripe-hosted redirect URL.
		 *
		 * @param string $url
		 *
		 * @return bool
		 */
		protected function is_safe_stripe_url( string $url ): bool {

			$scheme = wp_parse_url( $url, PHP_URL_SCHEME );
			$host   = wp_parse_url( $url, PHP_URL_HOST );

			return 'https' === $scheme && is_string( $host ) && ( 'stripe.com' === $host || substr( $host, -11 ) === '.stripe.com' );
		}

		/**
		 * Get stripe currency rules.
		 * For calculate order amount with currency.
		 *
		 * @docs https://docs.stripe.com/currencies#zero-decimal
		 *
		 * @return array stripe currency list
		 * @since 4.0.3
		 */
		public function get_stripe_currency_rules(): array {
			return include LP_ADDON_STRIPE_PAYMENT_PATH . '/config/stripe-currency-rules.php';
		}

		/**
		 * Calculate order amount with currency rule.
		 *
		 * @param float $amount
		 *
		 * @return int|float
		 * @version 1.0.0
		 * @since 4.0.3
		 */
		public function calculate_order_amount( float $amount = 0 ) {

			return $this->calculate_stripe_amount_for_currency( $amount, learn_press_get_currency() );
		}

		/**
		 * Calculate Stripe minor-unit amount for a specific currency.
		 *
		 * @param float  $amount
		 * @param string $currency
		 *
		 * @return int|float
		 */
		protected function calculate_stripe_amount_for_currency( float $amount = 0, string $currency = '' ) {
			$stripe_support_rules = $this->get_stripe_currency_rules();
			$currency             = strtoupper( $currency );
			if ( empty( $currency ) ) {
				$currency = learn_press_get_currency();
			}
			$currency = strtoupper( $currency );

			if ( in_array( $currency, $stripe_support_rules['zero-decimal'] ) ) {
				$order_amount = (int) $amount;
			} elseif ( in_array( $currency, $stripe_support_rules['three-decimal'] ) ) {
					$order_amount = round( $amount, 2 ) * 1000;
			} elseif ( in_array( $currency, $stripe_support_rules['special-case'] ) ) {
				$order_amount = (int) $amount * 100;
			} else {
				$order_amount = $amount * 100;
			}

			return $order_amount;
		}
	}
}
