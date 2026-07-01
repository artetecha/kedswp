<?php
$panel_list      = $instance['panel'] ? $instance['panel'] : '';
$pagination      = ( ! empty( $instance['show_pagination'] ) && $instance['show_pagination'] !== 'no' ) ? 1 : 0;
$show_navigation = isset( $instance['show_navigation'] ) ? $instance['show_navigation'] : 1;
$autoplay        = isset( $instance['auto_play'] ) ? $instance['auto_play'] : 0;
$html            = '<div class="thim-instructors-new">';
$heading         = ! ( empty( $instance['title'] ) ) ? $instance['title'] : esc_html__( 'Popular Courses', 'eduma' );

if ( ! empty( $panel_list ) ) {
	// Batch query: 1 query for all instructors instead of N separate queries.
	$instructor_ids = array();
	foreach ( $panel_list as $panel ) {
		if ( ! empty( $panel['panel_id'] ) ) {
			$instructor_ids[] = (int) $panel['panel_id'];
		}
	}
	$courses_by_author = array();
	if ( ! empty( $instructor_ids ) ) {
		$batch = new WP_Query(
			array(
				'post_type'           => 'lp_course',
				'author__in'          => $instructor_ids,
				'post_status'         => 'publish',
				'ignore_sticky_posts' => true,
				'posts_per_page'      => count( $instructor_ids ) * 2,
				'no_found_rows'       => true,
			)
		);
		foreach ( $batch->posts as $course ) {
			$author_id = (int) $course->post_author;
			if ( count( $courses_by_author[ $author_id ] ?? array() ) < 2 ) {
				$courses_by_author[ $author_id ][] = $course;
			}
		}
		wp_reset_postdata();
	}

	$html .= '<div class="thim-carousel-wrapper" data-visible="1" data-navigation="' . $show_navigation . '" data-itemtablet="1" data-pagination="' . $pagination . '" data-autoplay="' . esc_attr( $autoplay ) . '">';
	foreach ( $panel_list as $key => $panel ) {
		if ( ! empty( $panel['panel_id'] ) ) {
			$courses_posts = $courses_by_author[ (int) $panel['panel_id'] ] ?? array();

			$img_id = is_array( $panel['panel_img'] ) ? $panel['panel_img']['id'] : $panel['panel_img'];

			$html .= '<div class="instructor-item">';
			$html .= '<div class="instructor-image">';
			$html .= thim_get_feature_image( $img_id );
			$html .= '</div>';
			$html .= '<div class="instructor-info">';
			$html .= '<h4><a href="' . esc_url( learn_press_user_profile_link( $panel['panel_id'] ) ) . '">' . get_the_author_meta( 'display_name', $panel['panel_id'] ) . '</a></h4>';
			$html .= '<div class="des">' . get_the_author_meta( 'description', $panel['panel_id'] ) . '</div>';
			if ( ! empty( $courses_posts ) ) {
				$html .= '<div class="list-courses">';
				$html .= '<h3>' . $heading . '</h3>';
				foreach ( $courses_posts as $course ) {
					$html .= '<div class="course-instructor">';
					$html .= thim_get_feature_image( get_post_thumbnail_id( $course->ID ), 'full', '100', '80' );
					$html .= '<h5><a href="' . esc_url( get_permalink( $course->ID ) ) . '">' . get_the_title( $course->ID ) . '</a></h5>';
					$html .= '</div>';
				}
				$html .= '</div>';
			}
			$html .= '</div>';
			$html .= '</div>';
		}
	}
	$html .= '</div>';
}
$html .= '</div>';

echo ent2ncr( $html );
