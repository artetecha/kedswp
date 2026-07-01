<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly
global $product;
?>
<div id="content" class="quickview woocommerce">
	<div class="product-info">
		<?php if ( ! post_password_required() ) : ?>
			<script type="text/javascript">
				jQuery(document).ready(function () {
					if (jQuery().flexslider) {
						jQuery('#slider').flexslider({
							animation    : "slide",
							controlNav   : false,
							animationLoop: false,
							slideshow    : false,
							directionNav : true,
							prevText     : "",
							start        : function (slider) {
								jQuery('body').removeClass('loading');
							}
						});
					}
				});
			</script>
			<div class="left col-sm-6">
				<div id="slider" class="flexslider">
					<ul class="slides">
						<?php
						if ( has_post_thumbnail() ) {
							$image = get_the_post_thumbnail( $product->get_id(), apply_filters( 'single_product_large_thumbnail_size', 'shop_single' ) );
							echo apply_filters( 'woocommerce_single_product_image_html', sprintf( '<li>%s</li>', $image ), $product->get_id() );
						}
						$attachment_ids = $product->get_gallery_image_ids();
						foreach ( $attachment_ids as $attachment_id ) {
							$image = wp_get_attachment_image( $attachment_id, apply_filters( 'single_product_large_thumbnail_size', 'shop_single' ) );
							echo apply_filters( 'woocommerce_single_product_image_html', sprintf( '<li>%s</li>', $image ), $product->get_id() );
						}
						?>
					</ul>
				</div>
			</div>
			<div class="right col-sm-6">
				<?php
				/**
				 * woocommerce_single_product_summary hook
				 *
				 * @hooked woocommerce_template_single_title - 5
				 * @hooked woocommerce_template_single_price - 10
				 * @hooked woocommerce_template_single_excerpt - 20
				 * @hooked woocommerce_template_single_add_to_cart - 30
				 * @hooked woocommerce_template_single_meta - 40
				 * @hooked woocommerce_template_single_sharing - 50
				 */
				do_action( 'woocommerce_single_product_summary_quick' );
				?>

			</div>
			<div class="clear"></div>
			<?php echo '<a href="' . esc_url( get_the_permalink( $product->get_id() ) ) . '" target="_top" class="quick-view-detail">' . esc_html__( 'View Detail', 'eduma' ) . '</a>'; ?>
			<?php
		else :
			echo get_the_password_form();  // WPCS: XSS ok.
		endif;
		?>
	</div>
</div>
