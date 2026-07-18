<?php
/**
 * Title: KEDS Principal Quote
 * Slug: eduma-child/principal-quote
 * Categories: keds
 * Description: A full-width indigo band with the Principal's quote and a stop/start contrast list.
 * Keywords: quote, principal, callout, stop start
 * Viewport Width: 1280
 *
 * @package eduma-child
 */

?>
<!-- wp:group {"tagName":"section","align":"full","backgroundColor":"primary","textColor":"base","style":{"spacing":{"padding":{"top":"var:preset|spacing|80","bottom":"var:preset|spacing|80"}},"elements":{"heading":{"color":{"text":"var:preset|color|base"}}}},"layout":{"type":"constrained"}} -->
<section class="wp-block-group alignfull has-base-color has-primary-background-color has-text-color has-background" style="padding-top:var(--wp--preset--spacing--80);padding-bottom:var(--wp--preset--spacing--80)">
	<!-- wp:group {"layout":{"type":"constrained","contentSize":"760px"}} -->
	<div class="wp-block-group">
		<!-- wp:heading {"textAlign":"center","level":3,"style":{"typography":{"fontStyle":"italic","fontWeight":"400","lineHeight":"1.4"},"color":{"text":"var:preset|color|base"}},"fontSize":"x-large"} -->
		<h3 class="wp-block-heading has-text-align-center has-text-color has-x-large-font-size" style="color:var(--wp--preset--color--base);font-style:italic;font-weight:400;line-height:1.4"><?php echo esc_html__( '“Don\'t waste further time scrolling through content that takes you nowhere. Be faithful to your calling and study with purpose.”', 'eduma-child' ); ?></h3>
		<!-- /wp:heading -->

		<!-- wp:paragraph {"align":"center","style":{"typography":{"fontWeight":"700"},"spacing":{"margin":{"top":"var:preset|spacing|40"}}}} -->
		<p class="has-text-align-center" style="margin-top:var(--wp--preset--spacing--40);font-weight:700"><?php echo esc_html__( 'Dr. Anthony P. Royle, Principal', 'eduma-child' ); ?></p>
		<!-- /wp:paragraph -->
	</div>
	<!-- /wp:group -->

	<!-- wp:group {"align":"wide","style":{"spacing":{"margin":{"top":"var:preset|spacing|70"}}},"layout":{"type":"grid","minimumColumnWidth":"18rem"}} -->
	<div class="wp-block-group alignwide" style="margin-top:var(--wp--preset--spacing--70)">
		<?php
		$keds_contrasts = array(
			array( __( 'Stop collecting content', 'eduma-child' ), __( 'Start following a clear pathway', 'eduma-child' ) ),
			array( __( 'Stop learning without purpose', 'eduma-child' ), __( 'Start learning with an expert guide', 'eduma-child' ) ),
			array( __( 'Stop putting study off', 'eduma-child' ), __( 'Start earning recognised qualifications', 'eduma-child' ) ),
		);
		foreach ( $keds_contrasts as $keds_contrast ) :
			?>
		<!-- wp:group {"style":{"spacing":{"blockGap":"var:preset|spacing|20"}},"layout":{"type":"constrained"}} -->
		<div class="wp-block-group">
			<!-- wp:paragraph {"style":{"color":{"text":"#c9caf0"}}} -->
			<p class="has-text-color" style="color:#c9caf0"><?php echo esc_html( $keds_contrast[0] ); ?></p>
			<!-- /wp:paragraph -->
			<!-- wp:paragraph {"textColor":"accent","style":{"typography":{"fontWeight":"700"}}} -->
			<p class="has-accent-color has-text-color" style="font-weight:700"><?php echo esc_html( $keds_contrast[1] ); ?></p>
			<!-- /wp:paragraph -->
		</div>
		<!-- /wp:group -->
		<?php endforeach; ?>
	</div>
	<!-- /wp:group -->
</section>
<!-- /wp:group -->
