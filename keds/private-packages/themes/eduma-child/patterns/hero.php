<?php
/**
 * Title: KEDS Hero
 * Slug: eduma-child/hero
 * Categories: keds
 * Description: Full-width hero banner image with brand overlay, headline, supporting copy and two calls to action.
 * Keywords: hero, banner, cover, call to action
 * Block Types: core/cover
 * Viewport Width: 1280
 *
 * The background image reuses the live site's banner. Swap the URL (or set an
 * image via the editor) to change it.
 *
 * @package eduma-child
 */

?>
<!-- wp:cover {"url":"/wp-content/uploads/2026/07/rise_up_banner.avif","dimRatio":60,"overlayColor":"primary-700","isUserOverlayColor":true,"minHeight":72,"minHeightUnit":"vh","isDark":true,"align":"full","className":"keds-hero","style":{"spacing":{"padding":{"top":"var:preset|spacing|80","bottom":"var:preset|spacing|80"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-cover alignfull keds-hero" style="padding-top:var(--wp--preset--spacing--80);padding-bottom:var(--wp--preset--spacing--80);min-height:72vh"><span aria-hidden="true" class="wp-block-cover__background has-primary-700-background-color has-background-dim-60 has-background-dim"></span><img class="wp-block-cover__image-background" alt="" src="/wp-content/uploads/2026/07/rise_up_banner.avif" data-object-fit="cover"/><div class="wp-block-cover__inner-container">
	<!-- wp:group {"style":{"spacing":{"blockGap":"var:preset|spacing|50"}},"layout":{"type":"constrained","contentSize":"760px"}} -->
	<div class="wp-block-group">
		<!-- wp:heading {"textAlign":"center","level":1,"textColor":"base","style":{"typography":{"lineHeight":"1.1"}}} -->
		<h1 class="wp-block-heading has-text-align-center has-base-color has-text-color" style="line-height:1.1"><?php echo esc_html__( 'Feel Called to Serve but Not Fully Equipped?', 'eduma-child' ); ?></h1>
		<!-- /wp:heading -->

		<!-- wp:paragraph {"align":"center","fontSize":"large","textColor":"base"} -->
		<p class="has-text-align-center has-base-color has-text-color has-large-font-size"><?php echo esc_html__( 'Grow in biblical knowledge and theological confidence so you can rise up to your calling — study flexibly, with personal tuition, from anywhere in the world.', 'eduma-child' ); ?></p>
		<!-- /wp:paragraph -->

		<!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"},"style":{"spacing":{"marginTop":"var:preset|spacing|50"}}} -->
		<div class="wp-block-buttons" style="margin-top:var(--wp--preset--spacing--50)">
			<!-- wp:button {"backgroundColor":"accent","textColor":"primary-700","style":{"elements":{"link":{"color":{"text":"var:preset|color|primary-700"}}}}} -->
			<div class="wp-block-button"><a class="wp-block-button__link has-primary-700-color has-accent-background-color has-text-color has-background has-link-color wp-element-button" href="/course-overview/"><?php echo esc_html__( 'Start Here', 'eduma-child' ); ?></a></div>
			<!-- /wp:button -->

			<!-- wp:button {"className":"is-style-outline","textColor":"base","style":{"elements":{"link":{"color":{"text":"var:preset|color|base"}}},"border":{"color":"var:preset|color|base"}}} -->
			<div class="wp-block-button is-style-outline"><a class="wp-block-button__link has-base-color has-text-color has-link-color has-border-color wp-element-button" style="border-color:var(--wp--preset--color--base)" href="/prospectus-download-form/"><?php echo esc_html__( 'Learn More', 'eduma-child' ); ?></a></div>
			<!-- /wp:button -->
		</div>
		<!-- /wp:buttons -->
	</div>
	<!-- /wp:group -->
</div></div>
<!-- /wp:cover -->
