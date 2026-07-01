<?php
/**
 * Display single row item course of Package.
 *
 * @version 1.0.0
 * @since 4.0.1
 */

if ( ! isset( $course ) && ! isset( $item ) ) {
	return;
}

$order = learn_press_get_order( $item['order_id'] ?? 0 );
?>
<tr class="order-item-row" data-item_id="<?php echo esc_attr( $course->id ); ?>">
	<td class="column-name">

		<a href="<?php echo apply_filters( 'learn_press/order_item_link', get_edit_post_link( $course->id ) ); ?>">
			<?php echo apply_filters( 'learn_press/order_item_name', $course->title, $item, $order ); ?>
		</a>

		<?php do_action( 'learn_press/after_order_details_item_title', $course ); ?>
	</td>

	<td class="column-price align-right">
		<?php echo learn_press_format_price( $course->price ?? 0, $currency_symbol ?? '$' ); ?>
	</td>

	<td class="column-quantity align-right">

	</td>

	<td class="column-total align-right"><?php echo learn_press_format_price( $course->price ?? 0, $currency_symbol ?? '$' ); ?></td>
</tr>


