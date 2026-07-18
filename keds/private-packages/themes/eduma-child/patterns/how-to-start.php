<?php
/**
 * Title: KEDS How to Get Started
 * Slug: eduma-child/how-to-start
 * Categories: keds
 * Description: A three-step "How to get started" sequence with numbered badges and a call to action.
 * Keywords: steps, how to, get started, process, onboarding
 * Viewport Width: 1280
 *
 * @package eduma-child
 */

?>
<!-- wp:group {"tagName":"section","align":"full","style":{"spacing":{"padding":{"top":"var:preset|spacing|80","bottom":"var:preset|spacing|80"}}},"layout":{"type":"constrained"}} -->
<section class="wp-block-group alignfull" style="padding-top:var(--wp--preset--spacing--80);padding-bottom:var(--wp--preset--spacing--80)">
	<!-- wp:heading {"textAlign":"center","style":{"spacing":{"margin":{"bottom":"var:preset|spacing|60"}}}} -->
	<h2 class="wp-block-heading has-text-align-center" style="margin-bottom:var(--wp--preset--spacing--60)"><?php echo esc_html__( 'How to Get Started', 'eduma-child' ); ?></h2>
	<!-- /wp:heading -->

	<!-- wp:group {"align":"wide","layout":{"type":"grid","minimumColumnWidth":"18rem"}} -->
	<div class="wp-block-group alignwide">
		<?php
		$keds_steps = array(
			array( '1', __( 'Choose a Programme', 'eduma-child' ), __( 'Select the right programme for you to rise up to your calling.', 'eduma-child' ) ),
			array( '2', __( 'Apply for Your Programme', 'eduma-child' ), __( 'Complete the simple application process for your chosen programme.', 'eduma-child' ) ),
			array( '3', __( 'Rise Up to Your Calling', 'eduma-child' ), __( 'Purchase your first module and gain instant access to your course material.', 'eduma-child' ) ),
		);
		foreach ( $keds_steps as $keds_step ) :
			?>
		<!-- wp:group {"style":{"spacing":{"blockGap":"var:preset|spacing|30"}},"layout":{"type":"constrained"}} -->
		<div class="wp-block-group">
			<!-- wp:paragraph {"className":"keds-step-number"} -->
			<p class="keds-step-number"><?php echo esc_html( $keds_step[0] ); ?></p>
			<!-- /wp:paragraph -->
			<!-- wp:heading {"level":3,"fontSize":"large"} -->
			<h3 class="wp-block-heading has-large-font-size"><?php echo esc_html( $keds_step[1] ); ?></h3>
			<!-- /wp:heading -->
			<!-- wp:paragraph {"textColor":"body"} -->
			<p class="has-body-color has-text-color"><?php echo esc_html( $keds_step[2] ); ?></p>
			<!-- /wp:paragraph -->
		</div>
		<!-- /wp:group -->
		<?php endforeach; ?>
	</div>
	<!-- /wp:group -->

	<!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"},"style":{"spacing":{"margin":{"top":"var:preset|spacing|60"}}}} -->
	<div class="wp-block-buttons" style="margin-top:var(--wp--preset--spacing--60)">
		<!-- wp:button -->
		<div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="/course-overview/"><?php echo esc_html__( 'Start Here', 'eduma-child' ); ?></a></div>
		<!-- /wp:button -->
	</div>
	<!-- /wp:buttons -->
</section>
<!-- /wp:group -->
