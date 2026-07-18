<?php
/**
 * Title: KEDS FAQ Accordion
 * Slug: eduma-child/faq
 * Categories: keds
 * Description: Frequently asked questions as an accessible, native disclosure (details) accordion.
 * Keywords: faq, questions, accordion, details, help
 * Viewport Width: 1280
 *
 * @package eduma-child
 */

?>
<!-- wp:group {"tagName":"section","align":"full","backgroundColor":"surface","style":{"spacing":{"padding":{"top":"var:preset|spacing|80","bottom":"var:preset|spacing|80"}}},"layout":{"type":"constrained"}} -->
<section class="wp-block-group alignfull has-surface-background-color has-background" style="padding-top:var(--wp--preset--spacing--80);padding-bottom:var(--wp--preset--spacing--80)">
	<!-- wp:heading {"textAlign":"center","style":{"spacing":{"margin":{"bottom":"var:preset|spacing|60"}}}} -->
	<h2 class="wp-block-heading has-text-align-center" style="margin-bottom:var(--wp--preset--spacing--60)"><?php echo esc_html__( 'Frequently Asked Questions', 'eduma-child' ); ?></h2>
	<!-- /wp:heading -->

	<!-- wp:group {"layout":{"type":"constrained","contentSize":"760px"}} -->
	<div class="wp-block-group">
		<?php
		$keds_faqs = array(
			array( __( 'Do I need any prior qualifications to start?', 'eduma-child' ), __( 'Most of our short courses and certificates are open to all. Higher-level programmes have entry requirements, which are listed on each programme page.', 'eduma-child' ) ),
			array( __( 'Is study fully online?', 'eduma-child' ), __( 'Yes. KEDS is 100% online, so you can study from anywhere in the world and organise your learning around your life.', 'eduma-child' ) ),
			array( __( 'How is the teaching delivered?', 'eduma-child' ), __( 'You learn through written materials, recorded lectures and personal tuition from experienced tutors, with academic supervision throughout.', 'eduma-child' ) ),
			array( __( 'Can I pay module by module?', 'eduma-child' ), __( 'Yes. You can purchase your first module and gain instant access, then progress at a pace and cost that suits you.', 'eduma-child' ) ),
			array( __( 'Are the qualifications recognised?', 'eduma-child' ), __( 'Our programmes are mapped to EQF levels and KEDS is working towards full external accreditation with ECTE. See each programme page for details.', 'eduma-child' ) ),
		);
		foreach ( $keds_faqs as $keds_faq ) :
			?>
		<!-- wp:details {"summary":"<?php echo esc_attr( $keds_faq[0] ); ?>","style":{"border":{"color":"var:preset|color|border","width":"1px","radius":"10px"},"spacing":{"padding":{"top":"var:preset|spacing|40","bottom":"var:preset|spacing|40","left":"var:preset|spacing|50","right":"var:preset|spacing|50"},"margin":{"bottom":"var:preset|spacing|30"}}},"backgroundColor":"base"} -->
		<details class="wp-block-details has-base-background-color has-background has-border-color" style="border-color:var(--wp--preset--color--border);border-width:1px;border-radius:10px;padding-top:var(--wp--preset--spacing--40);padding-right:var(--wp--preset--spacing--50);padding-bottom:var(--wp--preset--spacing--40);padding-left:var(--wp--preset--spacing--50);margin-bottom:var(--wp--preset--spacing--30)"><summary><?php echo esc_html( $keds_faq[0] ); ?></summary>
			<!-- wp:paragraph {"textColor":"body"} -->
			<p class="has-body-color has-text-color"><?php echo esc_html( $keds_faq[1] ); ?></p>
			<!-- /wp:paragraph -->
		</details>
		<!-- /wp:details -->
		<?php endforeach; ?>
	</div>
	<!-- /wp:group -->
</section>
<!-- /wp:group -->
