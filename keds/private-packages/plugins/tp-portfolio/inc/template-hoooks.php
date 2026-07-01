<?php

defined( 'ABSPATH' ) || exit;

/*
 * Hook for Archive Portfolio
 */
add_action( 'thim_portfolio_loop_header', 'thim_portfolio_taxonomy_archive_header', 10 );

add_action( 'thim_portfolio_before_loop', 'thim_portfolio_start_loop', 10 );
add_action( 'thim_portfolio_after_loop', 'thim_portfolio_end_loop', 10 );
add_action( 'thim_portfolio_after_loop', 'portfolio_pagination', 20 );
add_action( 'thim_no_portfolio_found', 'thim_no_portfolio_found', 10 );

/*
 * Hook for Content Portfolio
 */
add_action( 'thim_portfolio_before_loop_item', 'thim_portfolio_template_item_start', 10 );
add_action( 'thim_portfolio_before_loop_item', 'thim_portfolio_template_loop_thumbnail', 20 );
add_action( 'thim_portfolio_after_loop_item', 'thim_portfolio_template_item_end', 50 );

add_action( 'thim_portfolio_loop_item_title', 'thim_portfolio_template_title_start', 5 );
add_action( 'thim_portfolio_loop_item_title', 'thim_portfolio_template_loop_meta', 10 );
add_action( 'thim_portfolio_loop_item_title', 'thim_portfolio_template_loop_title', 15 );
add_action( 'thim_portfolio_loop_item_title', 'thim_portfolio_template_loop_read_more', 20 );
add_action( 'thim_portfolio_loop_item_title', 'thim_portfolio_template_title_end', 90 );
/*
 * Hook for single
 */
add_action( 'thim_portfolio_before_single_content', 'thim_portfolio_template_loop_thumbnail', 10 );

add_action( 'thim_portfolio_single_content', 'thim_portfolio_template_single_title', 10 );
add_action( 'thim_portfolio_single_content', 'thim_portfolio_template_single_meta', 15 );
add_action( 'thim_portfolio_single_content', 'thim_portfolio_template_single_content', 20 );
add_action( 'thim_portfolio_single_content', 'thim_portfolio_template_single_content_pdf', 20 );
add_action( 'thim_portfolio_single_content', 'thim_portfolio_template_single_meta_footer', 30 );
add_action( 'thim_portfolio_single_content', 'thim_portfolio_template_single_comment', 60 );

add_action( 'thim_portfolio_after_single_content', 'thim_portfolio_output_related', 10 );
