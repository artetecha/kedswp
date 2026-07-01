<?php
namespace LearnPress\Upsell\Package\API;

use Exception;
use LearnPress\Models\CourseModel;
use LearnPress\Upsell\Package\Package;

class Admin {

	protected static $instance = null;

	const NAMESPACE = 'learnpress-package/v1/admin';

	public function __construct() {
		add_action( 'rest_api_init', array( $this, 'register_rest_api' ) );
	}

	public function register_rest_api() {
		register_rest_route(
			self::NAMESPACE,
			'/packages',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_packages' ),
				'permission_callback' => array( $this, 'permission_callback' ),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/package/(?P<id>[\d]+)',
			array(
				array(
					'methods'             => 'GET',
					'callback'            => array( $this, 'get_package' ),
					'permission_callback' => array( $this, 'permission_callback' ),
				),
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'save_package' ),
					'permission_callback' => array( $this, 'permission_callback' ),
				),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/delete',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'delete_package' ),
				'permission_callback' => array( $this, 'permission_callback' ),
			)
		);
	}

	public function permission_callback() {
		return current_user_can( 'manage_options' );
	}

	public function delete_package( $request ) {
		$id       = $request->get_param( 'id' );
		$is_trash = $request->get_param( 'trash' );

		if ( ! $id ) {
			return new \WP_Error( 'package_not_found', __( 'Package not found', 'learnpress-upsell' ), array( 'status' => 404 ) );
		}

		$package = get_post( $id );

		if ( ! $package ) {
			return new \WP_Error( 'package_not_found', __( 'Package not found', 'learnpress-upsell' ), array( 'status' => 404 ) );
		}

		if ( $is_trash ) {
			$deleted = wp_trash_post( $id );
		} else {
			$deleted = wp_delete_post( $id, true );
		}

		if ( ! $deleted ) {
			return new \WP_Error( 'package_not_deleted', __( 'Package not deleted', 'learnpress-upsell' ), array( 'status' => 500 ) );
		}

		return array(
			'success' => true,
			'message' => $is_trash ? __( 'Package moved to trash', 'learnpress-upsell' ) : __( 'Package deleted', 'learnpress-upsell' ),
		);
	}

	public function get_packages( $request ) {
		$packages = array();

		$query_args = array(
			'post_type'      => LP_PACKAGE_CPT,
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

				$package = new Package( get_the_ID() );

				$price = $package->get_price_html();

				$course_ids = get_post_meta( $post->ID, '_lp_package_courses', false );

				$courses = array();
				if ( $course_ids ) {
					foreach ( $course_ids as $course_id ) {
						$course = CourseModel::find( $course_id, true );
						// Check if course exists.
						if ( ! $course || $course->post_status != 'publish' ) {
							continue;
						}

						$courses[] = array(
							'id'    => $course_id,
							'title' => html_entity_decode( get_the_title( $course_id ) ),
						);
					}
				}

				$packages[] = array(
					'id'          => $post->ID,
					'title'       => $post->post_title,
					'description' => $post->post_content,
					'permalink'   => get_the_permalink( $post->ID ),
					'price'       => $package->get_price_html(),
					'courses'     => $courses,
					'status'      => $post->post_status,
					'author'      => get_the_author(),
					'date'        => $post->post_modified ?? $post->post_modified_gmt ?? '',
				);
			}
		}

		return new \WP_REST_Response(
			array(
				'success'  => true,
				'packages' => $packages,
				'total'    => (int) $total_posts,
				'pages'    => (int) ceil( $total_posts / (int) $query->query_vars['posts_per_page'] ),
			)
		);
	}

	public function get_package( $request ) {
		$id = ! empty( $request['id'] ) ? absint( $request['id'] ) : 0;

		try {
			if ( empty( $id ) ) {
				throw new Exception( __( 'Package not found', 'learnpress-upsell' ) );
			}

			$post = get_post( $id );

			if ( ! $post || $post->post_type !== LP_PACKAGE_CPT ) {
				throw new Exception( __( 'Package not found', 'learnpress-upsell' ) );
			}

			$price            = get_post_meta( $post->ID, '_lp_package_price', true );
			$new_price        = get_post_meta( $post->ID, '_lp_package_new_price_enabled', true );
			$new_price_type   = get_post_meta( $post->ID, '_lp_package_new_price_type', true );
			$new_price_amount = get_post_meta( $post->ID, '_lp_package_new_price_amount', true );
			$sale_price       = get_post_meta( $post->ID, '_lp_package_sale_price', true );
			$course_ids       = get_post_meta( $post->ID, '_lp_package_courses', false );
			$certificate_id   = get_post_meta( $post->ID, '_lp_package_certificate', true );

			$courses = array();
			if ( $course_ids ) {
				foreach ( $course_ids as $course_id ) {
					$course = CourseModel::find( $course_id, true );
					// Check if course exists.
					if ( ! $course || $course->post_status != 'publish' ) {
						continue;
					}

					$courses[] = array(
						'id'    => $course_id,
						'name'  => html_entity_decode( get_the_title( $course_id ) ),
						'price' => $course->get_price(),
					);
				}
			}

			$document = defined( 'ELEMENTOR_VERSION' ) ? \Elementor\Plugin::$instance->documents->get( absint( $post->ID ) ) : false;

			$feature_image_url = get_the_post_thumbnail_url( $post->ID, 'full' );

			$data = array(
				'id'              => $post->ID,
				'title'           => $post->post_title,
				'description'     => $post->post_content,
				'status'          => $post->post_status,
				'publishDate'     => $post->post_date ?? $post->post_date_gmt ?? '',
				'price'           => ! empty( $price ) ? $price : 0,
				'newPriceEnabled' => $new_price === 'yes' ? true : false,
				'newPriceType'    => $new_price_type ? $new_price_type : 'percent',
				'newPriceAmount'  => ! empty( $new_price_amount ) ? $new_price_amount : 0,
				'salePrice'       => ! empty( $sale_price ) ? $sale_price : 0,
				'courses'         => ! empty( $courses ) ? $courses : array(),
				'featuredImage'   => array(
					'id'  => get_post_thumbnail_id( $post->ID ),
					'url' => ! empty( $feature_image_url ) ? $feature_image_url : '',
				),
				'tags'            => $this->get_package_taxonomy( $post->ID ),
				'is_elementor'    => $document ? $document->is_built_with_elementor() : false,
				'certificateID'   => $certificate_id ? absint( $certificate_id ) : 0,
			);

			return new \WP_REST_Response(
				array(
					'success' => true,
					'package' => $data,
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

	private function get_package_taxonomy( $id ) {
		$terms  = get_the_terms( $id, 'learnpress_package_tag' );
		$output = array();

		if ( $terms ) {
			$output = wp_list_pluck( $terms, 'term_id' );
		}

		return $output;
	}

	public function save_package( $request ) {
		try {
			$package_id = $request['id'];

			$is_insert = empty( $package_id ) ? true : false;

			if ( $is_insert ) {
				$argc = array(
					'post_date'     => current_time( 'mysql' ),
					'post_date_gmt' => current_time( 'mysql', 1 ),
					'post_type'     => LP_PACKAGE_CPT,
					'post_title'    => sanitize_text_field( $request['title'] ?? '' ),
					'post_content'  => wp_unslash( $request['description'] ?? '' ),
					'post_status'   => ! empty( $request['status'] ) ? sanitize_text_field( $request['status'] ) : 'publish',
					'tax_input'     => array(
						'learnpress_package_tag' => ! empty( $request['tags'] ) ? array_map( 'absint', $request['tags'] ) : array(),
					),
				);

				if ( ! empty( $request['publishDate'] ) ) {
					$argc['post_date']     = $request['publishDate'];
					$argc['post_date_gmt'] = get_gmt_from_date( $request['publishDate'] );
				}

				$package_id = wp_insert_post( $argc, true );

				if ( is_wp_error( $package_id ) ) {
					throw new Exception( $package_id->get_error_message() );
				}

				$post = get_post( $package_id );
			} else {
				$post = get_post( $package_id );

				if ( ! $post || $post->post_type !== LP_PACKAGE_CPT ) {
					throw new Exception( __( 'Package not found', 'learnpress-upsell' ) );
				}

				$argv = array(
					'post_date'     => current_time( 'mysql' ),
					'post_date_gmt' => current_time( 'mysql', 1 ),
					'ID'            => $package_id,
					'post_title'    => sanitize_text_field( $request['title'] ?? '' ),
					'post_content'  => wp_unslash( $request['description'] ?? '' ),
					'post_status'   => ! empty( $request['status'] ) ? sanitize_text_field( $request['status'] ) : 'publish',
					'tax_input'     => array(
						'learnpress_package_tag' => ! empty( $request['tags'] ) ? array_map( 'absint', $request['tags'] ) : array(),
					),
				);

				if ( ! empty( $request['publishDate'] ) ) {
					$argv['post_date']     = $request['publishDate'];
					$argv['post_date_gmt'] = get_gmt_from_date( $request['publishDate'] );
				}

				$package_id = wp_update_post( $argv, true );

				if ( is_wp_error( $package_id ) ) {
					throw new Exception( $package_id->get_error_message() );
				}
			}

			if ( ! empty( $request['featuredImage']['id'] ) ) {
				set_post_thumbnail( $package_id, absint( $request['featuredImage']['id'] ) );
			} else {
				delete_post_thumbnail( $package_id );
			}

			$publish_date = ! empty( $request['publishDate'] ) ? sanitize_text_field( $request['publishDate'] ) : '';

			$course_ids = array();

			if ( ! empty( $request['courses'] ) ) {
				$course_ids = array_map(
					function( $course ) {
						return absint( $course['id'] );
					},
					$request['courses']
				);
			}

			$course_ids_old = get_post_meta( $package_id, '_lp_package_courses', false );
			$course_ids_old = ! empty( $course_ids_old ) ? array_values( $course_ids_old ) : array();

			// delete diffirent course ids.
			$delete_course_ids = array_diff( $course_ids_old, $course_ids );

			foreach ( $delete_course_ids as $course_id ) {
				delete_post_meta( $package_id, '_lp_package_courses', $course_id );
			}

			// add new course ids.
			$add_course_ids = array_diff( $course_ids, $course_ids_old );

			foreach ( $add_course_ids as $course_id ) {
				// Check course exists.
				if ( ! get_post( $course_id ) ) {
					continue;
				}

				add_post_meta( $package_id, '_lp_package_courses', $course_id, false );
			}

			$price             = ! empty( $request['price'] ) ? floatval( $request['price'] ) : 0;
			$new_price_enabled = ! empty( $request['newPriceEnabled'] ) ? 'yes' : 'no';
			$new_price_type    = ! empty( $request['newPriceType'] ) ? sanitize_text_field( $request['newPriceType'] ) : 'percent';
			$new_price_amount  = ! empty( $request['newPriceAmount'] ) ? floatval( $request['newPriceAmount'] ) : 0;
			$sale_price        = ! empty( $request['salePrice'] ) ? floatval( $request['salePrice'] ) : 0;

			update_post_meta( $package_id, '_lp_package_price', $price );
			update_post_meta( $package_id, '_lp_package_new_price_enabled', $new_price_enabled );
			update_post_meta( $package_id, '_lp_package_new_price_type', $new_price_type );
			update_post_meta( $package_id, '_lp_package_new_price_amount', $new_price_amount );
			update_post_meta( $package_id, '_lp_package_sale_price', $sale_price );

			// Update certificate.
			if ( class_exists( 'LP_Addon_Certificates' ) ) {
				$certificate_id = ! empty( $request['certificateID'] ) ? absint( $request['certificateID'] ) : 0;
				update_post_meta( $package_id, '_lp_package_certificate', $certificate_id );
			}

			return new \WP_REST_Response(
				array(
					'success' => true,
					'message' => $is_insert ? __( 'Insert package successfully!', 'learnpress-upsell' ) : __( 'Update package successfully!', 'learnpress-upsell' ),
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

	public static function instance() {
		if ( ! self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}
}

Admin::instance();
