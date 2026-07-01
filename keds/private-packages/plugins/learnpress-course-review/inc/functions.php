<?php
/**
 * LearnPress Course Review Functions
 *
 * Define common functions for both front-end and back-end
 *
 * @author   ThimPress
 * @package  LearnPress/Course-Review/Functions
 * @version  3.0.2
 */

// Prevent loading this file directly
use LearnPress\CourseReview\CourseReviewCache;
use LearnPress\CourseReview\TemplateHooks\CourseRatingTemplate;
use LearnPress\CourseReview\TemplateHooks\TemplateHooks;

defined( 'ABSPATH' ) || exit;

/**
 * @param int $course_id
 * @param int $paged
 * @param int $per_page
 * @param boolean $force
 *
 * @tungnx: fix temporary, when have time will write, remove it.
 *
 * @return mixed
 */
function learn_press_get_course_review( $course_id, $paged = 1, $per_page = LP_ADDON_COURSE_REVIEW_PER_PAGE, $force = false ) {
	$results = array(
		'reviews'  => array(),
		'paged'    => $paged,
		'total'    => 0,
		'per_page' => $per_page,
	);

	try {
		global $wpdb;
		$per_page = absint( apply_filters( 'learn_press_course_reviews_per_page', $per_page ) );
		$paged    = absint( $paged );

		if ( $per_page == 0 ) {
			$per_page = 9999999;
		}

		if ( $paged == 0 ) {
			$paged = 1;
		}

		$start = ( $paged - 1 ) * $per_page;

		$query = $wpdb->prepare(
			"
			SELECT SQL_CALC_FOUND_ROWS u.user_email, u.display_name,
				c.comment_ID as comment_id, cm1.meta_value as title,
				c.comment_content as content, cm2.meta_value as rate,
				c.comment_date_gmt as comment_date_gmt
			FROM {$wpdb->posts} p
			INNER JOIN {$wpdb->comments} c ON p.ID = c.comment_post_ID
			INNER JOIN {$wpdb->users} u ON u.ID = c.user_id
			INNER JOIN {$wpdb->commentmeta} cm1 ON cm1.comment_id = c.comment_ID AND cm1.meta_key = %s
			INNER JOIN {$wpdb->commentmeta} cm2 ON cm2.comment_id = c.comment_ID AND cm2.meta_key = %s
			WHERE p.ID = %d AND c.comment_type = %s AND c.comment_approved = %d
			ORDER BY c.comment_date DESC
			LIMIT %d, %d
			",
			'_lpr_review_title',
			'_lpr_rating',
			$course_id,
			'review',
			1,
			$start,
			$per_page
		);

		$course_review = $wpdb->get_results( $query );

		if ( $course_review ) {
			$ratings            = LP_Addon_Course_Review_Preload::$addon->get_rating_of_course( $course_id );
			$results['reviews'] = $course_review;
			$results['total']   = $ratings['total'];
			$results['pages']   = ceil( $results['total'] / $per_page );
		}
	} catch ( Throwable $e ) {

	}

	return $results;
}

function _learn_press_get_ratings( $course_id ) {
	$ratings = [
		$course_id => LP_Addon_Course_Review_Preload::$addon->get_rating_of_course( $course_id ),
	];

	return $ratings;
}


/**
 * Get the rating info of a course
 *
 * @param $course_id
 * @param $field
 *
 * @return mixed
 */
function learn_press_get_course_rate( $course_id, $field = 'rated' ) {
	$ratings = _learn_press_get_ratings( $course_id );
	$rate    = ( $field && array_key_exists( $field, $ratings[ $course_id ] ) ) ? $ratings[ $course_id ][ $field ] : $ratings[ $course_id ];

	return apply_filters( 'learn_press_get_course_rate', $rate );
}

function learn_press_get_course_rate_total( $course_id, $field = 'total' ) {
	$ratings = _learn_press_get_ratings( $course_id );

	$total = $ratings[ $course_id ]['total'] ?? 0;

	return apply_filters( 'learn_press_get_course_rate_total', $total );
}

/**
 * Get the rating user has posted for a course.
 *
 * @param int $course_id
 * @param int $user_id
 * @param bool $force
 *
 * @return mixed
 */
function learn_press_get_user_rate( int $course_id = 0, int $user_id = 0, $force = false ) {
	if ( ! $user_id ) {
		$user_id = get_current_user_id();
	}
	if ( ! $course_id ) {
		$course_id = get_the_ID();
	}

	global $wpdb;
	$query = $wpdb->prepare(
		"
		SELECT *
		FROM {$wpdb->posts} p
		INNER JOIN {$wpdb->comments} c ON c.comment_post_ID = p.ID
		WHERE c.comment_post_ID = %d
		AND c.user_id = %d
		AND c.comment_type = %s
		",
		$course_id,
		$user_id,
		'review'
	);

	$comment = $wpdb->get_row( $query );
	if ( $comment ) {
		$comment->comment_title = get_comment_meta( $comment->comment_ID, '_lpr_review_title', true );
		$comment->rating        = get_comment_meta( $comment->comment_ID, '_lpr_rating', true );
	}

	return $comment;
}

/**
 * Add new review for a course
 *
 * @param array
 *
 * @return int
 */
function learn_press_add_course_review( $args = array() ) {
	$args        = wp_parse_args(
		$args,
		array(
			'title'     => '',
			'content'   => '',
			'rate'      => '',
			'user_id'   => 0,
			'course_id' => 0,
			'force'     => 0,
		)
	);
	$user_id     = $args['user_id'];
	$course_id   = $args['course_id'];
	$user_review = learn_press_get_user_rate( $course_id, $user_id, $args['force'] );
	$comment_id  = 0;

	if ( ! $user_review ) {
		$user       = get_user_by( 'id', $user_id );
		$comment_id = wp_new_comment(
			array(
				'comment_post_ID'      => $course_id,
				'comment_author'       => $user->display_name,
				'comment_author_email' => $user->user_email,
				'comment_author_url'   => '',
				'comment_content'      => $args['content'],
				'comment_parent'       => 0,
				'user_id'              => $user->ID,
				'comment_approved'     => 1,
				'comment_type'         => 'review', // let filter to not display it as comments
			),
			true
		);
	}

	if ( ! $comment_id instanceof WP_Error ) {
		add_comment_meta( $comment_id, '_lpr_rating', $args['rate'] );
		add_comment_meta( $comment_id, '_lpr_review_title', $args['title'] );

		// Clear cache
		$CourseReviewCache = new CourseReviewCache( true );
		$CourseReviewCache->clean_rating( $course_id, $user_id );
	}

	return $comment_id;
}

/**
 * @deprecated 4.0.6
 * Check still use on themes: Coaching, Elearning, Ivy
 */
function learn_press_course_review_template( $name, $args = null ) {
	learn_press_get_template( $name, $args, learn_press_template_path() . '/addons/course-review/', LP_ADDON_COURSE_REVIEW_TMPL );
}

/**
 * @return void
 * @deprecated 4.1.6
 * Remove on some themes before set _deprecated_function
 */
function learn_press_course_meta_primary_review() {
	//_deprecated_function( __FUNCTION__, '4.1.6', 'TemplateHooks::instance()->meta_single_course_classic_layout()' );
	TemplateHooks::instance()->meta_single_course_classic_layout();
}
