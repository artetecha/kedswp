<?php
if ( thim_is_new_learnpress( '4.1.6' ) ) {

	/**
	 * Thim custom params to api get course page archive.
	 */

	if ( ! function_exists( 'thim_get_courses_is_free' ) ) {
		/**
		 * Get list courses is free
		 *
		 * @param LP_Course_Filter $filter
		 *
		 * @return LP_Course_Filter
		 * @since 4.1.5
		 * @author tungnx
		 * @version 1.0.0
		 */
		function thim_get_courses_is_free( LP_Course_Filter $filter ): LP_Course_Filter {
			global $wpdb;
			$filter->only_fields = array( 'ID' );
			$filter->join[]      = "INNER JOIN {$wpdb->prefix}postmeta AS pm ON p.ID = pm.post_id";
			$filter->where[]     = $wpdb->prepare( 'AND pm.meta_key = %s AND pm.meta_value = %d', '_lp_price', 0 );
			$filter->order_by    = 'CAST( pm.meta_value AS UNSIGNED )';

			return $filter;
		}
	}

	if ( ! function_exists( 'thim_get_courses_is_paid' ) ) {
		/**
		 * Get list courses is paid
		 *
		 * @param LP_Course_Filter $filter
		 *
		 * @return LP_Course_Filter
		 * @since 4.1.5
		 * @version 1.0.0
		 */
		function thim_get_courses_is_paid( LP_Course_Filter $filter ): LP_Course_Filter {
			global $wpdb;
			$filter->only_fields = array( 'ID' );
			$filter->join[]      = "INNER JOIN {$wpdb->prefix}postmeta AS pm ON p.ID = pm.post_id";
			$filter->where[]     = $wpdb->prepare( 'AND pm.meta_key = %s AND pm.meta_value > %d', '_lp_price', 0 );
			$filter->order_by    = 'CAST( pm.meta_value AS UNSIGNED )';

			return $filter;
		}
	}

	if ( ! function_exists( 'thim_get_courses_by_title' ) ) {
		/**
		 * Get list courses by title ASC
		 *
		 * @param LP_Course_Filter $filter
		 *
		 * @return LP_Course_Filter
		 * @since 4.1.5
		 * @version 1.0.0
		 */
		function thim_get_courses_by_title( LP_Course_Filter $filter ): LP_Course_Filter {
			$filter->order = 'ASC';

			return $filter;
		}
	}

	if ( ! function_exists( 'thim_filter_get_courses_by_api' ) ) {
		function thim_filter_get_courses_by_api( $filter, $request ) {
			if ( ! empty( $request['sort_by'] ) ) {
				switch ( $request['sort_by'] ) {
					case 'on_free':
						$filter->sort_by[] = 'on_free';
						break;
					case 'on_paid':
						$filter->sort_by[] = 'on_paid';
						break;
					default:
						return $filter;
				}
			}

			if ( ! empty( $request['order_by'] ) ) {
				switch ( $request['order_by'] ) {
					case 'post_title':
						$filter->order_by = 'post_title';
						break;
					case 'popular':
						$filter->order_by = 'popular';
						break;
					case 'post_date':
						$filter->order_by = 'post_date';
						break;
					default:
						return $filter;
				}
			}

			return $filter;
		}

		add_filter( 'lp/api/courses/filter', 'thim_filter_get_courses_by_api', 10, 2 );
	}

	/**
	 * Thim custom filter sort_by to api get course is free page archive.
	 */
	if ( ! function_exists( 'thim_filter_get_courses_sort_by_on_free' ) ) {
		function thim_filter_get_courses_sort_by_on_free( $filter ) {
			$filter = thim_get_courses_is_free( $filter );

			return $filter;
		}

		add_filter( 'lp/courses/filter/sort_by/on_free', 'thim_filter_get_courses_sort_by_on_free', 10, 1 );
	}

	/**
	 * Thim custom filter sort_by to api get course is paid page archive.
	 */
	if ( ! function_exists( 'thim_filter_get_courses_sort_by_on_paid' ) ) {
		function thim_filter_get_courses_sort_by_on_paid( $filter ) {
			$filter = thim_get_courses_is_paid( $filter );

			return $filter;
		}

		add_filter( 'lp/courses/filter/sort_by/on_paid', 'thim_filter_get_courses_sort_by_on_paid', 10, 1 );
	}

	/**
	 * Thim custom filter order_by to api get course alphabetical page archive.
	 */
	if ( ! function_exists( 'thim_filter_get_courses_order_by_alphabetical' ) ) {
		function thim_filter_get_courses_order_by_alphabetical( $filter ) {
			$filter = thim_get_courses_by_title( $filter );

			return $filter;
		}

		add_filter( 'lp/courses/filter/order_by/post_title', 'thim_filter_get_courses_order_by_alphabetical', 10, 1 );
	}
	add_filter( 'lp/page/courses/query/lazy_load', '__return_true' );
}
