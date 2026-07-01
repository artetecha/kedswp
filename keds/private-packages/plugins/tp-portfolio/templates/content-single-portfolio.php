<?php
/**
 * @package thimpress
 * @version 2.0.0
 */

defined( 'ABSPATH' ) || exit;

?>
<div id="post-<?php the_ID(); ?>" <?php post_class('portfolio-container'); ?>>
	<?php
	/**
	 * Hook: thim_portfolio_before_single_content
	 *
	 * @hooked thim_portfolio_template_loop_thumbnail - 20
	 */
	do_action( 'thim_portfolio_before_single_content' );
	?>

	<div class="entry-content">
		<?php
		/**
		 * Hook: thim_portfolio_single_content.
		 *
		 * @hooked thim_portfolio_template_single_title - 5
		 * @hooked thim_portfolio_template_single_meta - 10
		 * @hooked thim_portfolio_template_single_content - 20
		 * @hooked thim_portfolio_template_single_content_pdf - 20
		 * @hooked thim_portfolio_template_single_content_pdf - 20
		 * @hooked thim_portfolio_template_single_comment - 20
		 */
		do_action( 'thim_portfolio_single_content' );
		?>
	</div>

	<?php
	/**
	 *  Hook: thim_portfolio_after_single_content.
	 *
	 * @hooked thim_portfolio_output_related_products - 20
	 */
	do_action( 'thim_portfolio_after_single_content' );
	?>

</div>
