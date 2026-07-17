<?php
/**
 * Child-theme shim.
 *
 * The parent theme's setup_theme() runs:
 *   require get_theme_file_path() . '/inc/core/block-styles.php';
 * and get_theme_file_path() resolves to the CHILD (stylesheet) directory
 * first, so with this child active the parent would fatally fail to find the
 * file. This shim provides it and loads the parent's real implementation so
 * its block-style registration still runs.
 *
 * @package online-learning-child
 */

defined( 'ABSPATH' ) || exit;

$online_learning_parent_file = get_template_directory() . '/inc/core/block-styles.php';
if ( is_readable( $online_learning_parent_file ) ) {
	require $online_learning_parent_file;
}
