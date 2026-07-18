<?php
/**
 * Title: KEDS Call to Action
 * Slug: eduma-child/cta
 * Categories: keds
 * Description: A full-width closing call-to-action band on the indigo gradient.
 * Keywords: cta, call to action, banner, enrol, signup
 * Viewport Width: 1280
 *
 * @package eduma-child
 */

?>
<!-- wp:group {"tagName":"section","align":"full","gradient":"indigo","textColor":"base","style":{"spacing":{"padding":{"top":"var:preset|spacing|80","bottom":"var:preset|spacing|80"}},"elements":{"heading":{"color":{"text":"var:preset|color|base"}}}},"layout":{"type":"constrained"}} -->
<section class="wp-block-group alignfull has-base-color has-indigo-gradient-background has-text-color has-background" style="padding-top:var(--wp--preset--spacing--80);padding-bottom:var(--wp--preset--spacing--80)">
	<!-- wp:group {"layout":{"type":"constrained","contentSize":"680px"}} -->
	<div class="wp-block-group">
		<!-- wp:heading {"textAlign":"center","style":{"color":{"text":"var:preset|color|base"}}} -->
		<h2 class="wp-block-heading has-text-align-center has-text-color" style="color:var(--wp--preset--color--base)"><?php echo esc_html__( 'Rise Up to Your Calling', 'eduma-child' ); ?></h2>
		<!-- /wp:heading -->

		<!-- wp:paragraph {"align":"center","fontSize":"large","style":{"color":{"text":"#dcdcf5"}}} -->
		<p class="has-text-align-center has-text-color has-large-font-size" style="color:#dcdcf5"><?php echo esc_html__( 'Take the first step towards biblical knowledge and theological confidence — study flexibly, guided by experts, from anywhere in the world.', 'eduma-child' ); ?></p>
		<!-- /wp:paragraph -->

		<!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"},"style":{"spacing":{"margin":{"top":"var:preset|spacing|50"}}}} -->
		<div class="wp-block-buttons" style="margin-top:var(--wp--preset--spacing--50)">
			<!-- wp:button {"backgroundColor":"accent","textColor":"primary-700","style":{"elements":{"link":{"color":{"text":"var:preset|color|primary-700"}}}}} -->
			<div class="wp-block-button"><a class="wp-block-button__link has-primary-700-color has-accent-background-color has-text-color has-background has-link-color wp-element-button" href="/course-overview/"><?php echo esc_html__( 'Start Here', 'eduma-child' ); ?></a></div>
			<!-- /wp:button -->

			<!-- wp:button {"className":"is-style-outline","textColor":"base","style":{"elements":{"link":{"color":{"text":"var:preset|color|base"}}},"border":{"color":"var:preset|color|base"}}} -->
			<div class="wp-block-button is-style-outline"><a class="wp-block-button__link has-base-color has-text-color has-link-color has-border-color wp-element-button" style="border-color:var(--wp--preset--color--base)" href="/prospectus-download-form/"><?php echo esc_html__( 'Download Prospectus', 'eduma-child' ); ?></a></div>
			<!-- /wp:button -->
		</div>
		<!-- /wp:buttons -->
	</div>
	<!-- /wp:group -->
</section>
<!-- /wp:group -->
