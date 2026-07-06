<?php
defined( 'ABSPATH' ) || exit();

/**
 * Class LP_Certificate_WC
 */
class LP_Certificate_WC {
	protected static $_instance;

	public static function getInstance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	protected function __construct() {
		// check plugin LP - Woo installed
		include_once ABSPATH . 'wp-admin/includes/plugin.php';

		if ( is_plugin_active( 'learnpress-woo-payment/learnpress-woo-payment.php' )
		&& is_plugin_active( 'woocommerce/woocommerce.php' ) ) {

			if ( LearnPress::instance()->settings()->get( 'woo-payment.enable' ) ) {
				// add_filter( 'learn-press/woo-cert-product-price', array( $this, 'lp_cert_set_price_woo' ), 11, 2 );
				add_action( 'woocommerce_checkout_update_order_meta', array( $this, 'update_certificate_info_to_order_meta' ), 10, 2 );
				add_action( 'learn-press/added-order-item-data', array( $this, 'lp_cert_add_order_meta' ), 10, 3 );
				add_action( 'woocommerce_cart_item_name', array( $this, 'lp_certificate_title_cart_woo' ), 10, 2 );
				add_action( 'woocommerce_cart_item_thumbnail', array( $this, 'lp_certificate_image_cart_woo' ), 10, 2 );

				/*** Apply for add cert_id to cart */
				add_filter( 'woocommerce_product_class', array( $this, 'product_class' ), 10, 4 );
				// add_filter( 'woocommerce_get_product_from_item', array( $this, 'learnpress_woo_payment_woocommerce_get_product_from_item_callback' ), 10, 3 );
				/*** End apply for add cert_id to cart */

				// add item_type certificate when create lp_order via woocommerce;
				add_action( 'learnpress/wc-order/subtotal/item_type_lp_cert', array( $this, 'lp_cert_sub_total_item_order_via_woo' ), 10, 2 );

				// minhpd edit: 17-1-2022
				add_filter( 'woocommerce_get_order_item_classname', array( $this, 'get_classname_lp_cert_wc_order' ), 10, 3 );
				add_action( 'woocommerce_checkout_create_order_line_item', array( $this, 'lp_cert_order_item_line' ), 10, 4 );

				// custom link + title cert in page tks woocommerce
				add_filter( 'woocommerce_order_item_name', array( $this, 'lp_cert_order_item_name' ), 10, 2 );
			}
		}
	}

	/**
	 * custom title cert in page tks woocommerce
	 *
	 * @param $link
	 * @param $item
	 */
	public function lp_cert_order_item_name( $link, $item ) {

		if ( $item ) {
			$data_item = $item->get_data();
			if ( LP_ADDON_CERTIFICATES_CERT_CPT === get_post_type( $data_item['product_id'] ) ) {
				$cert_link  = get_the_permalink( $data_item['product_id'] );
				$cert_title = get_the_title( $data_item['product_id'] );

				$course_id    = wc_get_order_item_meta( $data_item['id'], '_lp_course_id_of_cert' );
				$course_link  = get_the_permalink( $course_id );
				$course_title = get_the_title( $course_id );

				$title = sprintf( '%s %s - %s %s', __( 'Certificate:', 'learnpress-certificates' ), $cert_title, __( 'Course:', 'learnpress-certificates' ), $course_title );
				$link  = '<a href="' . $course_link . '">' . $title . '</a>';
			}
		}

		return $link;
	}

	/**
	 * Add item line meta data contains our course_id from product_id in cart.
	 * Since WC 3.x order item line product_id always is 0 if it is not a REAL product.
	 * Need to track course_id for creating LP order in WC hook after this action.
	 *
	 * @param $item
	 * @param $cart_item_key
	 * @param $values
	 * @param $order
	 */
	public function lp_cert_order_item_line( $item, $cart_item_key, $values, $order ) {
		if ( LP_ADDON_CERTIFICATES_CERT_CPT === get_post_type( $values['product_id'] ) ) {
			$wc_cart = WC()->cart->get_cart();
			if ( array_key_exists( $cart_item_key, $wc_cart ) ) {
				$cart_item = $wc_cart[ $cart_item_key ];
				if ( $cart_item['product_id'] && LP_ADDON_CERTIFICATES_CERT_CPT == get_post_type( $cart_item['product_id'] ) ) {
					$item->add_meta_data( '_lp_cert_id', $values['product_id'], true );
					$item->add_meta_data( '_lp_course_id_of_cert', $values['course_id'], true );
				}
			}
		}
	}

	/**
	 * Get classname WC_Order_Item_LP_Cert
	 *
	 * @throws Exception
	 */
	public function get_classname_lp_cert_wc_order( $classname, $item_type, $id ) {

		if ( in_array( $item_type, array( 'line_item', 'product' ) ) ) {
			$cert_id = wc_get_order_item_meta( $id, '_lp_cert_id' );
			if ( $cert_id && LP_ADDON_CERTIFICATES_CERT_CPT == get_post_type( $cert_id ) ) {
				$classname = 'WC_Order_Item_LP_Cert';
			}
		}

		return $classname;
	}

	/**
	 * @param array $lp_order_items
	 * @param int   $course_id
	 * add item_type certificate when create lp_order via woocommerce;
	 */
	public function lp_cert_sub_total_item_order_via_woo( $order_subtotal, $item ) {

		if ( $item['product_id'] && LP_ADDON_CERTIFICATES_CERT_CPT == get_post_type( $item['product_id'] ) ) {

			$cert_id        = $item['product_id'];
			$price_cert     = get_post_meta( $cert_id, '_lp_certificate_price', true );
			$order_subtotal = $price_cert * absint( $item['quantity'] );
		}

		return $order_subtotal;
	}


	// public function lp_cert_set_price_woo( $price, $course ) {
	// $wc_cart = WC()->cart;

	// if ( empty( $wc_cart ) ) {
	// return $price;
	// }

	// $cart_arr = $wc_cart->get_cart();

	// foreach ( $cart_arr as $key => $cart_item ) {
	// if ( $cart_item['product_id'] && LP_ADDON_CERTIFICATES_CERT_CPT == get_post_type( $cart_item['product_id'] ) ) {
	// $price = get_post_meta( $cart_item['product_id'], '_lp_certificate_price', true );
	// }
	// }

	// return $price;
	// }

	/**
	 * @param $order_id
	 * @param $data
	 */
	public function update_certificate_info_to_order_meta( $order_id, $data ) {
		$wc_cart = WC()->cart->get_cart();
		$order   = new WC_Order( $order_id );
		$items   = $order->get_items();

		try {
			foreach ( $items as $item ) {
				$product       = $item->get_data();
				$product_id    = $product['product_id'];
				$cart_item_key = WC()->cart->generate_cart_id( $product_id );

				if ( array_key_exists( $cart_item_key, $wc_cart ) ) {
					$cart_item = $wc_cart[ $cart_item_key ];
					if ( $cart_item['product_id'] && LP_ADDON_CERTIFICATES_CERT_CPT == get_post_type( $cart_item['product_id'] ) ) {
						wc_add_order_item_meta( $item->get_id(), '_lp_cert_id', $cart_item['product_id'] );
					}
				}
			}
		} catch ( Exception $e ) {

		}
	}

	/**
	 * lp_cert_add_order_meta
	 */
	public function lp_cert_add_order_meta( $lp_order_item_id = 0, $lp_order_item = array(), $lp_order_id = 0 ) {
		$woo_order_id = get_post_meta( $lp_order_id, '_woo_order_id', true );
		if ( ! empty( $woo_order_id ) ) {
			$wc_oder = wc_get_order( $woo_order_id );
			$items   = $wc_oder->get_items();
			foreach ( $items as $item ) {
				$product_id = $item->get_product_id();
				if ( $product_id === $lp_order_item['item_id'] && LP_ADDON_CERTIFICATES_CERT_CPT === get_post_type( $product_id ) ) {
					$course_id = wc_get_order_item_meta( $item->get_id(), '_lp_course_id_of_cert' );
					$cert_id   = wc_get_order_item_meta( $item->get_id(), '_lp_cert_id' );
					learn_press_add_order_item_meta( $lp_order_item_id, '_lp_cert_id', $cert_id );
					learn_press_add_order_item_meta( $lp_order_item_id, '_lp_course_id_of_cert', $course_id );
				}
			}
		}
	}

	/**
	 * Product class by Certificate
	 */
	public function product_class( $classname, $product_type, $post_type, $product_id ) {
		if ( 'lp_cert' == get_post_type( $product_id ) ) {
			$classname = 'WC_Product_LP_Certificate';
		}

		return $classname;
	}

	/**
	 * update title certificate in page cart.
	 */
	public function lp_certificate_title_cart_woo( $product_link, $cart_item ) {
		if ( $cart_item['product_id'] && LP_ADDON_CERTIFICATES_CERT_CPT == get_post_type( $cart_item['product_id'] ) && $cart_item['course_id'] ) {
			$cert_title   = get_the_title( $cart_item['product_id'] );
			$course_title = get_the_title( $cart_item['course_id'] );

			$product_title = sprintf( '%s: %s - %s', __( 'Certificate', 'learnpress-certificates' ), $cert_title, $course_title );
			$product_link  = apply_filters( 'learn-press/lp-cert-woo-link-product', '<a href="' . get_permalink( $cart_item['course_id'] ) . '">' . $product_title . '</a>', $cart_item );
		}

		return $product_link;
	}

	/**
	 * update image certificate in page cart.
	 */
	public function lp_certificate_image_cart_woo( $image, $cart_item ) {
		if ( $cart_item['product_id'] && LP_ADDON_CERTIFICATES_CERT_CPT == get_post_type( $cart_item['product_id'] ) ) {
			$cert_bg_img = LP_Addon_Certificates::get_link_cert_bg_by_course( $cart_item['product_id'] );
			if ( ! empty( $cert_bg_img ) ) {
				$image = '<img src="' . $cert_bg_img . '" width="300" height="300" />';
			} else {
				$image = wc_placeholder_img();
			}
		}

		return $image;
	}
}

LP_Certificate_WC::getInstance();
