<?php
/**
 * The Template for displaying all single posts.
 *
 * @package    thimpress
 */
get_header();

do_action( 'thim_portfolio_layout_start' );

do_action( 'thim_portfolio_before_single_portfolio' );

while ( have_posts() ) :
	the_post();

	$type = get_post_meta( get_the_ID(), 'selectPortfolio', true );

	if ( $type == 'portfolio_type_page_builder' ) {
		echo '<div class="portfolio-content-builder">';
		the_content();
		echo '</div>';
	} else {
		tp_portfolio_get_template( 'content-single-portfolio' );
	}

endwhile; // end of the loop.

do_action( 'thim_portfolio_after_single_portfolio' );

do_action( 'thim_portfolio_layout_end' );

get_footer();
