<?php

use Automattic\WooCommerce\Utilities\FeaturesUtil;
use LearnPress\Helpers\Singleton;
use LearnPress\Helpers\Template;
use LearnPress\Models\CourseModel;
use LearnPress\Models\UserModel;
use LearnPress\WooPayment\WooGateway;
use LearnPress\WooPayment\LPWooOrderHandler;

defined( 'ABSPATH' ) || exit();

class LP_WC_Hooks {
	use Singleton;

	public function init() {
		$this->hooks();
	}

	protected function hooks() {
		//add_filter( 'woocommerce_add_to_cart_redirect', array( $this, 'redirect_to_checkout' ), 10, 1 );
		add_action( 'woocommerce_order_status_changed', array( $this, 'lp_order_create_or_update' ), 10, 3 );
		//add_action( 'learn-press/order/status-changed', array( $this, 'lp_woo_update_status_order_for_woo' ), 10, 4 );
		//add_action( 'woocommerce_checkout_update_order_meta', array( $this, 'create_lp_order' ), 10, 2 );
		//add_action( 'woocommerce_store_api_checkout_update_order_meta', array( $this, 'create_lp_order_from_api' ), 10, 1 );
		//add_action( 'woocommerce_after_order_object_save', array( $this, 'create_lp_order' ), 10, 2 );
		// For case when payment via stripe of woo - completed order didn't complete LP Order
		//add_action( 'woocommerce_thankyou', array( $this, 'update_lp_order_status' ), 10, 1 );
		add_action( 'wp_enqueue_scripts', array( $this, 'load_assets' ) );
		add_action( 'admin_notices', array( $this, 'wc_order_notice' ), 99 );
		add_filter( 'learn-press/order-payment-method-title', array( $this, 'lp_woo_payment_method_title' ), 10, 2 );
		add_filter( 'woocommerce_cart_item_quantity', array( $this, 'disable_quantity_box' ), 10, 3 );
		// Add LP Order key on the email Woo
		add_action( 'woocommerce_email_order_meta', array( $this, 'show_lp_order_key_on_wc_order' ), 99 );
		// Add Lp Order key on the order woo detail
		add_action(
			'woocommerce_admin_order_data_after_order_details',
			array(
				$this,
				'show_lp_order_key_on_wc_order',
			),
			10
		);
		// declare_compatibility for Woo new from 8.3.
		add_action(
			'before_woocommerce_init',
			function () {
				if ( class_exists( FeaturesUtil::class ) ) {
					FeaturesUtil::declare_compatibility( 'custom_order_tables', LP_ADDON_WOO_PAYMENT_FILE );
				}
			}
		);

		if ( ! WooGateway::is_by_courses_via_product() ) {
			add_filter(
				'woocommerce_json_search_found_products',
				array(
					$this,
					'wc_json_search_found_products_and_courses',
				)
			);
			add_filter( 'woocommerce_get_order_item_classname', array( $this, 'get_classname_lp_wc_order' ), 10, 3 );
			add_action( 'woocommerce_checkout_create_order_line_item', array( $this, 'order_item_line' ), 10, 4 );
			add_filter( 'woocommerce_product_class', array( $this, 'product_class' ), 10, 4 );
			add_filter( 'woocommerce_ajax_order_item', array( $this, 'add_manually_course_to_wc_order' ), 10, 4 );
			// Remove button purchase course
			//remove_action( 'learn-press/course-buttons', Learnpress::instance()->template( 'course' )->func( 'course_purchase_button' ), 10 );
			// Add button "add to cart" on archive course page
			/*add_action( 'learn-press/course-buttons', array( $this, 'btn_add_to_cart' ) );
			if ( version_compare( LEARNPRESS_VERSION, '4.2.6-beta-0', '<' ) ) {
				add_action( 'learn-press/after-courses-loop-item', array( $this, 'btn_add_to_cart' ), 55 );
			} else {
				add_action(
					'learn-press/list-courses/layout/item/section/bottom',
					array(
						$this,
						'add_btn_add_to_cart',
					),
					10,
					2
				);
			}*/

			// Set LP course order item total price when create order from WC order
			add_filter( 'learnpress/order/item/total', array( $this, 'set_item_total_course' ), 10, 4 );
		}

		// Run hook button purchase course after hook of PMS.
		add_filter( 'learn-press/course/html-button-purchase', array( $this, 'button_add_to_cart' ), 12, 3 );
		add_filter( 'learn-press/course/html-button-enroll', array( $this, 'button_add_to_cart' ), 10, 3 );

		// Add tab to profile.
		add_filter( 'learn-press/profile-tabs', array( $this, 'profile_tabs_woo_order' ) );

		// Update customer_id for woocommerce_order when LP user enter Recover key
		add_action(
			'learn-press/order/recovered-successful',
			array(
				$this,
				'update_woocommerce_order_by_recovered_key',
			),
			10,
			2
		);

		// Notice when have course with no slots
		add_action( 'woocommerce_before_checkout_form', array( $this, 'check_product_in_checkout' ) );

		// Add notice in single product
		add_action( 'woocommerce_single_product_summary', array( $this, 'message_course_out_stock' ), 5 );

		// Add list course sold out in product
		add_action( 'woocommerce_review_order_before_payment', array( $this, 'list_course_checkout' ) );

		add_filter( 'learn_press/order_item_name', array( $this, 'name_course_order' ), 10, 3 );
		add_filter( 'learn-press/order-item-link', array( $this, 'name_course_order' ), 10, 3 );
		add_filter( 'learn-press/order/item-name', array( $this, 'name_course_order' ), 10, 3 );

		// Add hook for payment gateways support type product.
		add_filter(
			'wcpay_payment_request_supported_types',
			function ( $types ) {
				$types[] = LP_COURSE_CPT;

				return $types;
			}
		);
		// Add hook for stripe payment gateways support type product.
		add_filter(
			'wc_stripe_payment_request_supported_types',
			function ( $types ) {
				$types[] = LP_COURSE_CPT;

				return $types;
			}
		);
	}

	/**
	 * Show LP Order key in order Woo
	 *
	 * @param WC_Order $wc_order
	 *
	 * @return void
	 * @sicne 4.0.7
	 * @author hoangvlm
	 * @version 1.0.1
	 */
	public function show_lp_order_key_on_wc_order( $wc_order ) {
		$order_id = $wc_order->get_id();

		if ( ! $order_id ) {
			return;
		}

		$lp_order_id = $wc_order->get_meta( LPWooOrderHandler::$KEY_META_ID );
		if ( ! $lp_order_id ) {
			return;
		}

		$lp_order = learn_press_get_order( $lp_order_id );
		if ( ! $lp_order ) {
			return;
		}

		$order_key = $lp_order->get_order_key();

		ob_start();
		?>
		<br class="clear"/>
		<div class="lp_woo_order_key">
			<h3><?php esc_html_e( 'LP Order:', 'learnpress-woo-payment' ); ?>
				<span style="font-weight:normal; color: rgba(0,128,0,0.61)">
					<?php echo sprintf( '<a href="%s">%s</a>', $lp_order->get_edit_link(), $lp_order->get_order_number() ); ?>
				</span>
			</h3>
			<h3><?php esc_html_e( 'LP Order key:', 'learnpress-woo-payment' ); ?>
				<span style="font-weight:normal; color: rgba(0,128,0,0.61)">
					<?php echo $order_key; ?>
				</span>
			</h3>
		</div>
		<?php
		echo ob_get_clean();
	}

	/**
	 * Enable redirect checkout
	 *
	 * @param string|bool $url
	 *
	 * @return string|bool
	 */
	public function redirect_to_checkout( $url ) {
		if ( 'yes' === LP_Settings::get_option( 'woo-payment_redirect_to_checkout', 'no' ) ) {
			$url = wc_get_checkout_url();
		}

		return $url;
	}

	/**
	 * Get the product class name.
	 *
	 * @param string
	 * @param string
	 * @param string
	 * @param int
	 *
	 * @return string
	 */
	public function product_class( $classname, $product_type, $post_type, $product_id ): string {
		if ( LP_COURSE_CPT === get_post_type( $product_id ) ) {
			$classname = 'WC_Product_LP_Course';
		}

		return $classname;
	}

	/**
	 * Disable select quantity product has post_type 'lp_course'
	 *
	 * @param string $product_quantity
	 * @param string $cart_item_key
	 * @param array $cart_item
	 *
	 * @return string
	 */
	public function disable_quantity_box( $product_quantity, string $cart_item_key, array $cart_item ): string {
		$product_id       = $cart_item['product_id'] ?? 0;
		$quantity_disable = false;

		if ( get_class( $cart_item['data'] ) === 'WC_Product_LP_Course' ) {
			$quantity_disable = true;
		} elseif ( WooGateway::is_by_courses_via_product() ) {
			$product_has_courses = get_post_meta( $product_id, LP_Woo_Assign_Course_To_Product::$meta_key_lp_woo_courses_assigned, true );
			if ( ! empty( $product_has_courses ) ) {
				$quantity_disable = true;
			}
		}

		if ( $quantity_disable ) {
			$product_quantity = sprintf(
				'<span style="text-align: center; display: block">%s</span>',
				$cart_item['quantity']
			);
		}

		if ( ! is_string( $product_quantity ) ) {
			$product_quantity = '';
		}

		return $product_quantity;
	}

	/**
	 * Update LearnPress order status when WooCommerce updated status
	 * It runs only change status of WC Order type shop_order.
	 *
	 * Logic if you use Woo Subscription:
	 * - First buy will create LP Order mapping.
	 * - If this order is a renewal order, we don't need to create LP Order mapping
	 * - If this order is a resubscribe order, we don't need to create LP Order mapping
	 *  Woo Subscription (child order of this parent) will handle status of LP Order
	 *
	 * @param int $wc_order_id
	 * @param string $old_status
	 * @param string $new_status
	 *
	 * @throws Exception
	 * @uses LPWooSubscription::handle_status_changed
	 *
	 * @since 4.0.2
	 * @version 1.0.1
	 */
	public function lp_order_create_or_update( int $wc_order_id = 0, string $old_status = '', string $new_status = '' ) {
		$wc_order = wc_get_order( $wc_order_id );
		if ( ! $wc_order ) {
			return;
		}

		/**
		 * Case: install Woo Subscription
		 */
		if ( class_exists( 'WC_Subscriptions' ) ) {
			$order_subscriptions     = wcs_get_subscriptions_for_order( $wc_order );
			$is_wc_order_renewal     = $wc_order->get_meta( '_subscription_renewal' );
			$is_wc_order_resubscribe = $wc_order->get_meta( '_subscription_resubscribe' );

			// If this order is a renewal order, we don't need to create LP Order mapping
			if ( ! empty( $is_wc_order_renewal ) ) {
				return;
			}

			// If this order is a resubscribe order, we don't need to create LP Order mapping
			// Woo Subscription (child order of this parent) will handle status of LP Order
			// @uses LPWooSubscription::handle_status_changed
			if ( ! empty( $is_wc_order_resubscribe ) ) {
				return;
			}
		}

		$lp_order_id = $wc_order->get_meta( LPWooOrderHandler::$KEY_META_ID );
		if ( ! empty( $lp_order_id ) ) {
			/**
			 * Check Woo Order is parent Subscription (type shop_order)
			 * No need handle LP Order of shop_order parent here
			 * Because Woo Subscription (child order of this parent) will handle status of LP Order
			 * @uses LPWooSubscription::handle_status_changed
			 */
			if ( ! empty( $order_subscriptions ) ) {
				return;
			}

			$lp_order = learn_press_get_order( $lp_order_id );

			if ( $lp_order ) {
				$lp_order_status_need_update = $new_status;
				if ( 'wc-' === substr( $lp_order_status_need_update, 0, 3 ) ) {
					$lp_order_status_need_update = substr( $lp_order_status_need_update, 3 );
				}

				/**
				 * Status processing of Woo is completed of LP.
				 * https://woocommerce.com/document/managing-orders/order-statuses/
				 * Processing: Orders with this status are paid, but awaiting fulfillment (e.g. shipping products).
				 * All products except those that are both Virtual
				 * and Downloadable will remain in this status unless you manually change them to a different one.
				 * Completed: Payment is successful and the order has been fulfilled.
				 */
				if ( $lp_order_status_need_update === LP_ORDER_PROCESSING ) {
					$lp_order_status_need_update = LP_ORDER_COMPLETED;
				}

				//$lp_order->update_status( $lp_order_status_need_update, false );
				$lp_order->set_status( $lp_order_status_need_update );
				// Update customer info.
				$wc_customer_id = $wc_order->get_customer_id();
				if ( $wc_customer_id ) {
					$lp_order->set_user_id( $wc_customer_id );
				} else {
					$email = $wc_order->get_billing_email();
					$user  = get_user_by( 'email', $email );
					if ( $user ) {
						$lp_order->set_user_id( $user->ID );
					} else {
						$lp_order->set_checkout_email( $email );
						$lp_order->set_user_id( 0 );
					}
				}
				// End update customer info.
				$lp_order->save();
			}
		} else {
			$lp_woo_order = new LPWooOrderHandler( 0, $wc_order_id );
			$lp_woo_order->create_lp_order();
		}
	}

	/**
	 * Update status of Woo order if exists
	 *
	 * @param int $lp_order_id
	 * @param string $old_status
	 * @param string $new_status
	 * Note: not use this function to reverse change status of Woo order, easy conflict logic.
	 */
	public function lp_woo_update_status_order_for_woo( int $lp_order_id, string $old_status, string $new_status ) {
		if ( empty( $lp_order_id ) ) {
			return;
		}

		$woo_order_id = get_post_meta( $lp_order_id, '_woo_order_id', true );

		if ( ! empty( $woo_order_id ) ) {
			$woo_order = wc_get_order( $woo_order_id );

			if ( $woo_order ) {
				$wc_order_status_need_update = 'wc-' . str_replace( 'lp-', '', $new_status );

				$woo_order->update_status( $wc_order_status_need_update );
			}
		}
	}

	/**
	 * Create LP order base on WC order data
	 *
	 * @param WC_Order $wc_order
	 *
	 * @since 4.0.2
	 * @version 1.0.7
	 */
	public function create_lp_order( WC_Order $wc_order ) {
		$wc_order_id  = $wc_order->get_id();
		$lp_woo_order = new LPWooOrderHandler( 0, $wc_order_id );
		$lp_woo_order->create_lp_order();
	}

	/**
	 * Create LP order base on WC order data when wc_order is created from API since WC-7.2
	 * This is similar to existing core hook woocommerce_checkout_update_order_meta.
	 * WC is using a new action:
	 * - To keep the interface focused (only pass $order, not passing request data).
	 * - This also explicitly indicates these orders are from checkout block/StoreAPI.
	 * @see https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/3686
	 *
	 * @param $wc_order $order WC Order object.
	 *
	 * @throws Exception
	 */
	public function create_lp_order_from_api( $wc_order ) {
		if ( ! $wc_order instanceof \WC_Order ) {
			return;
		}

		$wc_order_id  = $wc_order->get_id();
		$lp_woo_order = new LPWooOrderHandler( 0, $wc_order_id );
		$lp_woo_order->create_lp_order();
	}

	/**
	 * For case when payment via stripe of woo - completed order didn't complete LP Order
	 *
	 * @param $order_id
	 *
	 * @throws Exception
	 * @author minhpd
	 * @since 4.0.2
	 * @version 1.0.0
	 */
	public function update_lp_order_status( $order_id ) {
		$wc_order = wc_get_order( $order_id );
		if ( ! $wc_order ) {
			return;
		}
		$status      = $wc_order->get_status();
		$lp_order_id = $wc_order->get_meta( LPWooOrderHandler::$KEY_META_ID );

		if ( ! $lp_order_id ) {
			return;
		}

		$lp_order = learn_press_get_order( $lp_order_id );
		if ( ! $lp_order ) {
			return;
		}

		$lp_order->update_status( $status );
	}

	/**
	 * Handle load assets
	 *
	 * @return void
	 * @version 1.0.1
	 * @since 4.0.2
	 */
	public function load_assets() {
		$min    = '.min';
		$ver    = LP_ADDON_WOO_PAYMENT_VER;
		$is_rtl = is_rtl() ? '-rtl' : '';
		if ( LP_Debug::is_debug() ) {
			$min = '';
			$ver = uniqid();
		}

		wp_register_style(
			'lp-woo-css',
			LP_ADDON_WOO_PAYMENT_URL . "assets/dist/css/lp_woo{$is_rtl}{$min}.css",
			array(),
			$ver
		);
		wp_register_script(
			'lp-woo-payment-js',
			LP_ADDON_WOO_PAYMENT_URL . "assets/dist/js/lp_woo{$min}.js",
			[],
			$ver,
			[
				'strategy' => 'defer',
			]
		);

		$localize_lp_woo = apply_filters(
			'learnpress/localize/lp_woo',
			[
				'url_ajax'                          => admin_url( 'admin-ajax.php' ),
				'woo_enable_signup_and_login_from_checkout' => get_option( 'woocommerce_enable_signup_and_login_from_checkout' ),
				'woocommerce_enable_guest_checkout' => get_option( 'woocommerce_enable_guest_checkout' ),
				'nonce'                             => wp_create_nonce( 'lp-woo-payment-nonce' ),
				'redirect_i18n'                     => __( 'Redirecting...', 'learnpress-woo-payment' ),
				'adding_i18n'                       => __( 'Adding...', 'learnpress-woo-payment' ),
			]
		);
		wp_localize_script( 'lp-woo-payment-js', 'lpWoo', $localize_lp_woo );

		if ( ! WooGateway::is_by_courses_via_product()
			&& (
				LP_PAGE_COURSES === LP_Page_Controller::page_current()
				|| LP_PAGE_PROFILE === LP_Page_Controller::page_current()
			) ) {
			wp_enqueue_style( 'lp-woo-css' );
			wp_enqueue_script( 'lp-woo-payment-js' );
		}
	}

	/**
	 * Hook button purchase course
	 *
	 * @param array $section
	 * @param CourseModel $course
	 * @param UserModel|false $user
	 *
	 * @return array
	 * @throws Exception
	 * @since 4.1.5
	 * @version 1.0.3
	 */
	public function button_add_to_cart( $section, $course, $user ) {
		// Case course is purchased, not auto enroll
		if ( ! $course->is_free() ) {
			$user         = UserModel::find( get_current_user_id(), true );
			$can_purchase = $course->can_purchase( $user );
			if ( is_wp_error( $can_purchase ) ) {
				return $section;
			}
		}

		if ( WooGateway::is_by_courses_via_product() ) { // For theme can easily use
			if ( $course->is_free() &&
				! LP_Woo_Assign_Course_To_Product::check_course_is_assigned_to_product( $course->get_id() ) ) {
				return $section;
			}

			ob_start();
			LP_Woo_Assign_Course_To_Product::instance()->notice_purchase_course_via_product();
			$html_message_buy_via_product = ob_get_clean();

			return [ 'message_buy_via_product' => $html_message_buy_via_product ];
		} else {
			if ( $course->is_free() ) {
				// If course is free, we don't need to show button "add to cart"
				return $section;
			}
		}

		// Check if button default removed will not show button "add to cart"
		if ( empty( $section['btn'] ) ) {
			return $section;
		}

		$item = [
			'id'   => $course->get_id(),
			'type' => LP_COURSE_CPT,
		];

		ob_start();
		do_action( 'learnpress/woo-payment/btn-add-item-to-cart/layout', $item );
		$html_btn_cart = ob_get_clean();

		return [
			'button_add_to_cart' => $html_btn_cart,
			'btn'                => '',
		];
	}

	/**
	 * Add button "add to cart" on list courses
	 *
	 * @param array $section
	 * @param LP_Course $course
	 *
	 * @return array
	 * @since 4.1.3
	 * @version 1.0.0
	 * @deprecated 4.1.5
	 */
	/*public function add_btn_add_to_cart( array $section, LP_Course $course ): array {
		$section_new = [];

		try {
			foreach ( $section as $key => $item ) {
				$section_new[ $key ] = $item;
				if ( 'info' === $key ) {
					ob_start();
					$this->btn_add_to_cart( $course );
					$section_new['btn-add-to-cart'] = [ 'text_html' => ob_get_clean() ];
				}
			}
		} catch ( Throwable $e ) {
			error_log( __METHOD__ . ': ' . $e->getMessage() );
			$section_new = $section;
		}

		return $section_new;
	}*/

	/**
	 * Show Add-to-cart course button if is enabled
	 *
	 * @throws Exception
	 * @version 1.0.3
	 * @since  4.0.2
	 * @deprecated 4.1.5 themes is usings.
	 */
	public function btn_add_to_cart( $course = null ) {
		if ( WooGateway::is_by_courses_via_product() ) { // For theme can easily use
			return;
		}

		$course_id = 0;
		if ( empty( $course ) ) {
			$course_id = get_the_ID();
		} elseif ( $course instanceof LP_Course ) {
			$course_id = $course->get_id();
		}

		$course = CourseModel::find( $course_id, true );
		if ( ! $course ) {
			return;
		}

		if ( $course->is_free() ) {
			if ( LP_PAGE_SINGLE_COURSE !== LP_Page_Controller::page_current() ) {
				do_action( 'learnpress/woo-payment/course-free/btn_add_to_cart_before', $course ); // For the Eduma theme add button "Read more"
			}

			return;
		}

		$user         = UserModel::find( get_current_user_id(), true );
		$can_purchase = $course->can_purchase( $user );
		if ( ! $can_purchase || is_wp_error( $can_purchase ) ) {
			return;
		}

		wp_enqueue_style( 'lp-woo-css' );
		wp_enqueue_script( 'lp-woo-payment-js' );

		$item = [
			'id'   => $course_id,
			'type' => LP_COURSE_CPT,
		];

		do_action( 'learnpress/woo-payment/btn-add-item-to-cart/layout', $item );
	}

	/**
	 * Return true if a course is already added into WooCommerce cart
	 *
	 * @param int $course_id
	 *
	 * @return bool
	 * @version 1.0.3
	 * @since 4.0.2
	 *
	 * @author tungnx
	 */
	public function is_added_in_cart( int $course_id ): bool {
		global $wpdb;

		try {
			// Don't use WC_Cart on here, make error null something
			// $cart       = new WC_Cart();
			// $key_cart_item = $cart->generate_cart_id( $course_id );
			$wc_session    = new WC_Session_Handler();
			$session       = $wc_session->get_session_cookie();
			$key_cart_item = md5( implode( '_', array( $course_id ) ) );

			if ( empty( $session ) ) {
				return false;
			}

			$cookie_hash = $session[0] ?? '';

			if ( empty( $cookie_hash ) ) {
				return false;
			}

			$query = $wpdb->prepare(
				"
				SELECT session_value FROM {$wpdb->prefix}woocommerce_sessions
				WHERE session_key = %s
				AND session_value LIKE %s
				",
				$cookie_hash,
				'%' . $key_cart_item . '%'
			);

			$result = $wpdb->get_var( $query );

			if ( empty( $result ) ) {
				return false;
			}

			$result_arr  = maybe_unserialize( $result );
			$result_cart = maybe_unserialize( $result_arr['cart'] ?? '' );

			if ( array_key_exists( $key_cart_item, $result_cart ) ) {
				return true;
			} else {
				return false;
			}
		} catch ( Throwable $e ) {
			return false;
		}
	}

	/**
	 * Add Woocommerce Order on tab profile
	 */
	public function profile_tabs_woo_order( $tabs ) {
		$tabs['lp_orders_woocommerce'] = array(
			'title'    => esc_html__( 'Order Woocommerce', 'learnpress-woo-payment' ),
			'slug'     => 'orders_woocommerce',
			'callback' => array( $this, 'profile_tabs_woo_order_content' ),
			'priority' => 25,
			'icon'     => '<i class="fas fa-shopping-cart" aria-hidden="true"></i>',
		);

		return $tabs;
	}

	/**
	 * Content of profile order woocommerce page.
	 */
	public function profile_tabs_woo_order_content() {
		global $wp;
		$url   = home_url( $wp->request );
		$parts = explode( '/', $url );
		$parts = array_filter( $parts );

		$total_records  = wc_get_customer_order_count( get_current_user_id() );
		$posts_per_page = get_option( 'posts_per_page' );
		$total_pages    = ceil( $total_records / $posts_per_page );

		$paged = end( $parts ) === 'orders_woocommerce' ? 1 : end( $parts );
		$from  = ( $paged - 1 ) * $posts_per_page + 1;
		$to    = $from + $posts_per_page - 1;
		$to    = min( $to, $total_records );
		if ( $total_records < 1 ) {
			$from = 0;
		}

		$offset = array( $from, $to );
		/*
		$customer_orders = get_posts(
			array(
				'meta_key'       => '_customer_user',
				'meta_value'     => get_current_user_id(),
				'post_type'      => wc_get_order_types( 'view-orders' ),
				'posts_per_page' => $posts_per_page,
				'paged'          => $paged,
				'post_status'    => array_keys( wc_get_order_statuses() ),
			)
		);*/
		$customer_orders = wc_get_orders(
			array(
				'customer' => get_current_user_id(),
				'limit'    => $posts_per_page,
				'page'     => $paged,
				'type'     => wc_get_order_types( 'view-orders' ),
			)
		);

		$format_text        = __( 'Displaying {{from}} to {{to}} of {{total}} {{item_name}}.', 'learnpress-woo-payment' );
		$output_format_text = str_replace(
			array( '{{from}}', '{{to}}', '{{total}}', '{{item_name}}' ),
			array(
				$offset[0],
				$offset[1],
				$total_records,
				'items',
			),
			$format_text
		);

		LP_Addon_Woo_Payment_Preload::$addon->get_template( 'wc-order-profile', compact( 'customer_orders', 'format_text', 'output_format_text', 'total_pages', 'paged' ) );
	}

	/**
	 * Show related LP Order on WC Order
	 */
	public function wc_order_notice() {
		global $post, $pagenow;
		if ( $pagenow !== 'post.php' || empty( $post ) ) {
			return;
		}

		$post_type = get_post_type( $post->ID );

		if ( 'shop_order' === $post_type ) {
			$wc_order    = wc_get_order( $post->ID );
			$lp_order_id = $wc_order->get_meta( LPWooOrderHandler::$KEY_META_ID );
			if ( ! $lp_order_id ) {
				return;
			}

			$lp_order = learn_press_get_order( $lp_order_id );
			?>
			<div class="notice notice-warning woo-payment-order-notice">
				<p>
					<?php
					echo sprintf(
						'%s %s',
						__( 'This order is related to LearnPress order', 'learnpress-woo-payment' ),
						$lp_order ? '<a href="' . get_edit_post_link( $lp_order_id ) . '">#' . $lp_order_id . '</a>' : '#' . $lp_order_id
					);
					?>
				</p>
			</div>
			<?php
		} elseif ( LP_ORDER_CPT === $post_type ) {
			$wc_order_id = get_post_meta( $post->ID, '_woo_order_id', true );
			if ( ! $wc_order_id ) {
				return;
			}

			$wc_order = wc_get_order( $wc_order_id );
			?>
			<div class="notice notice-warning woo-payment-order-notice">
				<p>
					<?php
					echo sprintf(
						'%s %s<br>%s',
						__( 'This order is related to Woocommerce order', 'learnpress-woo-payment' ),
						$wc_order ? '<a href="' . get_edit_post_link( $wc_order_id ) . '">#' . $wc_order_id . '</a>' : '#' . $wc_order_id,
						__( 'If you want to change status of this Order, you must go to Woocommerce Order related and change', 'learnpress-woo-payment' )
					);
					?>
				</p>
			</div>
			<?php
		}
	}

	/**
	 * Add item line meta-data contains our course_id from product_id in cart.
	 * Since WC 3.x order item line product_id always is 0 if it is not a REAL product.
	 * Need to track course_id for creating LP order in WC hook after this action.
	 *
	 * @param WC_Order_Item_LP_Course $item
	 * @param $cart_item_key
	 * @param $values
	 * @param $order
	 */
	public function order_item_line( $item, $cart_item_key, $values, $order ) {
		if ( LP_COURSE_CPT === get_post_type( $values['product_id'] ) ) {
			$item->add_meta_data( '_course_id', $values['product_id'], true );
		}
	}

	/**
	 * Add course id to order item when admin create wc order and add WC_Product_LP_Course items
	 *
	 * @param WC_Order_Item_Product $item
	 * @param integer $item_id order item id
	 * @param WC_Order $order wc order
	 * @param WC_Product_LP_Course $product
	 *
	 * @return WC_Order_Item_Product
	 */
	public function add_manually_course_to_wc_order( $item, $item_id, $order, $product ) {
		if ( $product->get_type() === LP_COURSE_CPT ) {
			$item->add_meta_data( '_course_id', $product->get_id(), true );
			$item->save();
		}

		return $item;
	}

	/**
	 * Get classname WC_Order_Item_LP_Course
	 *
	 * @throws Exception
	 */
	public function get_classname_lp_wc_order( $classname, $item_type, $id ) {
		if ( in_array( $item_type, array( 'line_item', 'product' ) ) ) {
			$course_id = wc_get_order_item_meta( $id, '_course_id' );
			if ( $course_id && LP_COURSE_CPT === get_post_type( $course_id ) ) {
				$classname = 'WC_Order_Item_LP_Course';
			}
		}

		return $classname;
	}

	/**
	 * Set payment method title for LP Order
	 *
	 * @param $title
	 * @param $lp_order
	 *
	 * @return mixed|string
	 * @version 1.0.2
	 * @since 4.0.2
	 */
	public function lp_woo_payment_method_title( $title, $lp_order ) {
		$woo_order_id = get_post_meta( $lp_order->get_id(), '_woo_order_id', true );

		if ( ! empty( $woo_order_id ) ) {
			$wc_order             = wc_get_order( $woo_order_id );
			$payment_method_title = '';
			if ( $wc_order ) {
				$payment_method_title = $wc_order->get_payment_method_title();
				$link_woo_order       = $wc_order->get_edit_order_url();
				$title                = sprintf( '<a href="%s">Woo #%d: %s</a>', $link_woo_order, $woo_order_id, $payment_method_title );
			} else {
				$title = sprintf( 'Woo #%d: %s %s', $woo_order_id, $payment_method_title, __( 'Deleted', 'learnpress-woo-payment' ) );
			}
		}

		return $title;
	}

	/**
	 * For on WC Coupon data
	 *
	 * @param $products
	 *
	 * @return mixed
	 */
	public function wc_json_search_found_products_and_courses( $products ) {
		global $wpdb;
		$term = wc_clean( empty( $term ) ? stripslashes( $_GET['term'] ) : $term );
		$sql  = $wpdb->prepare(
			"
			SELECT ID, post_title FROM {$wpdb->posts}
			WHERE post_title LIKE %s
			AND post_type = 'lp_course'
			AND post_status = 'publish'",
			'%' . $wpdb->esc_like( $term ) . '%'
		);

		$rows = $wpdb->get_results( $sql );

		foreach ( $rows as $row ) {
			$products[ $row->ID ] = $row->post_title . ' (' . __( 'Course', 'learnpress-woo-payment' ) . ' #' . $row->ID . ')';
		}

		return $products;
	}

	/**
	 * [update_woocommerce_order_by_recovered_key update woocommerce order's customer_id when user enter LP Order recover key]
	 *
	 * @param $lp_order_id [lp order id]
	 * @param $user_id [lp order user]
	 *
	 * @since 2.1.0
	 */
	public function update_woocommerce_order_by_recovered_key( $lp_order_id, $user_id ) {
		$woo_order_id = get_post_meta( $lp_order_id, '_woo_order_id', true );
		try {
			if ( ! empty( $woo_order_id ) ) {
				$wc_order = wc_get_order( $woo_order_id );
				if ( $wc_order ) {
					$wc_order->set_customer_id( $user_id );
					$wc_order->save();
				}
			}
		} catch ( Throwable $e ) {
			error_log( __METHOD__ . ': ' . $e->getMessage() );
		}
	}

	/**
	 * Set total for item of LP Order buy via Woo
	 *
	 * @param $item_total
	 * @param LP_Course $course LP course
	 * @param array $item LP Order Item
	 * @param LP_Order $lp_order
	 *
	 * @return mixed
	 * @since 4.1.3
	 */
	public function set_item_total_course( $item_total, $course, $item, $lp_order ) {
		if ( $lp_order->get_created_via() == 'woocommerce' && $item['item_type'] == LP_COURSE_CPT ) {
			$item_total = $item['total'];
		}

		return $item_total;
	}

	/**
	 * Check course is out stock in product
	 *
	 * @param $checkout
	 *
	 * @since 4.1.5
	 */
	public function check_product_in_checkout( $checkout ) {
		$cart = WC()->cart->get_cart();
		if ( empty( $cart ) || count( $cart ) < 1 ) {
			return;
		}
		wp_enqueue_style( 'lp-woo-css' );
		wp_enqueue_script( 'lp-woo-payment-js' );

		foreach ( $cart as $cart_item ) {
			$product_id  = $cart_item['product_id'];
			$data_course = $this->list_course_out_stock_product( $product_id );

			// Remove product has out of stock in cart
			if ( $data_course['all_course_is_out_stock'] ) {
				if ( WooGateway::is_by_courses_via_product() ) {
					LP_Woo_Assign_Course_To_Product::remove_product_cart_by_id( $product_id );
				}
			}
		}
	}

	/**
	 * Message course is out stock
	 *
	 * @since 4.1.5
	 */
	public function message_course_out_stock() {
		global $product;
		$product_id              = $product->get_id();
		$data_course             = $this->list_course_out_stock_product( $product_id );
		$all_course_is_out_stock = $data_course['all_course_is_out_stock'] ?? false;
		if ( $all_course_is_out_stock ) {
			remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_add_to_cart', 30 );
			//remove_action( 'woocommerce_product_add_to_cart', 'woocommerce_template_loop_add_to_cart', 10 );

			Template::print_message( __( 'Courses are full of students', 'learnpress-woo-payment' ), 'warning' );
		}
	}

	/**
	 *  List course out stock in checkout
	 *
	 * @since 4.1.5
	 */
	public function list_course_checkout() {
		if ( ! WooGateway::is_by_courses_via_product() ) {
			return;
		}

		$cart = WC()->cart->get_cart();
		if ( empty( $cart ) || count( $cart ) < 1 ) {
			return;
		}

		wp_enqueue_style( 'lp-woo-css' );
		wp_enqueue_script( 'lp-woo-payment-js' );
		$user = UserModel::find( get_current_user_id(), true );

		foreach ( $cart as $cart_item ) {
			$product_id               = $cart_item['product_id'];
			$data                     = LP_Woo_Assign_Course_To_Product::get_list_courses_assign_to_product( $product_id, $user );
			$courses_assign_product   = $data['courses_assign_product'];
			$all_courses_out_of_stock = $data['all_courses_out_of_stock'];
			$product                  = $cart_item['data'];
			if ( empty( $courses_assign_product ) ) {
				continue;
			}

			LP_Addon_Woo_Payment_Preload::$addon->get_template(
				'list-courses-assign-product-checkout',
				compact( 'product', 'courses_assign_product', 'all_courses_out_of_stock' )
			);
			//LP_Addon_Woo_Payment_Preload::$addon->get_template( 'wc-list-course-checkout', compact( 'is_out_stock', 'product_title', 'all_course_is_out_stock' ) );
		}
	}

	/**
	 *  List course out stock in product
	 *
	 * @param int $product_id
	 *
	 * @return array
	 * @since 4.1.5
	 */
	public function list_course_out_stock_product( $product_id ): array {
		$output = [
			'course_ids'              => array(),
			'all_course_is_out_stock' => false,
		];

		if ( empty( $product_id ) ) {
			return $output;
		}

		$course_ids              = get_post_meta( $product_id, '_lp_woo_courses_assigned', true );
		$is_out_stock            = array();
		$all_course_is_out_stock = false;

		if ( empty( $course_ids ) || count( $course_ids ) < 1 ) {
			return $output;
		}

		foreach ( $course_ids as $course_id ) {
			$course_id = absint( $course_id );
			$course    = CourseModel::find( $course_id, true );
			if ( ! empty( $course ) && ! $course->is_in_stock() && ! $course->has_no_enroll_requirement() ) {
				$is_out_stock[] = $course_id;
			}
		}

		if ( count( $is_out_stock ) === count( $course_ids ) ) {
			$all_course_is_out_stock = true;
		}

		$output['course_ids']              = $is_out_stock;
		$output['all_course_is_out_stock'] = $all_course_is_out_stock;

		return $output;
	}

	/**
	 * Get items out stock in order
	 *
	 * @param int $order_id
	 *
	 * @return array
	 * @since 4.1.5
	 * @author  VuxMinhThanh
	 */
	public function get_items_out_stock( int $order_id ): array {
		$course_ids_out_of_stock = [];
		if ( empty( $order_id ) ) {
			return $course_ids_out_of_stock;
		}

		$key                         = '_lp_course_out_stock';
		$course_ids_out_of_stock_str = get_post_meta( $order_id, $key, true );
		if ( ! empty( $course_ids_out_of_stock_str ) ) {
			$course_ids_out_of_stock = explode( ',', $course_ids_out_of_stock_str );
		}

		return $course_ids_out_of_stock;
	}

	/**
	 * Display label course full students.
	 *
	 * @param $name
	 * @param $item
	 *
	 * @return mixed|string
	 */
	public function name_course_order( $name, $item, $order ) {
		$course_id         = $item['course_id'];
		$courses_out_stock = $this->get_items_out_stock( $order->get_id() );
		if ( empty( $courses_out_stock ) ) {
			return $name;
		}

		if ( in_array( $course_id, $courses_out_stock ) ) {
			$name = sprintf( '%s - %s', $name, esc_html__( 'The course is full of students.', 'learnpress' ) );
		}

		return $name;
	}
}

