<?php
/**
 * Title: KEDS Trusted Partners
 * Slug: eduma-child/partners
 * Categories: keds
 * Description: A "Trusted Partners" strip with real partner logos and an accreditation note.
 * Keywords: partners, logos, accreditation, trust
 * Viewport Width: 1280
 *
 * Logos reuse the live site's media. Replace each Image block to change a logo.
 *
 * @package eduma-child
 */

$keds_partners = array(
	array( '/wp-content/uploads/2024/11/Screenshot-2024-11-12-at-5.20.39%E2%80%AFPM-300x133.png', __( 'University of Chester', 'eduma-child' ), 'https://www.chester.ac.uk' ),
	array( '/wp-content/uploads/2024/09/ea-logo-gc-rgb-transparent-larger-1024x155.png', __( 'Evangelical Alliance', 'eduma-child' ), 'https://www.eauk.org' ),
	array( '/wp-content/uploads/2024/12/ECTElogo-removebg-preview-300x126.png', __( 'ECTE', 'eduma-child' ), 'https://ecte.eu' ),
);
?>
<!-- wp:group {"tagName":"section","align":"full","backgroundColor":"surface","style":{"spacing":{"padding":{"top":"var:preset|spacing|80","bottom":"var:preset|spacing|80"}}},"layout":{"type":"constrained"}} -->
<section class="wp-block-group alignfull has-surface-background-color has-background" style="padding-top:var(--wp--preset--spacing--80);padding-bottom:var(--wp--preset--spacing--80)">
	<!-- wp:heading {"textAlign":"center"} -->
	<h2 class="wp-block-heading has-text-align-center"><?php echo esc_html__( 'Trusted Partners', 'eduma-child' ); ?></h2>
	<!-- /wp:heading -->

	<!-- wp:paragraph {"align":"center","textColor":"body","style":{"spacing":{"margin":{"bottom":"var:preset|spacing|60"}}},"layout":{"type":"constrained","contentSize":"640px"}} -->
	<p class="has-text-align-center has-body-color has-text-color" style="margin-bottom:var(--wp--preset--spacing--60)"><?php echo esc_html__( 'Join our growing network of partners helping students get equipped for their calling.', 'eduma-child' ); ?></p>
	<!-- /wp:paragraph -->

	<!-- wp:group {"align":"wide","className":"keds-card-grid","layout":{"type":"default"}} -->
	<div class="wp-block-group alignwide keds-card-grid">
		<?php foreach ( $keds_partners as $keds_partner ) : ?>
		<!-- wp:group {"backgroundColor":"base","className":"keds-partner-cell","style":{"border":{"color":"var:preset|color|border","width":"1px","radius":"12px"},"spacing":{"padding":{"top":"var:preset|spacing|50","bottom":"var:preset|spacing|50","left":"var:preset|spacing|50","right":"var:preset|spacing|50"}},"dimensions":{"minHeight":"8rem"}},"layout":{"type":"flex","orientation":"vertical","justifyContent":"center","verticalAlignment":"center"}} -->
		<div class="wp-block-group keds-partner-cell has-base-background-color has-background has-border-color" style="border-color:var(--wp--preset--color--border);border-width:1px;border-radius:12px;min-height:8rem;padding-top:var(--wp--preset--spacing--50);padding-right:var(--wp--preset--spacing--50);padding-bottom:var(--wp--preset--spacing--50);padding-left:var(--wp--preset--spacing--50)">
			<!-- wp:image {"className":"keds-partner-logo","sizeSlug":"large","linkDestination":"custom"} -->
			<figure class="wp-block-image size-large keds-partner-logo"><a href="<?php echo esc_url( $keds_partner[2] ); ?>"><img src="<?php echo esc_url( $keds_partner[0] ); ?>" alt="<?php echo esc_attr( $keds_partner[1] ); ?>"/></a></figure>
			<!-- /wp:image -->
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
