<?php

$link           = '';
$regency        = '';
$link_to_single = ! empty( $instance['link_to_single'] ) ? true : false;
$limit          = ( $instance['limit'] && '' <> $instance['limit'] ) ? (int) $instance['limit'] : 10;
$item_visible   = ( $instance['item_visible'] && '' <> $instance['item_visible'] ) ? (int) $instance['item_visible'] : 5;
$item_time      = ( $instance['pause_time'] && '' <> $instance['pause_time'] ) ? (int) $instance['pause_time'] : 5000;
$autoplay       = $instance['autoplay'] ? 1 : 0;
$mousewheel     = $instance['mousewheel'] ? 1 : 0;

$cache_key    = 'thim_testimonial_' . md5( serialize( $instance ) );
$cached_title = get_transient( $cache_key . '_title' );
$cached_html  = get_transient( $cache_key );

if ( false !== $cached_html ) {
	if ( $cached_title ) {
		echo ent2ncr( $args['before_title'] . $cached_title . $args['after_title'] );
	}
	echo ent2ncr( $cached_html );
	return;
}

$testomonial_args = array(
	'post_type'           => 'testimonials',
	'posts_per_page'      => $limit,
	'ignore_sticky_posts' => true,
);

$testimonial = new WP_Query( $testomonial_args );

if ( $testimonial->have_posts() ) {
	if ( $instance['title'] ) {
		echo ent2ncr( $args['before_title'] . $instance['title'] . $args['after_title'] );
		set_transient( $cache_key . '_title', $instance['title'], HOUR_IN_SECONDS );
	}
	$html = '<div class="thim-testimonial-slider" data-time="' . $item_time . '" data-visible="' . $item_visible . '" data-auto="' . $autoplay . '" data-mousewheel="' . $mousewheel . '">';
	while ( $testimonial->have_posts() ) :
		$testimonial->the_post();
		$link    = get_post_meta( get_the_ID(), 'website_url', true );
		$regency = get_post_meta( get_the_ID(), 'regency', true );

		$html .= '<div class="item">';
		if ( has_post_thumbnail() ) {
			$html .= '<div class="image">';
			$html .= thim_get_feature_image( get_post_thumbnail_id(), 'full', apply_filters( 'thim_testimonial_thumbnail_width', 100 ), apply_filters( 'thim_testimonial_thumbnail_height', 100 ), '', '', 1 );
			$html .= '</div>';
		}
		$html .= '<div class="content">';
		if ( $link_to_single ) {
			$html .= '<h3 class="title"><a href="' . esc_url( get_the_permalink() ) . '">' . get_the_title() . '</a></h3>';
		} elseif ( $link <> '' ) {
			$html .= '<h3 class="title"><a href="' . esc_url( $link ) . '">' . get_the_title() . '</a></h3>';
		} else {
			$html .= '<h3 class="title">' . get_the_title() . '</h3>';
		}
		$html .= '<div class="regency">' . esc_html( $regency ) . '</div>';
		$html .= '<div class="description">' . apply_filters( 'the_content', get_the_content() ) . '</div>';
		$html .= '</div></div>';

	endwhile;
	$html .= '</div>';
	wp_reset_postdata();
	set_transient( $cache_key, $html, HOUR_IN_SECONDS );
	echo ent2ncr( $html );
}
