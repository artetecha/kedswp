<?php
/**
 * Title: KEDS Programme Cards
 * Slug: eduma-child/featured-courses
 * Categories: keds
 * Description: A responsive grid of programme cards linking to each KEDS programme landing page.
 * Keywords: courses, programmes, cards, grid, curriculum
 * Viewport Width: 1280
 *
 * Note: These are curated marketing cards linking to the programme pages. For a
 * live, data-driven list you can instead drop in the LearnPress "Courses" block
 * (available once LearnPress is active in the editor).
 *
 * @package eduma-child
 */

?>
<!-- wp:group {"tagName":"section","align":"full","style":{"spacing":{"padding":{"top":"var:preset|spacing|80","bottom":"var:preset|spacing|80"}}},"layout":{"type":"constrained"}} -->
<section class="wp-block-group alignfull" style="padding-top:var(--wp--preset--spacing--80);padding-bottom:var(--wp--preset--spacing--80)">
	<!-- wp:paragraph {"align":"center","fontSize":"small","textColor":"primary","style":{"typography":{"textTransform":"uppercase","letterSpacing":"0.08em","fontWeight":"600"}}} -->
	<p class="has-text-align-center has-primary-color has-text-color has-small-font-size" style="font-weight:600;letter-spacing:0.08em;text-transform:uppercase"><?php echo esc_html__( 'Programmes', 'eduma-child' ); ?></p>
	<!-- /wp:paragraph -->

	<!-- wp:heading {"textAlign":"center"} -->
	<h2 class="wp-block-heading has-text-align-center"><?php echo esc_html__( 'Find the Pathway for Your Calling', 'eduma-child' ); ?></h2>
	<!-- /wp:heading -->

	<!-- wp:paragraph {"align":"center","textColor":"body","style":{"spacing":{"margin":{"bottom":"var:preset|spacing|60"}}},"layout":{"type":"constrained","contentSize":"640px"}} -->
	<p class="has-text-align-center has-body-color has-text-color" style="margin-bottom:var(--wp--preset--spacing--60)"><?php echo esc_html__( 'From your first steps in the Bible to doctoral research, choose the level that meets you where you are.', 'eduma-child' ); ?></p>
	<!-- /wp:paragraph -->

	<!-- wp:group {"align":"wide","layout":{"type":"grid","minimumColumnWidth":"18rem"}} -->
	<div class="wp-block-group alignwide">
		<?php
		$keds_programmes = array(
			array( __( 'Short Course', 'eduma-child' ), __( 'Knowing Your Bible', 'eduma-child' ), __( 'A guided introduction to reading and understanding Scripture with confidence.', 'eduma-child' ), '/knowing-your-bible-kyb/' ),
			array( __( 'Certificate', 'eduma-child' ), __( 'Foundations in Biblical Interpretation', 'eduma-child' ), __( 'Build the interpretive skills that underpin all faithful study of the Bible.', 'eduma-child' ), '/foundations-in-biblical-interpretation/' ),
			array( __( 'Specialist', 'eduma-child' ), __( 'Jewish-Christian Studies', 'eduma-child' ), __( 'Explore the Jewish roots of the Christian faith and the New Testament world.', 'eduma-child' ), '/jewish-christian-studies/' ),
			array( __( 'Specialist', 'eduma-child' ), __( 'Biblical Languages', 'eduma-child' ), __( 'Read the Scriptures in their original Hebrew and Greek.', 'eduma-child' ), '/biblical-languages/' ),
			array( __( 'EQF Level 6', 'eduma-child' ), __( 'Bachelor-Level Programme', 'eduma-child' ), __( 'A full undergraduate-level programme in theology, studied flexibly online.', 'eduma-child' ), '/bachelor-level-programme/' ),
			array( __( 'EQF Level 7', 'eduma-child' ), __( 'Master-Level Programme', 'eduma-child' ), __( 'Advance your theological study to postgraduate depth and specialism.', 'eduma-child' ), '/master-level-programme/' ),
			array( __( 'Doctoral', 'eduma-child' ), __( 'Doctoral Programmes', 'eduma-child' ), __( 'Original, supervised research at the highest academic level.', 'eduma-child' ), '/doctoral-programmes/' ),
		);
		foreach ( $keds_programmes as $keds_programme ) :
			?>
		<!-- wp:group {"backgroundColor":"base","style":{"border":{"color":"var:preset|color|border","width":"1px","radius":"12px"},"spacing":{"padding":{"top":"var:preset|spacing|50","bottom":"var:preset|spacing|50","left":"var:preset|spacing|50","right":"var:preset|spacing|50"},"blockGap":"var:preset|spacing|30"}},"layout":{"type":"constrained"}} -->
		<div class="wp-block-group has-base-background-color has-background has-border-color" style="border-color:var(--wp--preset--color--border);border-width:1px;border-radius:12px;padding-top:var(--wp--preset--spacing--50);padding-right:var(--wp--preset--spacing--50);padding-bottom:var(--wp--preset--spacing--50);padding-left:var(--wp--preset--spacing--50)">
			<!-- wp:paragraph {"fontSize":"small","textColor":"accent-600","style":{"typography":{"textTransform":"uppercase","letterSpacing":"0.06em","fontWeight":"700"}}} -->
			<p class="has-accent-600-color has-text-color has-small-font-size" style="font-weight:700;letter-spacing:0.06em;text-transform:uppercase"><?php echo esc_html( $keds_programme[0] ); ?></p>
			<!-- /wp:paragraph -->
			<!-- wp:heading {"level":3,"fontSize":"large"} -->
			<h3 class="wp-block-heading has-large-font-size"><?php echo esc_html( $keds_programme[1] ); ?></h3>
			<!-- /wp:heading -->
			<!-- wp:paragraph {"textColor":"body"} -->
			<p class="has-body-color has-text-color"><?php echo esc_html( $keds_programme[2] ); ?></p>
			<!-- /wp:paragraph -->
			<!-- wp:paragraph {"style":{"spacing":{"margin":{"top":"var:preset|spacing|20"}}}} -->
			<p style="margin-top:var(--wp--preset--spacing--20)"><a href="<?php echo esc_url( $keds_programme[3] ); ?>"><?php echo esc_html__( 'View programme', 'eduma-child' ); ?> &rarr;</a></p>
			<!-- /wp:paragraph -->
		</div>
		<!-- /wp:group -->
		<?php endforeach; ?>
	</div>
	<!-- /wp:group -->
</section>
<!-- /wp:group -->
