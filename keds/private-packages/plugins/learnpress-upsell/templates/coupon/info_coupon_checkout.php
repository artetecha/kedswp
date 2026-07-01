<?php
/**
 * Template display info coupon applied on the checkout page.
 *
 * @since 4.0.2
 * @version 1.0.1
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! isset( $coupon_code ) || ! isset( $price_discount ) ) {
	return;
}
?>
<tr class="lp-applied-coupon">
	<th colspan="2" class="lp-coupon__title">
		<?php esc_html_e( 'Coupon:', 'learnpress-upsell' ); ?>
		<?php echo esc_html( $coupon_code ); ?>
	</th>
	<td>
		<div class="lp-coupon__wrapper">
			<span class="lp-coupon__discount">
				<?php printf( '- %s', $price_discount ); ?>
			</span>
			<a href="#" class="lp-coupon__remove" data-code="<?php echo $coupon_code; ?>"
				data-text-loading="<?php _e( 'loading...', 'learnpress-upsell' ); ?>">
				<?php esc_html_e( '[Remove]', 'learnpress-upsell' ); ?>
			</a>
		</div>
	</td>
</tr>
