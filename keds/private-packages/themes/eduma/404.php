<?php

/**
 * The template for displaying 404 pages (not found).
 *
 * @package thim
 */

get_header();

/**
 * thim_wrapper_loop_start hook
 *
 * @hooked thim_wrapper_div_open - 1
 * @hooked thim_wapper_page_title - 5
 * @hooked thim_wrapper_loop_start - 10
 */

do_action( 'thim_wrapper_loop_start' );

$title_404        = get_theme_mod( 'thim_single_404_title', '' );
$content_404      = get_theme_mod( 'thim_single_404_content', '' );
$button_404       = (bool) get_theme_mod( 'show_back_home_404', false );
$button_title_404 = get_theme_mod( 'thim_single_404_button_title' );
if ( empty( $button_title_404 ) ) {
	$button_title_404 = esc_html__( 'Return to Home', 'eduma' );
}
$home_url = esc_url( get_home_url() );
$left_img = get_theme_mod( 'thim_single_404_left' );
?>
<section class="error-404 not-found">
	<div class="page-404-content">
		<div class="404-left">
			<?php
			if ( $left_img ) {
				echo '<img src="' . esc_url( $left_img ) . '" alt="' . esc_attr__( '404 page', 'eduma' ) . '" />';
			} else {
				echo '<img src="' . esc_url( get_template_directory_uri() . '/images/image-404.jpg' ) . '" alt="' . esc_attr__( '404 page', 'eduma' ) . '" />';
			}
			?>
		</div>
		<div class="404-right text-left">
			<?php
			if ( ! empty( $title_404 ) ) {
				echo '<h2 class="title-404">' . wp_kses_post(
					str_replace(
						array( '{{', '}}' ),
						array( '<span class="thim-color">', '</span>' ),
						wp_kses_post( $title_404 )
					)
				) . '</h2>';
			} else {
				?>
				<h2><?php echo esc_html__( '404 ', 'eduma' ); ?><span
						class="thim-color"><?php echo esc_html__( 'Error!', 'eduma' ); ?></span></h2>
				<?php
			}
			if ( ! empty( $content_404 ) ) {
				echo '<div class="thim-404-content">' . wp_kses_post( $content_404 ) . '</div>';
			} else {
				?>
				<p><?php echo esc_html__( 'Sorry, we can\'t find the page you are looking for. Please go to ', 'eduma' ); ?>
					<a href="<?php echo $home_url; ?>"
						class="thim-color"><?php echo esc_html__( 'Home Page', 'eduma' ); ?></a>
				</p>
				<?php
			}
			if ( $button_404 ) {
				?>
				<a href="<?php echo $home_url; ?>"
					class="button return-home"><?php echo esc_html( $button_title_404 ); ?></a>
			<?php } ?>
		</div>
	</div>
	<!-- .page-content -->
</section>
<?php
/**
 * thim_wrapper_loop_end hook
 *
 * @hooked thim_wrapper_loop_end - 10
 * @hooked thim_wrapper_div_close - 30
 */
do_action( 'thim_wrapper_loop_end' );

get_footer(); ?>
