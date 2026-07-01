<?php
use LearnPress\Upsell\Coupon\Core_Functions;
use LearnPress\Upsell\Coupon\Coupon;

add_action(
	'learn-press/review-order/after-cart-contents',
	function () {
		if ( ! Core_Functions::instance()->is_coupons_enabled() ) {
			return;
		}
		?>
		<tr>
			<?php
			if ( Core_Functions::instance()->is_show_list_coupon_checkout() ) {
				?>
				<td colspan="3"><?php do_action( 'lp/upsell/layout/list-coupon' ); ?></td>
				<?php
			}
			?>
		</tr>
		<tr class="lp-coupon">
			<th class="lp-coupon__title"><?php esc_html_e( 'Coupon:', 'learnpress-upsell' ); ?></th>
			<td colspan="2">
				<div class="lp-coupon__wrapper">
					<input type="text" name="coupon_code" class="input-text" id="lp_coupon_code" value=""
						   placeholder="<?php esc_attr_e( 'Coupon code', 'learnpress-upsell' ); ?>"/>
					<button class="lp-button lp-coupon-apply" type="button"
							value="<?php esc_attr_e( 'Apply', 'learnpress-upsell' ); ?>">
						<?php esc_html_e( 'Apply', 'learnpress-upsell' ); ?>
					</button>
				</div>
			</td>
		</tr>
		<?php
	},
	10
);

add_action( 'learn-press/review-order/before-order-total', function() {
	if ( ! Core_Functions::instance()->is_coupons_enabled() ) {
		return;
	}

	$cart       = LP()->cart;
	$cart_items = $cart ? $cart->get_items() : array();

	foreach ( $cart_items as $cart_item_key => $cart_item ) {
		$coupon_codes = ! empty( $cart_item['applied_coupons'] ) ? $cart_item['applied_coupons'] : array();
		if ( empty( $coupon_codes ) ) {
			continue;
		}

		foreach ( $coupon_codes as $coupon_code ) {
			if ( empty( $cart_item['discount_amount'][ $coupon_code ] ) ) {
				continue;
			}

			$price_discount = learn_press_format_price( $cart_item['discount_amount'][ $coupon_code ] );
			LP_Addon_Upsell_Preload::$addon->get_template( 'coupon/info_coupon_checkout.php', compact( 'coupon_code', 'price_discount' ) );
		}
	}
}, 10 );

add_filter( 'lp/cart/calculate_total', function( $data ) {
	$cart = LP()->cart;

	if ( ! Core_Functions::instance()->is_coupons_enabled() ) {
		return $data;
	}

	$cart_items = $cart ? $cart->get_items() : array();

	foreach ( $cart_items as $cart_item_key => $cart_item ) {
		$coupon_codes = ! empty( $cart_item['applied_coupons'] ) ? $cart_item['applied_coupons'] : array();
		if ( empty( $coupon_codes ) ) {
			continue;
		}
		foreach ( $coupon_codes as $coupon_code ) {
			$coupon = new \LearnPress\Upsell\Coupon\Coupon( $coupon_code );
			if ( ! Core_Functions::instance()->validate_coupon_exists( $coupon ) ) {
				continue;
			}
			if ( ! Core_Functions::instance()->validate_coupon_usage_limit( $coupon ) ) {
				continue;
			}
			$discount_amount = Core_Functions::instance()->calculate_discount_amount( $coupon, $data->subtotal );

			// if ( $data->total <= 0 ) {
				// 	break;
			// }

			$cart_items[ $cart_item_key ]['discount_amount'][ $coupon_code ] = $discount_amount;

			$data->total -= $discount_amount;
			$cart->total -= $discount_amount;

			if ( $data->total <= 0 ) {
				$data->total = 0;
				$cart->total = 0;
			}

			$cart->update_session( $cart_items );
		}
	}

	return $data;
}, 100 );


/**
 * Check customer coupon.
 *
 * @throws Exception
 */
function lp_upsell_check_coupon_apply() {
	if ( ! Core_Functions::instance()->is_coupons_enabled() ) {
		return;
	}

	$applied_coupons = Core_Functions::instance()->get_applied_coupons();
	if ( empty( $applied_coupons ) ) {
		return;
	}

	foreach ( $applied_coupons as $coupon_code ) {
		$coupon = new Coupon( $coupon_code );

		$current_user = wp_get_current_user();
		$guest_email  = ! empty( $_POST['guest_email'] ) ? sanitize_email( $_POST['guest_email'] ) : '';

		$check_emails = array_unique(
			array_map(
				'strtolower',
				array_map(
					'sanitize_email',
					array(
						$guest_email,
						$current_user->user_email,
					)
				)
			)
		);

		// Limit to defined email addresses.
		$restrictions = $coupon->get_email_restrictions();

		if ( is_array( $restrictions ) && 0 < count( $restrictions ) && ! Core_Functions::instance()->is_coupon_emails_allowed( $check_emails, $restrictions ) ) {
			throw new Exception(
				sprintf( __( 'Coupon "%s" is not valid for the email address entered!', 'learnpress-upsell' ), $coupon_code )
			);
		}

		$coupon_usage_limit = $coupon->get_usage_limit_per_user();

		if ( 0 < $coupon_usage_limit ) {
			// Guest checkout.
			if ( 0 === get_current_user_id() ) {
				$guest_email = strtolower( sanitize_email( $guest_email ) );

				$usage_limit_by_email = Core_Functions::instance()->get_usage_by_email( $coupon, $guest_email );

				$user = get_user_by( 'email', $guest_email );

				$usage_limit_by_user_id = $user ? Core_Functions::instance()->get_usage_by_user_id( $coupon, $user->ID ) : 0;

				if ( $usage_limit_by_email + $usage_limit_by_user_id >= $coupon_usage_limit ) {
					throw new Exception(
						sprintf( __( 'Coupon "%s" usage limit has been reached.', 'learnpress-upsell' ), $coupon_code )
					);
				}
			} else {
				$usage_limit_by_user_id = Core_Functions::instance()->get_usage_by_user_id( $coupon, get_current_user_id() );

				// Get user email.
				$user = get_user_by( 'id', get_current_user_id() );

				$usage_limit_by_email = $user ? Core_Functions::instance()->get_usage_by_email( $coupon, $user->user_email ) : 0;

				if ( $usage_limit_by_email + $usage_limit_by_user_id >= $coupon_usage_limit ) {
					throw new Exception(
						sprintf( __( 'Coupon "%s" usage limit has been reached.', 'learnpress-upsell' ), $coupon_code )
					);
				}
			}
		}
	}
}
add_action( 'learn-press/validate-checkout-fields', 'lp_upsell_check_coupon_apply', 20 );

add_action( 'learn-press/added-order-item-data', function( $item_id = 0, $item = array(), $order_id = 0 ) {
	$order = learn_press_get_order( $order_id );

	if ( ! $order ) {
		return;
	}

	if ( ! empty( $item['applied_coupons'] ) ) {
		update_post_meta( $order_id, 'applied_coupons', $item['applied_coupons'] );
	}
	if ( ! empty( $item['discount_amount'] ) ) {
		$total_discount = array_sum( $item['discount_amount'] );
		update_post_meta( $order_id, 'discount_amount', $total_discount );
	}
}, 10, 3 );

add_action( 'learn-press/order/status-changed', function( $order_id, $old_status, $new_status ) {
	$order = learn_press_get_order( $order_id );

	if ( ! $order ) {
		return;
	}

	if ( $order->has_status( 'cancelled' ) ) {
		$action = 'reduce';
	} else {
		$action = 'increase';
	}

	$applied_coupons = get_post_meta( $order_id, 'applied_coupons', true );

	if ( empty( $applied_coupons ) ) {
		return;
	}

	foreach ( $applied_coupons as $coupon_code ) {
		if ( empty( $coupon_code ) ) {
			continue;
		}

		$coupon = new \LearnPress\Upsell\Coupon\Coupon( $coupon_code );
		$used_by = $order->get_user_id();

		if ( empty( $used_by ) ) {
			$used_by = $order->get_checkout_email();
		}

		switch ( $action ) {
			case 'reduce':
				Core_Functions::instance()->decrement_usage_count( $coupon, $used_by );
				break;
			case 'increase':
				Core_Functions::instance()->increase_usage_count( $coupon, $used_by );
				break;
		}
	}
}, 10, 3 );

add_action( 'learn-press/admin/order/detail/before-total', function( $order ) {
	$applied_coupons = get_post_meta( $order->get_id(), 'applied_coupons', true );

	if ( empty( $applied_coupons ) ) {
		return;
	}

	$discount_amount = get_post_meta( $order->get_id(), 'discount_amount', true );

	if ( empty( $discount_amount ) ) {
		return;
	}

	$discount_amount = learn_press_format_price( $discount_amount );

	?>
	<tr class="row-total">
		<td colspan="2" style="text-align:left;">
			<?php esc_html_e( 'Coupon(s):', 'learnpress-upsell' ); ?>
			<?php echo implode( ',', $applied_coupons ); ?>
		</td>
		<td class="align-right">
			<?php esc_html_e( 'Discount:', 'learnpress-upsell' ); ?>
		</td>
		<td class="align-right"><?php echo $discount_amount; ?></td>
	</tr>
	<?php
} );

add_action( 'learn-press/order/items-table-foot', function( $order ) {
	$applied_coupons = get_post_meta( $order->get_id(), 'applied_coupons', true );

	if ( empty( $applied_coupons ) ) {
		return;
	}

	$discount_amount = get_post_meta( $order->get_id(), 'discount_amount', true );

	if ( empty( $discount_amount ) ) {
		return;
	}

	$discount_amount = learn_press_format_price( $discount_amount );

	?>
	<tr class="lp-order-coupons">
		<td>
			<?php esc_html_e( 'Coupon(s):', 'learnpress-upsell' ); ?>
			<?php echo implode( ',', $applied_coupons ); ?>
		</td>
		<td class="align-right"><?php echo $discount_amount; ?></td>
	</tr>
	<?php
} );
