<?php
/**
 * Template for displaying notice when buy course via product
 *
 * @author  ThimPress
 * @package LearnPress/Templates
 * @version 1.0.3
 */

defined( 'ABSPATH' ) || exit();

if ( ! isset( $products ) ) {
	return;
}

wp_enqueue_style( 'lp-woo-css' );
?>

<div class="course-via-product">
	<div class="learn-press-message warning">
		<?php if ( empty( $products ) ) : ?>
			<p>
				<?php
				if ( current_user_can( 'administrator' ) || current_user_can( LP_TEACHER_ROLE ) ) {
					_e( 'Purchase is only available if the course is already assigned to a product!', 'learnpress-woo-payment' );
				} else {
					_e( 'You couldn\'t purchase this course because it hasn\'t been assigned to any product yet!', 'learnpress-woo-payment' );
				}
				?>
			</p>
		<?php else : ?>
			<div>
				<?php
				_e( 'You need to purchase courses from below products list to begin learning.', 'learnpress-woo-payment' );
				?>
			</div>
			<ul>
				<?php foreach ( $products as $product ) : ?>
					<li>
						<a href="<?php echo get_permalink( $product->ID ); ?>"><?php echo get_the_title( $product->ID ); ?></a>
					</li>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>
	</div>
</div>
