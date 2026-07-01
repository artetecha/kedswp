<?php
/**
 * The template for displaying archive package.
 *
 * @version 1.0.0
 * @since 1.0.0
 */

use LearnPress\Upsell\Package\Core_Functions;

defined( 'ABSPATH' ) || exit;

if ( ! wp_is_block_theme() ) {
	get_header( 'package' );
}

do_action( 'lp/upsell/layout/archive-package' );

if ( ! wp_is_block_theme() ) {
	get_footer( 'package' );
}
