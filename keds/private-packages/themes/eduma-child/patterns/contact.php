<?php
/**
 * Title: KEDS Contact
 * Slug: eduma-child/contact
 * Categories: keds
 * Description: A two-column contact section — contact details beside a contact form placeholder.
 * Keywords: contact, form, address, phone, email
 * Viewport Width: 1280
 *
 * Note: The right column holds a Fluent Forms shortcode placeholder. Replace the
 * id in [fluentform id="1"] with the real contact form id (Fluent Forms → All Forms).
 *
 * @package eduma-child
 */

?>
<!-- wp:group {"tagName":"section","align":"full","style":{"spacing":{"padding":{"top":"var:preset|spacing|80","bottom":"var:preset|spacing|80"}}},"layout":{"type":"constrained"}} -->
<section class="wp-block-group alignfull" style="padding-top:var(--wp--preset--spacing--80);padding-bottom:var(--wp--preset--spacing--80)">
	<!-- wp:columns {"align":"wide","style":{"spacing":{"blockGap":{"top":"var:preset|spacing|70","left":"var:preset|spacing|70"}}}} -->
	<div class="wp-block-columns alignwide">
		<!-- wp:column {"width":"40%"} -->
		<div class="wp-block-column" style="flex-basis:40%">
			<!-- wp:heading -->
			<h2 class="wp-block-heading"><?php echo esc_html__( 'Get in Touch', 'eduma-child' ); ?></h2>
			<!-- /wp:heading -->

			<!-- wp:paragraph {"textColor":"body"} -->
			<p class="has-body-color has-text-color"><?php echo esc_html__( 'Have a question about a programme, application or partnership? We would love to hear from you.', 'eduma-child' ); ?></p>
			<!-- /wp:paragraph -->

			<!-- wp:group {"style":{"spacing":{"blockGap":"var:preset|spacing|40","margin":{"top":"var:preset|spacing|50"}}},"layout":{"type":"constrained"}} -->
			<div class="wp-block-group" style="margin-top:var(--wp--preset--spacing--50)">
				<!-- wp:paragraph {"style":{"spacing":{"margin":{"bottom":"0"}},"typography":{"fontWeight":"700"}}} -->
				<p style="margin-bottom:0;font-weight:700"><?php echo esc_html__( 'Phone', 'eduma-child' ); ?></p>
				<!-- /wp:paragraph -->
				<!-- wp:paragraph {"textColor":"body","style":{"spacing":{"margin":{"top":"0"}}}} -->
				<p class="has-body-color has-text-color" style="margin-top:0"><?php echo esc_html__( 'Domestic: 02038 131794', 'eduma-child' ); ?><br><?php echo esc_html__( 'International: +44 2038 131794', 'eduma-child' ); ?></p>
				<!-- /wp:paragraph -->

				<!-- wp:paragraph {"style":{"spacing":{"margin":{"bottom":"0"}},"typography":{"fontWeight":"700"}}} -->
				<p style="margin-bottom:0;font-weight:700"><?php echo esc_html__( 'Email', 'eduma-child' ); ?></p>
				<!-- /wp:paragraph -->
				<!-- wp:paragraph {"textColor":"body","style":{"spacing":{"margin":{"top":"0"}}}} -->
				<p class="has-body-color has-text-color" style="margin-top:0"><a href="mailto:office@kingsdivinity.org">office@kingsdivinity.org</a></p>
				<!-- /wp:paragraph -->

				<!-- wp:paragraph {"style":{"spacing":{"margin":{"bottom":"0"}},"typography":{"fontWeight":"700"}}} -->
				<p style="margin-bottom:0;font-weight:700"><?php echo esc_html__( 'Address', 'eduma-child' ); ?></p>
				<!-- /wp:paragraph -->
				<!-- wp:paragraph {"textColor":"body","style":{"spacing":{"margin":{"top":"0"}}}} -->
				<p class="has-body-color has-text-color" style="margin-top:0"><?php echo esc_html__( '1st Floor, 415 High Street, London, E15 4QZ', 'eduma-child' ); ?></p>
				<!-- /wp:paragraph -->
			</div>
			<!-- /wp:group -->
		</div>
		<!-- /wp:column -->

		<!-- wp:column {"width":"60%","backgroundColor":"surface","style":{"border":{"radius":"12px"},"spacing":{"padding":{"top":"var:preset|spacing|60","bottom":"var:preset|spacing|60","left":"var:preset|spacing|60","right":"var:preset|spacing|60"}}}} -->
		<div class="wp-block-column has-surface-background-color has-background" style="border-radius:12px;padding-top:var(--wp--preset--spacing--60);padding-right:var(--wp--preset--spacing--60);padding-bottom:var(--wp--preset--spacing--60);padding-left:var(--wp--preset--spacing--60);flex-basis:60%">
			<!-- wp:shortcode -->
			[fluentform id="1"]
			<!-- /wp:shortcode -->
		</div>
		<!-- /wp:column -->
	</div>
	<!-- /wp:columns -->
</section>
<!-- /wp:group -->
