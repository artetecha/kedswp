<?php

namespace LearnPress\WooPayment\Upsell;

use WC_Order_Item_Product;

defined( 'ABSPATH' ) || exit();

if ( ! class_exists( 'WC_Order_Item_Product' ) ) {
	return;
}

/**
 * @class WC_Order_Item_LP_Package
 */
class WC_Order_Item_LP_Package extends WC_Order_Item_Product {
	/**
	 * @throws \WC_Data_Exception
	 * @throws \Exception
	 */
	public function set_product_id( $value ) {
		$lp_package_id = wc_get_order_item_meta( $this->get_id(), '_lp_package_id' );
		if ( LP_PACKAGE_CPT == get_post_type( absint( $lp_package_id ) ) ) {
			$value = $lp_package_id;
		}

		$this->set_prop( 'product_id', absint( $value ) );
	}
}
