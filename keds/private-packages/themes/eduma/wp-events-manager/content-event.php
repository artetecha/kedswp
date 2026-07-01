<?php
defined( 'ABSPATH' ) || exit;

// Normalize theme mod values and cache frequently used options
$display_year = get_theme_mod( 'thim_event_display_year', false );
$view_detail  = get_theme_mod( 'thim_event_button_view_detail_event', false );
$countdown    = get_theme_mod( 'thim_event_countdown_archive_event', false );
$time_format  = get_option( 'time_format' );
$month_show   = wpems_get_time( 'M' );
if ( $display_year ) {
	$month_show .= ', ' . wpems_get_time( 'Y' );
}

$archive_event_layout = get_theme_mod( 'thim_tab_event_layout', '' );
?>
<div <?php post_class( 'item-event' ); ?>>
	<?php echo 'grid' === $archive_event_layout ? '<div class="event-top">' : ''; ?>
	<div class="time-from <?php echo wpems_event_location() ? ' has-location' : ''; ?>">
		<?php do_action( 'thim_before_event_time' ); ?>
		<div class="date">
			<?php echo esc_html( wpems_get_time( 'd' ) ); ?>
		</div>
		<div class="month">
			<?php echo esc_html( $month_show ); ?>
		</div>
		<?php do_action( 'thim_after_event_time' ); ?>
	</div>
	<?php
	if ( has_post_thumbnail() ) {
		echo '<div class="image">';

		if ( 'grid' === $archive_event_layout ) {
			echo wp_kses_post( thim_get_feature_image( get_post_thumbnail_id(), 'full', apply_filters( 'thim_event_thumbnail_width', 460 ), apply_filters( 'thim_event_thumbnail_height', 300 ) ) );
		} else {
			echo wp_kses_post( thim_get_feature_image( get_post_thumbnail_id(), 'full', apply_filters( 'thim_event_thumbnail_width', 450 ), apply_filters( 'thim_event_thumbnail_height', 233 ) ) );
		}
		if ( $countdown ) {
			do_action( 'tp_event_loop_event_countdown' );
		}
		echo '</div>';
	}
	echo 'grid' === $archive_event_layout ? '</div>' : '';
	?>
	<div class="event-wrapper">
		<?php the_title( sprintf( '<h5 class="title"><a href="%s">', esc_url( get_permalink() ) ), '</a></h5>' ); ?>

		<div class="meta ">
			<div class="time">
				<i class="edu-clock"></i>
				<?php echo esc_html( wpems_event_start( $time_format ) ) . ' - ' . esc_html( wpems_event_end( $time_format ) ); ?>
			</div>
			<?php if ( wpems_event_location() ) : ?>
				<div class="location">
					<i class="edu-map-marker"></i>
					<?php echo esc_html( wpems_event_location() ); ?>
				</div>
			<?php endif; ?>
		</div>
		<div class="description">
			<?php echo wp_kses_post( thim_excerpt( 25 ) ); ?>
		</div>
		<?php if ( $view_detail ) : ?>
			<div class="view-detail">
				<a href="<?php echo esc_url( get_the_permalink() ); ?>">
					<?php esc_html_e( 'Get ticket', 'eduma' ); ?>
				</a>
			</div>
		<?php endif; ?>
	</div>

</div>
