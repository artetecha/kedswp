<?php
/**
 * @package thimpress
 * @version 2.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Hook: thim_portfolio_before_loop_item_title.
 * @hooked thim_portfolio_template_item_start - 10
 * @hooked thim_portfolio_template_loop_thumbnail - 20
 */
do_action( 'thim_portfolio_before_loop_item' );

/**
 * Hook: thim_portfolio_loop_item_title.
 *
 * @hooked thim_portfolio_template_loop_title - 10
 * @hooked thim_portfolio_template_loop_category - 10
 */
do_action( 'thim_portfolio_loop_item_title' );

/**
 * Hook: thim_portfolio_after_loop_item
 * @hook thim_portfolio_template_item_end - 50
 */
do_action( 'thim_portfolio_after_loop_item' );
