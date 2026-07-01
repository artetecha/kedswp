<?php
/**
 * The template for displaying single package.
 *
 * @version 4.0.0
 */

use LearnPress\Upsell\Package\Core_Functions;

defined( 'ABSPATH' ) || exit;

if ( ! wp_is_block_theme() ) {
	get_header( 'package' );
}

do_action( 'lp/upsell/layout/single-package' );

if ( ! wp_is_block_theme() ) {
	get_footer( 'package' );
}
