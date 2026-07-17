<?php
/**
 * Plugin Name: KEDS Google Tag Manager
 * Description: Prints the GTM container (GTM-NP72X8ZG) that delivers GA4 and
 * any further tags the marketing team manages in Tag Manager. This snippet
 * was hand-added to the Pantheon codebase (replacing Site Kit) and was lost
 * at the 2026-07-15 cutover because it never existed in this build — without
 * it, GA4 collection is dead. Rendered verbatim for all visitors, matching
 * how it ran on Pantheon.
 */

defined( 'ABSPATH' ) || exit;

const KEDS_GTM_CONTAINER_ID = 'GTM-NP72X8ZG';

add_action( 'wp_head', function () {
	?>
	<!-- Google Tag Manager -->
	<script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
	new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
	j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
	'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
	})(window,document,'script','dataLayer','<?php echo esc_js( KEDS_GTM_CONTAINER_ID ); ?>');</script>
	<!-- End Google Tag Manager -->
	<?php
}, 1 );

add_action( 'wp_body_open', function () {
	?>
	<!-- Google Tag Manager (noscript) -->
	<noscript><iframe src="https://www.googletagmanager.com/ns.html?id=<?php echo esc_attr( KEDS_GTM_CONTAINER_ID ); ?>"
	height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
	<!-- End Google Tag Manager (noscript) -->
	<?php
} );
