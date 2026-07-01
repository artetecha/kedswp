<?php
/**
 * Template for displaying content of archive courses page.
 *
 * @author  ThimPress
 * @package LearnPress/Templates
 * @version 4.0.2
 */

defined( 'ABSPATH' ) || exit;

/**
 * @since 4.0.0
 *
 * @see   LP_Template_General::template_header()
 */
do_action( 'learn-press/template-header' );

/**
 * thim_wrapper_loop_start hook
 *
 * @hooked thim_wrapper_loop_end - 1
 * @hooked thim_wapper_page_title - 5
 * @hooked thim_wrapper_loop_start - 30
 */

do_action( 'thim_wrapper_loop_start' );
/**
 * LP Hook
 */
do_action( 'learn-press/before-main-content' );
?>

	<div class="lp-content-area">

		<?php do_action( 'learn-press/list-courses/layout' ); ?>

	</div>

<?php
/**
 * LP Hook
 */
do_action( 'learn-press/after-main-content' );

/**
 * thim_wrapper_loop_end hook
 *
 * @hooked thim_wrapper_loop_end - 10
 * @hooked thim_wrapper_div_close - 30
 */
do_action( 'thim_wrapper_loop_end' );
/**
 * @since 4.0.0
 *
 * @see   LP_Template_General::template_footer()
 */
do_action( 'learn-press/template-footer' );
