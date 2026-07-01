<?php

/**
 * The Template for displaying all archive products.
 *
 * Override this template by copying it to yourtheme/tp-event/templates/archive-event.php
 *
 * @author        ThimPress
 * @package       tp-event/template
 * @version       1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
get_header();

/**
 * thim_wrapper_loop_start hook
 *
 * @hooked thim_wrapper_loop_end - 1
 * @hooked thim_wapper_page_title - 5
 * @hooked thim_wrapper_loop_start - 30
 */

do_action( 'thim_wrapper_loop_start' );

$default_tab       = array( 'happening', 'upcoming', 'expired' );
$default_tab_title = array(
	'happening' => esc_html__( 'Happening', 'eduma' ),
	'upcoming'  => esc_html__( 'Upcoming', 'eduma' ),
	'expired'   => esc_html__( 'Expired', 'eduma' ),
);
$output_tab        = array();

// Normalize and sanitize customization values
$customize_order_tab = (array) get_theme_mod( 'thim_event_change_order_tab', array() );
$tab_style           = get_theme_mod( 'thim_tab_event_style' );
$tab_style           = $tab_style ? ' ' . esc_attr( $tab_style ) : '';
$tab_layout          = 'grid' === get_theme_mod( 'thim_tab_event_layout', '' ) ? ' style-grid' : '';

if ( empty( $customize_order_tab ) ) {
	// set default value for the first time
	$customize_order_tab = $default_tab;
}

foreach ( (array) $customize_order_tab as $tab_key ) {
	if ( isset( $default_tab_title[ $tab_key ] ) ) {
		$output_tab[ $tab_key ] = $default_tab_title[ $tab_key ];
	}
}

$paged                = max( 1, get_query_var( 'paged' ) ? intval( get_query_var( 'paged' ) ) : 1 );
$posts_per_page       = absint( get_theme_mod( 'thim_event_limit_post', 6 ) );
$current_event_status = isset( $_GET['event_status'] ) ? sanitize_text_field( wp_unslash( $_GET['event_status'] ) ) : '';
?>

	<div class="list-tab-event">
		<ul class="nav nav-tabs<?php echo esc_attr( $tab_style ); ?>">
			<?php
			$first_tab = true;
			foreach ( $output_tab as $k => $v ) {
				$is_active = ( $current_event_status && $current_event_status === $k ) || ( ! $current_event_status && $first_tab );

				if ( $is_active ) {
					printf( '<li class="active"><a href="#tab-%1$s" data-toggle="tab">%2$s</a></li>', esc_attr( $k ), esc_html( $v ) );
				} else {
					printf( '<li><a href="#tab-%1$s" data-toggle="tab">%2$s</a></li>', esc_attr( $k ), esc_html( $v ) );
				}

				if ( $first_tab ) {
					$first_tab = false;
				}
			}
			?>
		</ul>

		<div class="tab-content thim-list-event base">
			<?php
			$first_tab_content = true;

			foreach ( $output_tab as $type => $title ) :
				$is_active_tab  = ( $current_event_status && $current_event_status === $type ) || ( ! $current_event_status && $first_tab_content );
				$tab_paged      = $is_active_tab ? $paged : 1;
				$tab_query_args = array(
					'post_type'           => 'tp_event',
					'posts_per_page'      => intval( $posts_per_page ),
					'paged'               => $tab_paged,
					'ignore_sticky_posts' => true,
					'no_found_rows'       => false,
					'post_status'         => 'publish',
					'meta_query'          => array(
						array(
							'key'     => 'tp_event_status',
							'value'   => $type,
							'compare' => '=',
						),
					),
				);

				$tab_events = new WP_Query( $tab_query_args );

				// Output tab pane
				if ( $is_active_tab ) {
					printf( '<div role="tabpanel" class="tab-pane fade active in%1$s" id="tab-%2$s" data-status="%3$s">', esc_attr( $tab_layout ), esc_attr( $type ), esc_attr( $type ) );
				} else {
					printf( '<div role="tabpanel" class="tab-pane fade%1$s" id="tab-%2$s" data-status="%3$s">', esc_attr( $tab_layout ), esc_attr( $type ), esc_attr( $type ) );
				}

				if ( $first_tab_content ) {
					$first_tab_content = false;
				}
				echo '<div class="tab-content-inner">';
				if ( $tab_events->have_posts() ) {
					while ( $tab_events->have_posts() ) {
						$tab_events->the_post();
						get_template_part( 'wp-events-manager/content', 'event' );
					}
				} else {
					echo '<p class="no-events-found">' . esc_html__( 'No events found.', 'eduma' ) . '</p>';
				}
				echo '</div>';
				if ( $tab_events->max_num_pages > 1 ) {
					echo '<div class="pagination-event" data-max-pages="' . esc_attr( $tab_events->max_num_pages ) . '">';
					$big      = 999999999;
					$base_url = get_pagenum_link( $big );
					$base_url = remove_query_arg( 'paged', $base_url );
					$base_url = preg_replace( '/\/page\/\d+\/?/', '/', $base_url );
					$base_url = strtok( $base_url, '?' );

					$pagination_args = array(
						'base'         => str_replace( $big, '%#%', esc_url( $base_url ) ) . '%_%',
						'format'       => '?paged=%#%',
						'current'      => max( 1, $tab_paged ),
						'total'        => $tab_events->max_num_pages,
						'prev_text'    => '<i class="fa fa-angle-left"></i>',
						'next_text'    => '<i class="fa fa-angle-right"></i>',
						'type'         => 'plain',
						'add_args'     => array( 'event_status' => $type ),
						'add_fragment' => '',
					);
					if ( get_option( 'permalink_structure' ) ) {
						$pagination_args['format'] = 'page/%#%/';
						$pagination_args['base']   = trailingslashit( $base_url ) . '%_%';
					}

					echo paginate_links( $pagination_args );
					echo '</div>';
				}

				wp_reset_postdata();
				echo '</div>';

			endforeach;
			// do_action('tp_event_after_event_loop');
			?>
		</div>
	</div>

<?php

/**
 * thim_wrapper_loop_end hook
 *
 * @hooked thim_wrapper_loop_end - 10
 * @hooked thim_wrapper_div_close - 30
 */
do_action( 'thim_wrapper_loop_end' );

get_footer(); ?>
