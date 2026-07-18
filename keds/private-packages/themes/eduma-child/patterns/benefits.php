<?php
/**
 * Title: KEDS Benefits Grid
 * Slug: eduma-child/benefits
 * Categories: keds
 * Description: "Why KEDS works for you" — a responsive grid of four benefit cards on the brand surface.
 * Keywords: benefits, features, why, grid, cards
 * Viewport Width: 1280
 *
 * @package eduma-child
 */

?>
<!-- wp:group {"tagName":"section","align":"full","backgroundColor":"surface","style":{"spacing":{"padding":{"top":"var:preset|spacing|80","bottom":"var:preset|spacing|80"}}},"layout":{"type":"constrained"}} -->
<section class="wp-block-group alignfull has-surface-background-color has-background" style="padding-top:var(--wp--preset--spacing--80);padding-bottom:var(--wp--preset--spacing--80)">
	<!-- wp:paragraph {"align":"center","fontSize":"small","textColor":"primary","style":{"typography":{"textTransform":"uppercase","letterSpacing":"0.08em","fontWeight":"600"}}} -->
	<p class="has-text-align-center has-primary-color has-text-color has-small-font-size" style="font-weight:600;letter-spacing:0.08em;text-transform:uppercase"><?php echo esc_html__( 'Why choose KEDS', 'eduma-child' ); ?></p>
	<!-- /wp:paragraph -->

	<!-- wp:heading {"textAlign":"center"} -->
	<h2 class="wp-block-heading has-text-align-center"><?php echo esc_html__( 'Why KEDS works for You', 'eduma-child' ); ?></h2>
	<!-- /wp:heading -->

	<!-- wp:paragraph {"align":"center","textColor":"body","style":{"spacing":{"margin":{"bottom":"var:preset|spacing|60"}}},"layout":{"type":"constrained","contentSize":"640px"}} -->
	<p class="has-text-align-center has-body-color has-text-color" style="margin-bottom:var(--wp--preset--spacing--60)"><?php echo esc_html__( 'We understand the frustration of struggling to find time to study and not being empowered to serve the way you long to. KEDS is built around your life and your calling.', 'eduma-child' ); ?></p>
	<!-- /wp:paragraph -->

	<!-- wp:group {"align":"wide","layout":{"type":"grid","minimumColumnWidth":"15rem"}} -->
	<div class="wp-block-group alignwide">
		<?php
		$keds_benefits = array(
			array( __( 'Flexible Learning', 'eduma-child' ), __( 'Organise your studies around your day-to-day life.', 'eduma-child' ) ),
			array( __( 'Personal Tuition', 'eduma-child' ), __( 'Receive support from experienced, published tutors.', 'eduma-child' ) ),
			array( __( 'Student Progression', 'eduma-child' ), __( 'Gain academic credit as you grow in your calling.', 'eduma-child' ) ),
			array( __( 'Global Access', 'eduma-child' ), __( 'Study from anywhere in the world, 100% online.', 'eduma-child' ) ),
		);
		foreach ( $keds_benefits as $keds_benefit ) :
			?>
		<!-- wp:group {"backgroundColor":"base","style":{"border":{"color":"var:preset|color|border","width":"1px","radius":"12px"},"spacing":{"padding":{"top":"var:preset|spacing|50","bottom":"var:preset|spacing|50","left":"var:preset|spacing|50","right":"var:preset|spacing|50"},"blockGap":"var:preset|spacing|30"}},"layout":{"type":"constrained"}} -->
		<div class="wp-block-group has-base-background-color has-background has-border-color" style="border-color:var(--wp--preset--color--border);border-width:1px;border-radius:12px;padding-top:var(--wp--preset--spacing--50);padding-right:var(--wp--preset--spacing--50);padding-bottom:var(--wp--preset--spacing--50);padding-left:var(--wp--preset--spacing--50)">
			<!-- wp:heading {"level":3,"fontSize":"large"} -->
			<h3 class="wp-block-heading has-large-font-size"><?php echo esc_html( $keds_benefit[0] ); ?></h3>
			<!-- /wp:heading -->
			<!-- wp:paragraph {"textColor":"body"} -->
			<p class="has-body-color has-text-color"><?php echo esc_html( $keds_benefit[1] ); ?></p>
			<!-- /wp:paragraph -->
		</div>
		<!-- /wp:group -->
		<?php endforeach; ?>
	</div>
	<!-- /wp:group -->
</section>
<!-- /wp:group -->
