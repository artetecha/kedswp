<?php
/**
 * Title: KEDS Trusted Partners
 * Slug: eduma-child/partners
 * Categories: keds
 * Description: A "Trusted Partners" strip with logo placeholders and an accreditation note.
 * Keywords: partners, logos, accreditation, trust
 * Viewport Width: 1280
 *
 * Note: Replace each logo placeholder group with an Image block once the partner
 * logos are uploaded to the Media Library.
 *
 * @package eduma-child
 */

?>
<!-- wp:group {"tagName":"section","align":"full","style":{"spacing":{"padding":{"top":"var:preset|spacing|80","bottom":"var:preset|spacing|80"}}},"layout":{"type":"constrained"}} -->
<section class="wp-block-group alignfull" style="padding-top:var(--wp--preset--spacing--80);padding-bottom:var(--wp--preset--spacing--80)">
	<!-- wp:heading {"textAlign":"center"} -->
	<h2 class="wp-block-heading has-text-align-center"><?php echo esc_html__( 'Trusted Partners', 'eduma-child' ); ?></h2>
	<!-- /wp:heading -->

	<!-- wp:paragraph {"align":"center","textColor":"body","style":{"spacing":{"margin":{"bottom":"var:preset|spacing|60"}}},"layout":{"type":"constrained","contentSize":"640px"}} -->
	<p class="has-text-align-center has-body-color has-text-color" style="margin-bottom:var(--wp--preset--spacing--60)"><?php echo esc_html__( 'Join our growing network of partners helping students get equipped for their calling.', 'eduma-child' ); ?></p>
	<!-- /wp:paragraph -->

	<!-- wp:group {"align":"wide","layout":{"type":"grid","minimumColumnWidth":"14rem"}} -->
	<div class="wp-block-group alignwide">
		<?php
		$keds_partners = array(
			array( __( 'University of Chester', 'eduma-child' ), 'https://www.chester.ac.uk' ),
			array( __( 'Evangelical Alliance', 'eduma-child' ), 'https://www.eauk.org' ),
			array( __( 'ECTE', 'eduma-child' ), 'https://ecte.eu' ),
		);
		foreach ( $keds_partners as $keds_partner ) :
			?>
		<!-- wp:group {"backgroundColor":"surface","style":{"border":{"radius":"12px"},"spacing":{"padding":{"top":"var:preset|spacing|50","bottom":"var:preset|spacing|50","left":"var:preset|spacing|40","right":"var:preset|spacing|40"}},"dimensions":{"minHeight":"7rem"}},"layout":{"type":"flex","orientation":"vertical","justifyContent":"center","verticalAlignment":"center"}} -->
		<div class="wp-block-group has-surface-background-color has-background" style="border-radius:12px;min-height:7rem;padding-top:var(--wp--preset--spacing--50);padding-right:var(--wp--preset--spacing--40);padding-bottom:var(--wp--preset--spacing--50);padding-left:var(--wp--preset--spacing--40)">
			<!-- wp:paragraph {"align":"center","style":{"typography":{"fontWeight":"700"},"spacing":{"margin":{"top":"0","bottom":"0"}}},"textColor":"primary","fontFamily":"display"} -->
			<p class="has-text-align-center has-primary-color has-text-color has-display-font-family" style="margin-top:0;margin-bottom:0;font-weight:700"><a href="<?php echo esc_url( $keds_partner[1] ); ?>"><?php echo esc_html( $keds_partner[0] ); ?></a></p>
			<!-- /wp:paragraph -->
		</div>
		<!-- /wp:group -->
		<?php endforeach; ?>
	</div>
	<!-- /wp:group -->

	<!-- wp:paragraph {"align":"center","fontSize":"small","textColor":"muted","style":{"spacing":{"margin":{"top":"var:preset|spacing|50"}}},"layout":{"type":"constrained","contentSize":"720px"}} -->
	<p class="has-text-align-center has-muted-color has-text-color has-small-font-size" style="margin-top:var(--wp--preset--spacing--50)"><?php echo esc_html__( '*KEDS is currently working towards full external accreditation with ECTE as an alternative provider.', 'eduma-child' ); ?></p>
	<!-- /wp:paragraph -->
</section>
<!-- /wp:group -->
