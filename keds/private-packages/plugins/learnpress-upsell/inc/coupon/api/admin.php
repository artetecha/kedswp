<?php
namespace LearnPress\Upsell\Coupon\API;

use LearnPress\Upsell\Coupon\Core_Functions;

class Admin {

	protected static $instance = null;

	const NAMESPACE = 'learnpress-coupon/v1/admin';

	public function __construct() {
		add_action( 'rest_api_init', array( $this, 'register_rest_api' ) );
	}

	public function register_rest_api() {
		register_rest_route(
			self::NAMESPACE,
			'/coupons',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_coupons' ),
				'permission_callback' => array( $this, 'permission_callback' ),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/coupon/(?P<id>[\d]+)',
			array(
				array(
					'methods'             => 'GET',
					'callback'            => array( $this, 'get_coupon' ),
					'permission_callback' => array( $this, 'permission_callback' ),
				),
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'save_coupon' ),
					'permission_callback' => array( $this, 'permission_callback' ),
				),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/delete',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'delete_coupon' ),
				'permission_callback' => array( $this, 'permission_callback' ),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/get-packages',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_packages' ),
				'permission_callback' => array( $this, 'permission_callback' ),
			)
		);
	}

	public function permission_callback() {
		return current_user_can( 'manage_options' );
	}

	public function get_coupon( $request ) {
		$id = $request->get_param( 'id' );

		try {
			if ( ! $id ) {
				throw new Exception( __( 'Coupon not found', 'learnpress-upsell' ) );
			}

			$coupon = get_post( $id );

			if ( ! $coupon ) {
				throw new Exception( __( 'Coupon not found', 'learnpress-upsell' ) );
			}

			$data = array_merge( array(
				'id'          => $coupon->ID,
				'title'       => $coupon->post_title,
				'description' => $coupon->post_content,
				'status'      => $coupon->post_status,
				'publishDate' => $coupon->post_date ?? $coupon->post_date_gmt ?? '',
			), $this->get_coupon_data( $coupon->ID ) );

			return new \WP_REST_Response(
				array(
					'success' => true,
					'coupon' => $data,
				)
			);
		} catch ( \Throwable $th ) {
			return new \WP_REST_Response(
				array(
					'success' => false,
					'message' => $th->getMessage(),
				)
			);
		}
	}

	public function delete_coupon( $request ) {
		$id       = $request->get_param( 'id' );
		$is_trash = $request->get_param( 'trash' );

		if ( ! $id ) {
			return new \WP_Error( 'coupon_not_found', __( 'Coupon not found', 'learnpress-upsell' ), array( 'status' => 404 ) );
		}

		$coupon = get_post( $id );

		if ( ! $coupon ) {
			return new \WP_Error( 'coupon_not_found', __( 'Coupon not found', 'learnpress-upsell' ), array( 'status' => 404 ) );
		}

		if ( $is_trash ) {
			$deleted = wp_trash_post( $id );
		} else {
			$deleted = wp_delete_post( $id, true );
		}

		if ( ! $deleted ) {
			return new \WP_Error( 'coupon_not_deleted', __( 'Coupon not deleted', 'learnpress-upsell' ), array( 'status' => 500 ) );
		}

		return array(
			'success' => true,
			'message' => $is_trash ? __( 'Coupon moved to trash', 'learnpress-upsell' ) : __( 'Coupon deleted', 'learnpress-upsell' ),
		);
	}

	public function save_coupon( $request ) {

		try {
			$coupon_id = $request['id'];

			$is_insert = empty( $coupon_id ) ? true : false;

			$post_title = sanitize_text_field( $request['title'] ?? '' );

			$coupon_code = Core_Functions::instance()->format_coupon_code( $post_title );

			$coupon_id_from_code = Core_Functions::instance()->get_coupon_id_by_code( $coupon_code );

			if ( $coupon_id_from_code && $coupon_id_from_code != $coupon_id ) {
				throw new \Exception( __( 'Coupon code already exists', 'learnpress-upsell' ) );
			}

			if ( $is_insert ) {
				$argc = array(
					'post_date'     => current_time( 'mysql' ),
					'post_date_gmt' => current_time( 'mysql', 1 ),
					'post_type'     => LP_COUPON_CPT,
					'post_title'    => $post_title,
					'post_content'  => wp_unslash( $request['description'] ?? '' ),
					'post_status'   => ! empty( $request['status'] ) ? sanitize_text_field( $request['status'] ) : 'publish',
				);

				if ( ! empty( $request['publishDate'] ) ) {
					$argc['post_date']     = $request['publishDate'];
					$argc['post_date_gmt'] = get_gmt_from_date( $request['publishDate'] );
				}

				$coupon_id = wp_insert_post( $argc, true );

				if ( is_wp_error( $coupon_id ) ) {
					throw new \Exception( $coupon_id->get_error_message() );
				}

				$post = get_post( $coupon_id );
			} else {
				$post = get_post( $coupon_id );

				if ( ! $post || $post->post_type !== LP_COUPON_CPT ) {
					throw new \Exception( __( 'Coupon not found', 'learnpress-upsell' ) );
				}

				$argv = array(
					'ID'            => $coupon_id,
					'post_date'     => current_time( 'mysql' ),
					'post_date_gmt' => current_time( 'mysql', 1 ),
					'post_title'    => $post_title,
					'post_content'  => wp_unslash( $request['description'] ?? '' ),
					'post_status'   => ! empty( $request['status'] ) ? sanitize_text_field( $request['status'] ) : 'publish',
				);

				if ( ! empty( $request['publishDate'] ) ) {
					$argv['post_date']     = $request['publishDate'];
					$argv['post_date_gmt'] = get_gmt_from_date( $request['publishDate'] );
				}

				$coupon_id = wp_update_post( $argv, true );

				if ( is_wp_error( $coupon_id ) ) {
					throw new \Exception( $coupon_id->get_error_message() );
				}
			}

			if ( ! empty( $request['discountType'] ) ) {
				update_post_meta( $coupon_id, 'discount_type', sanitize_text_field( $request['discountType'] ) );
			}

			update_post_meta( $coupon_id, 'discount_amount', absint( $request['discountAmount'] ) );

			$discount_start_date = ! empty( $request['discountStartDate'] ) ? sanitize_text_field( $request['discountStartDate'] ) : '';
			update_post_meta( $coupon_id, 'discount_start_date', $discount_start_date );

			$discount_end_date = ! empty( $request['discountEndDate'] ) ? sanitize_text_field( $request['discountEndDate'] ) : '';
			update_post_meta( $coupon_id, 'discount_end_date', $discount_end_date );

			$usage_limit = ! empty( $request['limitPerCoupon'] ) ? absint( $request['limitPerCoupon'] ) : '';
			update_post_meta( $coupon_id, 'usage_limit', $usage_limit );

			$usage_limit_per_user = ! empty( $request['limitPerUser'] ) ? absint( $request['limitPerUser'] ) : '';
			update_post_meta( $coupon_id, 'usage_limit_per_user', $usage_limit_per_user );

			$include_packages = ! empty( $request['includePackages'] ) ? array_filter( array_map(
				function( $course ) {
					return absint( $course['id'] );
				},
				$request['includePackages']
			) ) : array();
			update_post_meta( $coupon_id, 'include_packages', $include_packages );

			$exclude_packages = ! empty( $request['excludePackages'] ) ? array_filter( array_map(
				function( $course ) {
					return absint( $course['id'] );
				},
				$request['excludePackages']
			) ) : array();
			update_post_meta( $coupon_id, 'exclude_packages', $exclude_packages );

			$include_courses = ! empty( $request['includeCourses'] ) ? array_filter( array_map(
				function( $course ) {
					return absint( $course['id'] );
				},
				$request['includeCourses']
			) ) : array();
			update_post_meta( $coupon_id, 'include_courses', $include_courses );

			$exclude_courses = ! empty( $request['excludeCourses'] ) ? array_filter( array_map(
				function( $course ) {
					return absint( $course['id'] );
				},
				$request['excludeCourses']
			) ) : array();
			update_post_meta( $coupon_id, 'exclude_courses', $exclude_courses );

			$include_course_categories = ! empty( $request['includeCourseCategories'] ) ? array_filter( array_map(
				function( $course ) {
					return absint( $course['id'] );
				},
				$request['includeCourseCategories']
			) ) : array();
			update_post_meta( $coupon_id, 'include_course_categories', $include_course_categories );

			$exclude_course_categories = ! empty( $request['excludeCourseCategories'] ) ? array_filter( array_map(
				function( $course ) {
					return absint( $course['id'] );
				},
				$request['excludeCourseCategories']
			) ) : array();
			update_post_meta( $coupon_id, 'exclude_course_categories', $exclude_course_categories );

			$email_restrictions = ! empty( $request['allowEmails'] ) ? array_filter( array_map( 'trim', explode( ',', $request['allowEmails'] ) ) ) : array();
			update_post_meta( $coupon_id, 'email_restrictions', $email_restrictions );

			return new \WP_REST_Response(
				array(
					'success' => true,
					'message' => $is_insert ? __( 'Insert coupon successfully!', 'learnpress-upsell' ) : __( 'Update coupon successfully!', 'learnpress-upsell' ),
				)
			);
		} catch ( \Throwable $th ) {
			return new \WP_REST_Response(
				array(
					'success' => false,
					'message' => $th->getMessage(),
				)
			);
		}
	}

	public function get_packages( $request ) {
		$packages = array();

		$query_args = array(
			'post_type'      => LP_PACKAGE_CPT,
			'post_status'    => array( 'publish' ),
			'posts_per_page' => 10,
		);

		if ( ! empty( $request['paged'] ) ) {
			$query_args['paged'] = absint( $request['paged'] ) + 1;
		}

		// Search packages
		if ( ! empty( $request['search'] ) ) {
			$query_args['s'] = $request['search'];
		}

		$query       = new \WP_Query();

		$result      = $query->query( $query_args );
		$total_posts = $query->found_posts;

		if ( $total_posts < 1 ) {
			unset( $query_args['paged'] );
			$count_query = new \WP_Query();
			$count_query->query( $query_args );
			$total_posts = $count_query->found_posts;
		}

		if ( $query->have_posts() ) {
			while ( $query->have_posts() ) {
				$query->the_post();

				$post = get_post( get_the_ID() );

				$packages[] = array(
					'id'          => $post->ID,
					'name'       => $post->post_title,
					'description' => $post->post_content,
					'status'      => $post->post_status,
					'author'      => get_the_author(),
					'date'        => $post->post_modified ?? $post->post_modified_gmt ?? '',
				);
			}
		}

		return $packages;
	}

	public function get_coupons( $request ) {
		$coupons = array();

		$query_args = array(
			'post_type'      => LP_COUPON_CPT,
			'post_status'    => array( 'publish', 'private', 'draft', 'pending', 'trash', 'future' ),
			'posts_per_page' => 10,
		);

		if ( ! empty( $request['paged'] ) ) {
			$query_args['paged'] = absint( $request['paged'] ) + 1;
		}

		$query       = new \WP_Query();
		$result      = $query->query( $query_args );
		$total_posts = $query->found_posts;

		if ( $total_posts < 1 ) {
			unset( $query_args['paged'] );
			$count_query = new \WP_Query();
			$count_query->query( $query_args );
			$total_posts = $count_query->found_posts;
		}

		if ( $query->have_posts() ) {
			while ( $query->have_posts() ) {
				$query->the_post();

				$post = get_post( get_the_ID() );

				$coupons[] = array_merge( array(
					'id'          => $post->ID,
					'title'       => $post->post_title,
					'description' => $post->post_content,
					'status'      => $post->post_status,
					'author'      => get_the_author(),
					'date'        => $post->post_modified ?? $post->post_modified_gmt ?? '',
					'usageCount'  => get_post_meta( $post->ID, 'usage_count', true ),
				), $this->get_coupon_data( $post->ID ) );
			}
		}

		return new \WP_REST_Response(
			array(
				'success'  => true,
				'coupons' => $coupons,
				'total'    => (int) $total_posts,
				'pages'    => (int) ceil( $total_posts / (int) $query->query_vars['posts_per_page'] ),
			)
		);
	}

	private function get_coupon_data( $id ) {
		$include_packages = get_post_meta( $id, 'include_packages', true );
		$exclude_packages = get_post_meta( $id, 'exclude_packages', true );
		$include_courses = get_post_meta( $id, 'include_courses', true );
		$exclude_courses = get_post_meta( $id, 'exclude_courses', true );
		$include_course_categories = get_post_meta( $id, 'include_course_categories', true );
		$exclude_course_categories = get_post_meta( $id, 'exclude_course_categories', true );

		$output = array(
			'discountType' => get_post_meta( $id, 'discount_type', true ),
			'discountAmount' => get_post_meta( $id, 'discount_amount', true ),
			'discountStartDate' => get_post_meta( $id, 'discount_start_date', true ),
			'discountEndDate' => get_post_meta( $id, 'discount_end_date', true ),
			'limitPerCoupon' => get_post_meta( $id, 'usage_limit', true ),
			'limitPerUser' => get_post_meta( $id, 'usage_limit_per_user', true ),
			'includePackages' => ! empty( $include_packages ) ? array_map(
				function( $package_id ) {
					return array(
						'id' => $package_id,
						'name'  => html_entity_decode( get_the_title( $package_id ) ),
					);
				},
				(array) $include_packages
			) : array(),
			'excludePackages' => $exclude_packages ? array_map(
				function( $package_id ) {
					return array(
						'id' => $package_id,
						'name'  => html_entity_decode( get_the_title( $package_id ) ),
					);
				},
				(array) $exclude_packages
			) : array(),
			'includeCourses' => $include_courses ? array_map(
				function( $course_id ) {
					return array(
						'id' => $course_id,
						'name'  => html_entity_decode( get_the_title( $course_id ) ),
					);
				},
				(array) $include_courses
			) : array(),
			'excludeCourses' => $exclude_courses ? array_map(
				function( $course_id ) {
					return array(
						'id' => $course_id,
						'name'  => html_entity_decode( get_the_title( $course_id ) ),
					);
				},
				(array) $exclude_courses
			) : array(),
			'includeCourseCategories' => $include_course_categories ? array_map(
				function( $course_category_id ) {
					$term = get_term( $course_category_id, 'course_category' );

					return array(
						'id' => $course_category_id,
						'name'  => ! is_wp_error( $term ) ? html_entity_decode( $term->name ) : '',
					);
				},
				(array) $include_course_categories
			) : array(),
			'excludeCourseCategories' => $exclude_course_categories ? array_map(
				function( $course_category_id ) {
					$term = get_term( $course_category_id, 'course_category' );

					return array(
						'id' => $course_category_id,
						'name'  => ! is_wp_error( $term ) ? html_entity_decode( $term->name ) : '',
					);
				},
				(array) $exclude_course_categories
			) : array(),
			'allowEmails' => implode( ', ', (array) get_post_meta( $id, 'email_restrictions', true ) ),
		);

		return $output;
	}

	public static function instance() {
		if ( ! self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}
}
Admin::instance();

