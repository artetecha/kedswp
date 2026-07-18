<?php
/**
 * Title: KEDS Hero
 * Slug: eduma-child/hero
 * Categories: keds
 * Description: Full-width hero with headline, supporting copy and two calls to action, on the brand indigo wash.
 * Keywords: hero, banner, call to action
 * Block Types: core/cover
 * Viewport Width: 1280
 *
 * @package eduma-child
 */

?>
<!-- wp:group {"tagName":"section","align":"full","className":"keds-hero","style":{"spacing":{"padding":{"top":"var:preset|spacing|80","bottom":"var:preset|spacing|80"}}},"layout":{"type":"constrained"}} -->
<section class="wp-block-group alignfull keds-hero" style="padding-top:var(--wp--preset--spacing--80);padding-bottom:var(--wp--preset--spacing--80)">
	<!-- wp:group {"style":{"spacing":{"blockGap":"var:preset|spacing|50"}},"layout":{"type":"constrained","contentSize":"720px"}} -->
	<div class="wp-block-group">
		<!-- wp:heading {"textAlign":"center","level":1,"style":{"typography":{"lineHeight":"1.1"}}} -->
		<h1 class="wp-block-heading has-text-align-center" style="line-height:1.1"><?php echo esc_html__( 'Feel Called to Serve but Not Fully Equipped?', 'eduma-child' ); ?></h1>
		<!-- /wp:heading -->

		<!-- wp:paragraph {"align":"center","fontSize":"large","textColor":"body"} -->
		<p class="has-text-align-center has-body-color has-text-color has-large-font-size"><?php echo esc_html__( 'Grow in biblical knowledge and theological confidence so you can rise up to your calling — study flexibly, with personal tuition, from anywhere in the world.', 'eduma-child' ); ?></p>
		<!-- /wp:paragraph -->

		<!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"},"style":{"spacing":{"marginTop":"var:preset|spacing|50"}}} -->
		<div class="wp-block-buttons" style="margin-top:var(--wp--preset--spacing--50)">
			<!-- wp:button -->
			<div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="/course-overview/"><?php echo esc_html__( 'Start Here', 'eduma-child' ); ?></a></div>
			<!-- /wp:button -->

			<!-- wp:button {"className":"is-style-outline"} -->
			<div class="wp-block-button is-style-outline"><a class="wp-block-button__link wp-element-button" href="/prospectus-download-form/"><?php echo esc_html__( 'Learn More', 'eduma-child' ); ?></a></div>
			<!-- /wp:button -->
		</div>
		<!-- /wp:buttons -->
	</div>
	<!-- /wp:group -->
</section>
<!-- /wp:group -->
