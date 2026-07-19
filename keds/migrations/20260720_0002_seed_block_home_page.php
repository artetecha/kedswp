<?php
/**
 * Seed the block-based Home page and point the site front page at it.
 *
 * This is the reference implementation of the KEDS content-deploy contract:
 * page bodies are built visually in the editor, but reach production through a
 * git-tracked, reviewable, idempotent migration rather than a database push
 * (which would clobber live students/orders). The page content is assembled
 * from the KEDS section patterns so the theme's pattern library stays the
 * single source of truth for the initial markup.
 *
 * SEED, NOT SYNC — create-if-absent and non-destructive: if the page already
 * exists we DO NOT touch its content. After this initial seed the page is
 * "editor-owned": the content team edits it visually on the live site and
 * those edits persist (this migration only runs once per database anyway).
 *
 * GO-LIVE NOTE: on deploy to production this makes the block home the PROD
 * front page. It requires `eduma-child` to be active (see the 0001 migration
 * that runs first) so the design tokens/patterns actually apply.
 *
 * Not handled here: media. Patterns currently use text/placeholders; real
 * imagery (hero, partner logos) is uploaded on the target environment.
 */

return static function () {
	$slug = 'keds-home';

	$existing = get_page_by_path( $slug, OBJECT, 'page' );

	if ( $existing instanceof WP_Post ) {
		$id = (int) $existing->ID;
		echo "Home page already exists (#{$id}); leaving its content untouched.\n";
	} else {
		// Assemble the page body from the KEDS section patterns, in the same
		// order as the eduma-child/page-home composite. We include the pattern
		// files directly (rather than the pattern registry) so this does not
		// depend on the just-activated theme's patterns being registered within
		// this same wp-cli process. The result is fully-expanded, editable
		// block markup — a snapshot the content team then owns.
		$dir      = get_theme_root() . '/eduma-child/patterns/';
		$sections = array( 'hero', 'benefits', 'featured-courses', 'principal-quote', 'testimonials', 'how-to-start', 'partners', 'cta' );
		$content  = '';

		foreach ( $sections as $section ) {
			$file = $dir . $section . '.php';
			if ( ! is_readable( $file ) ) {
				throw new RuntimeException( "Missing pattern file: {$file}" );
			}
			ob_start();
			include $file;
			$markup = trim( (string) ob_get_clean() );
			if ( '' === $markup ) {
				throw new RuntimeException( "Pattern '{$section}' produced no markup." );
			}
			$content .= $markup . "\n\n";
		}

		$id = wp_insert_post(
			array(
				'post_type'    => 'page',
				'post_status'  => 'publish',
				'post_title'   => 'Home',
				'post_name'    => $slug,
				'post_content' => $content,
			),
			true
		);

		if ( is_wp_error( $id ) ) {
			throw new RuntimeException( 'Failed to create home page: ' . $id->get_error_message() );
		}

		echo "Created block home page #{$id} (" . strlen( $content ) . " bytes of block markup).\n";
	}

	// Hide Eduma's title bar + breadcrumb so the hero leads (per-page meta read
	// by eduma/inc/templates/page-title.php).
	update_post_meta( $id, 'thim_mtb_using_custom_heading', 1 );
	update_post_meta( $id, 'thim_mtb_hide_title_and_subtitle', 1 );
	update_post_meta( $id, 'thim_mtb_hide_breadcrumbs', 1 );

	// Point the site front page at it.
	update_option( 'show_on_front', 'page' );
	update_option( 'page_on_front', $id );

	echo "Front page set to #{$id}; Eduma title bar + breadcrumb hidden.\n";
};
