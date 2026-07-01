<?php
/**
 * Custom functions
 */

use LearnPress\Models\CourseModel;

defined( 'ABSPATH' ) || exit();
define( 'LP_PMPRO_TEMPLATE', learn_press_template_path() . '/addons/paid-membership-pro/' );

/**
 * Get template file for addon
 *
 * @param      $name
 * @param null $args
 * @depreacted 4.0.9
 */
function learn_press_pmpro_get_template( $name, $args = null ) {
	_deprecated_function( __FUNCTION__, '4.0.9' );
	return;
	if ( file_exists( learn_press_locate_template( $name, 'learnpress-paid-membership-pro', LP_PMPRO_TEMPLATE ) ) ) {
		learn_press_get_template( $name, $args, 'learnpress-paid-membership-pro/', get_template_directory() . '/' . LP_PMPRO_TEMPLATE );
	} else {
		learn_press_get_template( $name, $args, LP_PMPRO_TEMPLATE, LP_ADDON_PMPRO_PATH . '/templates/' );
	}
}

function learn_press_pmpro_locate_template( $name ) {
	// Look in folder learnpress-paid-membership-pro in the theme first
	$file = learn_press_locate_template( $name, 'learnpress-paid-membership-pro', learn_press_template_path() . '/addons/paid-membership-pro/' );
	// If template does not exists then look in learnpress/addons/paid-membership-pro in the theme
	if ( ! file_exists( $file ) ) {
		$file = learn_press_locate_template( $name, learn_press_template_path() . '/addons/paid-membership-pro/', LP_ADDON_PMPRO_PATH . '/templates/' );
	}

	return $file;
}

function lp_pmpro_query_course_by_level( $level_id ) {
	global $learn_press_pmpro_cache;

	$level_id = intval( $level_id );

	if ( ! empty( $learn_press_pmpro_cache[ 'query_level_' . $level_id ] ) ) {
		return $learn_press_pmpro_cache[ 'query_level_' . $level_id ];
	}

	$post_type = LP_COURSE_CPT;
	$args      = array(
		'post_type'      => array( $post_type ),
		'post_status'    => array( 'publish' ),
		'posts_per_page' => - 1,
		'meta_query'     => array(
			array(
				'key'   => '_lp_pmpro_levels',
				'value' => $level_id,
			),
		),
	);

	$query = new WP_Query( $args );
	$learn_press_pmpro_cache[ 'query_level_' . $level_id ] = $query;

	return $query;
}

function lp_pmpro_get_all_levels() {
	$pmpro_levels = wp_cache_get( 'pmp-levels', 'learn-press' );

	if ( false === $pmpro_levels ) {
		$pmpro_levels = pmpro_getAllLevels( false, true );
		wp_cache_set( 'pmp-levels', $pmpro_levels, 'learn-press' );
	}

	$pmpro_levels = apply_filters( 'lp_pmpro_levels_array', $pmpro_levels );

	return $pmpro_levels;
}

function lp_pmpro_list_courses( $levels = null ) {
	global $current_user;

	$list_courses = array();

	if ( ! $levels ) {
		$levels = lp_pmpro_get_all_levels();
	}

	foreach ( $levels as $index => $level ) {
		$the_query = lp_pmpro_query_course_by_level( $level->id );

		if ( ! empty( $the_query->posts ) ) {
			foreach ( $the_query->posts as $key => $course ) {
				$course_id                          = $course->ID;
				$list_courses[ $course_id ]['id']   = $course_id;
				$list_courses[ $course_id ]['link'] = '<a href="' . get_the_permalink( $course_id ) . '" >' . get_the_title( $course_id ) . '</a>';

				if ( empty( $list_courses[ $course_id ]['level'] ) ) {
					$list_courses[ $course_id ]['level'] = array();
				}

				if ( ! in_array( $level->id, $list_courses[ $course_id ]['level'] ) ) {
					$list_courses[ $course_id ]['level'][] = $level->id;
				}
			}
		}
	}
	$list_courses = apply_filters( 'learn_press_pmpro_list_courses', $list_courses, $current_user, $levels );

	return $list_courses;
}

/**
 * get Course by Memberships level id
 *
 * @param int $level_id
 *
 * @return array object
 * @global type $wpdb
 */
function lp_pmpro_get_course_by_level_id( $level_id ) {
	global $wpdb;
	$sql  = $wpdb->prepare(
		"SELECT
			p.ID, CONCAT(p.ID,' - ', p.post_title) AS `title`
		FROM
			{$wpdb->posts} AS p
				INNER JOIN
			{$wpdb->postmeta} AS pm ON (p.ID = pm.post_id)
		WHERE
			1 = 1
				AND ((pm.meta_key = '_lp_pmpro_levels'
				AND pm.meta_value = %s))
				AND p.post_type = 'lp_course'
				AND ((p.post_status = 'publish'))
		GROUP BY p.ID
		ORDER BY p.post_date DESC",
		$level_id
	);
	$rows = $wpdb->get_results( $sql, OBJECT_K );

	return $rows;
}

function learn_press_pmpro_getLevelCost( $level, $user_id ) {
	$membership_values = pmpro_getMembershipLevelForUser( $user_id );
	$cost              = 0;

	if ( ! empty( $membership_values ) ) {
		$membership_values->original_initial_payment = $membership_values->initial_payment;
		$membership_values->initial_payment          = $membership_values->billing_amount;
	}

	if ( empty( $membership_values ) || pmpro_isLevelFree( $membership_values ) ) {
		if ( ! empty( $membership_values->original_initial_payment ) && $membership_values->original_initial_payment > 0 ) {
			$cost = $membership_values->original_initial_payment;
		} else {
			$cost = 0;
		}
	} else {
		$cost  = pmpro_getLevelCost( $level, false, true );
		$match = array();
		preg_match( '/' . $level->initial_payment . '/i', $cost, $match );

		if ( $match && is_array( $match ) && ! empty( $match ) ) {
			$cost = $level->initial_payment;
		}
	}

	return $cost;
}
