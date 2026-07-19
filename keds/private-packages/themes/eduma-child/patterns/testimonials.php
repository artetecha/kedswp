<?php
/**
 * Title: KEDS Testimonials
 * Slug: eduma-child/testimonials
 * Categories: keds
 * Description: "Transformational Stories" — a swipeable testimonial carousel (arrows, dots, autoplay) with initials avatars.
 * Keywords: testimonials, reviews, stories, students, quotes, carousel, slider
 * Viewport Width: 1280
 *
 * The carousel is progressive: the slides are a native scroll-snap row that
 * works without JS; assets/js/keds-carousel.js adds arrows, dots and autoplay.
 * Avatars are initials (no photos) — drop in an Image block per slide once
 * real student photos are available.
 *
 * @package eduma-child
 */

$keds_testimonials = array(
	array( 'CI', __( '“KEDS deepened my understanding of theology and shaped my character and ministry outlook.”', 'eduma-child' ), __( 'Craig Ireland', 'eduma-child' ), __( 'From MA Student to Institutional Leader', 'eduma-child' ) ),
	array( 'RF', __( '“KEDS has given me the tools to seek truth through proper exegesis, freeing me from years of indoctrination.”', 'eduma-child' ), __( 'Roland Francois', 'eduma-child' ), __( 'From Cult Member to Independent Exegete', 'eduma-child' ) ),
	array( 'TD', __( '“I have grown in my personal walk with Christ through the KYB course.”', 'eduma-child' ), __( 'Tim Duthie', 'eduma-child' ), __( 'From Unsure to Confident', 'eduma-child' ) ),
	array( 'MA', __( '“From day one, I enjoyed my studies. The lectures, written materials and supervisors were superb.”', 'eduma-child' ), __( 'Mark Anderson', 'eduma-child' ), __( 'From Student to Teacher', 'eduma-child' ) ),
	array( 'JS', __( '“Studying with KEDS has been an enjoyable, at times challenging, experience which has stretched my heart and mind.”', 'eduma-child' ), __( 'Jenny Sheldon', 'eduma-child' ), __( 'From Curiosity to Confidence', 'eduma-child' ) ),
);
?>
<!-- wp:group {"tagName":"section","align":"full","backgroundColor":"surface","style":{"spacing":{"padding":{"top":"var:preset|spacing|80","bottom":"var:preset|spacing|80"}}},"layout":{"type":"constrained"}} -->
<section class="wp-block-group alignfull has-surface-background-color has-background" style="padding-top:var(--wp--preset--spacing--80);padding-bottom:var(--wp--preset--spacing--80)">
	<!-- wp:paragraph {"align":"center","fontSize":"small","textColor":"primary","style":{"typography":{"textTransform":"uppercase","letterSpacing":"0.08em","fontWeight":"600"}}} -->
	<p class="has-text-align-center has-primary-color has-text-color has-small-font-size" style="font-weight:600;letter-spacing:0.08em;text-transform:uppercase"><?php echo esc_html__( 'Student stories', 'eduma-child' ); ?></p>
	<!-- /wp:paragraph -->

	<!-- wp:heading {"textAlign":"center"} -->
	<h2 class="wp-block-heading has-text-align-center"><?php echo esc_html__( 'Transformational Stories', 'eduma-child' ); ?></h2>
	<!-- /wp:heading -->

	<!-- wp:paragraph {"align":"center","textColor":"body","style":{"spacing":{"margin":{"bottom":"var:preset|spacing|60"}}},"layout":{"type":"constrained","contentSize":"640px"}} -->
	<p class="has-text-align-center has-body-color has-text-color" style="margin-bottom:var(--wp--preset--spacing--60)"><?php echo esc_html__( 'Hear from former students about their life-changing experience with KEDS.', 'eduma-child' ); ?></p>
	<!-- /wp:paragraph -->

	<!-- wp:group {"align":"wide","className":"keds-carousel","layout":{"type":"default"}} -->
	<div class="wp-block-group alignwide keds-carousel">
		<!-- wp:group {"className":"keds-carousel__track","layout":{"type":"default"}} -->
		<div class="wp-block-group keds-carousel__track">
			<?php foreach ( $keds_testimonials as $keds_t ) : ?>
			<!-- wp:group {"className":"keds-carousel__slide","backgroundColor":"base","style":{"border":{"radius":"12px"},"spacing":{"padding":{"top":"var:preset|spacing|50","bottom":"var:preset|spacing|50","left":"var:preset|spacing|50","right":"var:preset|spacing|50"},"blockGap":"var:preset|spacing|40"}},"layout":{"type":"constrained"}} -->
			<div class="wp-block-group keds-carousel__slide has-base-background-color has-background" style="border-radius:12px;padding-top:var(--wp--preset--spacing--50);padding-right:var(--wp--preset--spacing--50);padding-bottom:var(--wp--preset--spacing--50);padding-left:var(--wp--preset--spacing--50)">
				<!-- wp:paragraph {"className":"keds-avatar","backgroundColor":"primary","textColor":"base","fontFamily":"display"} -->
				<p class="keds-avatar has-base-color has-primary-background-color has-text-color has-background has-display-font-family"><?php echo esc_html( $keds_t[0] ); ?></p>
				<!-- /wp:paragraph -->
				<!-- wp:paragraph {"fontSize":"large","textColor":"ink","style":{"typography":{"fontStyle":"italic","fontWeight":"400","lineHeight":"1.5"}},"fontFamily":"display"} -->
				<p class="has-ink-color has-text-color has-display-font-family has-large-font-size" style="font-style:italic;font-weight:400;line-height:1.5"><?php echo esc_html( $keds_t[1] ); ?></p>
				<!-- /wp:paragraph -->
				<!-- wp:group {"style":{"spacing":{"blockGap":"0"}},"layout":{"type":"constrained"}} -->
				<div class="wp-block-group">
					<!-- wp:paragraph {"style":{"typography":{"fontWeight":"700"}}} -->
					<p style="font-weight:700"><?php echo esc_html( $keds_t[2] ); ?></p>
					<!-- /wp:paragraph -->
					<!-- wp:paragraph {"fontSize":"small","textColor":"muted"} -->
					<p class="has-muted-color has-text-color has-small-font-size"><?php echo esc_html( $keds_t[3] ); ?></p>
					<!-- /wp:paragraph -->
				</div>
				<!-- /wp:group -->
			</div>
			<!-- /wp:group -->
			<?php endforeach; ?>
		</div>
		<!-- /wp:group -->
	</div>
	<!-- /wp:group -->
</section>
<!-- /wp:group -->
