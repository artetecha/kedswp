<?php

/**
 * The Template for displaying all single posts.
 *
 * @package    thimpress
 */

get_header();

do_action( 'thim_portfolio_layout_start' );

do_action( 'thim_portfolio_before_archive_portfolio' );

echo '<div class="portfolio-container">';
/**
 * Hook: thim_portfolio_loop_header.
 **
 * @hooked thim_portfolio_taxonomy_archive_header - 10
 */
do_action( 'thim_portfolio_loop_header' );

if ( have_posts() ) {

	do_action( 'thim_portfolio_before_loop' );

	while ( have_posts() ) :
		the_post();

		tp_portfolio_get_template( 'content-portfolio' , ['price'=> '100']);

	endwhile;
	/**
	 * Hook: thim_portfolio_after_loop.
	 *
	 * @hooked portfolio_pagination - 10
	 */
	do_action( 'thim_portfolio_after_loop' );

} else {
	/**
	 * Hook: no_portfolio_found.
	 *
	 * @hooked thim_no_portfolio_found - 10
	 */
	do_action( 'thim_no_portfolio_found' );
}
echo '</div>';

do_action( 'thim_portfolio_after_archive_portfolio' );

do_action( 'thim_portfolio_layout_end' );

get_footer();
