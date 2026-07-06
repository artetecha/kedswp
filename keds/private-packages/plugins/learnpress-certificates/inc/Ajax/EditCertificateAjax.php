<?php
/**
 * class EditCertificateAjax
 *
 * This class handles the AJAX request to edit the certificate.
 *
 * @since 4.2.0
 * @version 1.0.0
 */

namespace LearnPress\Certificate\Ajax;

use Exception;
use LearnPress\Ajax\AbstractAjax;
use LearnPress\Certificate\Models\CertificatePostModel;
use LearnPress\Certificate\Models\CertificateBuilderData;
use LearnPress\Certificate\Models\CourseCertificateInfo;
use LearnPress\Certificate\Services\CertificateService;
use LearnPress\Certificate\TemplateHooks\AdminCertificateTemplate;
use LearnPress\Certificate\TemplateHooks\CourseBuilder\CBCertificateTemplate;
use LearnPress\CourseBuilder\CourseBuilder;
use LearnPress\Helpers\Template;
use LearnPress\Models\CourseModel;
use LP_Helper;
use LP_Certificate;
use LP_REST_Response;
use LP_User_Certificate;
use Throwable;

class EditCertificateAjax extends AbstractAjax {
	/**
	 * Check permissions and validate parameters.
	 *
	 * @throws Exception
	 *
	 * @since 4.2.8.6
	 * @version 1.0.1
	 */
	public static function check_valid() {
		$params = wp_unslash( $_REQUEST['data'] ?? '' );
		if ( empty( $params ) ) {
			throw new Exception( 'Error: params invalid!' );
		}

		// Check permissions
		if ( ! current_user_can( 'edit_' . LP_ADDON_CERTIFICATES_CERT_CPT . 's' ) ) {
			throw new Exception( 'Error: You do not have permission to perform this action!' );
		}

		return LP_Helper::json_decode( $params, true );
	}

	/**
	 * Save certificate via Ajax
	 *
	 * @return void
	 */
	public static function save_certificate() {
		$response = new LP_REST_Response();

		try {
			$data = self::check_valid();

			$post_id       = absint( $data['post_id'] ?? 0 );
			$title         = sanitize_text_field( $data['title'] ?? '' );
			$price         = (float) ( $data['price'] ?? 0 );
			$thumbnail     = esc_url_raw( $data['thumbnail'] ?? '' );
			$status        = sanitize_text_field( $data['status'] ?? 'draft' );
			$visibility    = sanitize_text_field( $data['visibility'] ?? 'public' );
			$post_password = sanitize_text_field( $data['post_password'] ?? '' );
			$post_date     = sanitize_text_field( $data['post_date'] ?? '' );

			if ( empty( $title ) ) {
				throw new Exception( __( 'Certificate title is required', 'learnpress-certificates' ) );
			}

			if ( ! $post_id ) {
				throw new Exception( __( 'Certificate ID is required', 'learnpress-certificates' ) );
			}

			$certificateModel = CertificatePostModel::find( $post_id, true );
			if ( ! $certificateModel ) {
				throw new Exception( __( 'Certificate not found', 'learnpress-certificates' ) );
			}

			if ( $visibility === 'private' ) {
				$status        = 'private';
				$post_password = '';
			} elseif ( $visibility !== 'password' ) {
				$post_password = '';
			}

			$allowed_statuses = array( 'publish', 'draft', 'pending', 'private', 'future' );
			$final_status     = in_array( $status, $allowed_statuses, true ) ? $status : 'draft';

			$update_args = array(
				'ID'            => $post_id,
				'post_title'    => $title,
				'post_status'   => $final_status,
				'post_password' => $post_password,
			);
			if ( ! empty( $post_date ) ) {
				$update_args['post_date']     = $post_date;
				$update_args['post_date_gmt'] = get_gmt_from_date( $post_date );
			}
			wp_update_post( $update_args );

			$certificateModel = CertificatePostModel::find( $post_id, true );

			$certificateModel->set_price( $price );
			$thumbnail = str_replace( home_url(), '', esc_url_raw( $thumbnail ) );
			$certificateModel->set_thumbnail( $thumbnail );

			$certificateModel->clean_caches();

			update_post_meta( $post_id, '_lp_cert_saved_via_ajax', time() );

			$response->status  = 'success';
			$response->message = __( 'Certificate saved successfully', 'learnpress-certificates' );
			$response->data    = array(
				'post_id' => $post_id,
			);
		} catch ( Exception $e ) {
			$response->status  = 'error';
			$response->message = $e->getMessage();
		}

		wp_send_json( $response );
	}

	public static function lp_cert_toggle_share() {
		$response = new LP_REST_Response();

		try {
			$raw = wp_unslash( $_REQUEST['data'] ?? '' );
			if ( empty( $raw ) ) {
				throw new Exception( __( 'Invalid request.', 'learnpress-certificates' ) );
			}

			$data   = LP_Helper::json_decode( $raw, true );
			$key    = sanitize_text_field( $data['cert_key'] ?? '' );
			$shared = ! empty( $data['shared'] );

			if ( ! lp_cert_share_link_enabled() ) {
				throw new Exception( __( 'Share link is disabled globally.', 'learnpress-certificates' ) );
			}

			if ( empty( $key ) ) {
				throw new Exception( __( 'Certificate key is required.', 'learnpress-certificates' ) );
			}

			$user_id = get_current_user_id();
			if ( ! $user_id ) {
				throw new Exception( __( 'You must be logged in.', 'learnpress-certificates' ) );
			}

			$cert = LP_Certificate::get_cert_by_key( $key );
			if ( ! $cert instanceof LP_User_Certificate ) {
				throw new Exception( __( 'Certificate not found.', 'learnpress-certificates' ) );
			}

			if ( (int) $cert->get_user_id() !== $user_id ) {
				throw new Exception( __( 'You are not allowed to change this certificate.', 'learnpress-certificates' ) );
			}

			$cert->set_shared( $shared );

			$response->status  = 'success';
			$response->message = $shared
				? __( 'Certificate is now public.', 'learnpress-certificates' )
				: __( 'Certificate is now private.', 'learnpress-certificates' );
		} catch ( Throwable $e ) {
			$response->status  = 'error';
			$response->message = $e->getMessage();
		}

		wp_send_json( $response );
	}

	/**
	 * Choose template type for certificate via Ajax
	 *
	 * @return void
	 */
	public static function lp_cert_choose_template_type() {
		$response = new LP_REST_Response();

		try {
			$data = self::check_valid();

			$certificate_id = absint( $data['certificate_id'] ?? 0 );
			$template_type  = LP_Helper::sanitize_params_submitted( $data['template_type'] ?? '' );

			$certificate = CertificatePostModel::find( $certificate_id, true );
			if ( ! $certificate ) {
				throw new Exception( 'Error: Certificate not found!' );
			}

			$layer_arr = self::get_template_layers( $template_type );

			if ( empty( $layer_arr ) || ! is_array( $layer_arr ) ) {
				throw new Exception( 'Error: Layers config is invalid!' );
			}

			$builder_data = new CertificateBuilderData( $certificate );
			$builder_data->save_layers( $layer_arr );

			$response->status       = 'success';
			$response->data->layers = $layer_arr;
			$response->message      = 'Layers saved successfully!';
		} catch ( Throwable $e ) {
			$response->message = $e->getMessage();
		}

		wp_send_json( $response );
	}
	private static function get_template_layers( string $template_type ): ?array {
		$config_file = LP_ADDON_CERTIFICATES_PATH . '/config/templates-default.php';

		if ( ! file_exists( $config_file ) ) {
			return null;
		}

		$config = include $config_file;

		if ( ! is_array( $config ) || empty( $config['templates'] ) ) {
			return null;
		}

		$assets_url = plugins_url( 'assets', LP_ADDON_CERTIFICATES_FILE );
		array_walk_recursive(
			$config,
			function ( &$value ) use ( $assets_url ) {
				if ( is_string( $value ) ) {
					$value = str_replace( '{{CERT_ASSETS_URL}}', $assets_url, $value );
				}
			}
		);

		$templates = $config['templates'];

		if ( ! isset( $templates[ $template_type ] ) ) {
			return [
				'width'      => 842,
				'height'     => 595,
				'background' => '#ffffff',
				'layers'     => [],
			];
		}

		$template = $templates[ $template_type ];

		return [
			'width'      => $template['width'] ?? 842,
			'height'     => $template['height'] ?? 595,
			'background' => $template['background'] ?? '#ffffff',
			'layers'     => $template['layers'] ?? [],
		];
	}

	public static function certificate_builder_save() {
		$response = new LP_REST_Response();

		try {
			$data           = self::check_valid();
			$certificate_id = absint( $data['certificate_id'] ?? 0 );
			$layers_data    = LP_Helper::sanitize_params_submitted( $data['layers'] ?? [] );

			$certificate = CertificatePostModel::find( $certificate_id, true );
			if ( ! $certificate ) {
				throw new Exception( 'Error: Certificate not found!' );
			}

			$builder_data = new CertificateBuilderData( $certificate );
			$builder_data->save_layers( $layers_data );

			$response->status = 'success';
		} catch ( Throwable $e ) {
			$response->message = $e->getMessage();
		}

		wp_send_json( $response );
	}

	/**
	 * Add layer to certificate
	 */
	public static function certificate_builder_add_layer() {
		$response = new LP_REST_Response();

		try {
			$data           = self::check_valid();
			$certificate_id = absint( $data['certificate_id'] ?? 0 );
			$layer_data     = LP_Helper::sanitize_params_submitted( $data['layer'] ?? [] );
			$certificate    = CertificatePostModel::find( $certificate_id, true );
			if ( ! $certificate ) {
				throw new Exception( 'Error: Certificate not found!' );
			}

			$builder_data = new CertificateBuilderData( $certificate );
			$raw_layers   = $builder_data->get_raw_layers();

			if ( empty( $layer_data['id'] ) ) {
				$layer_data['id'] = 'layer_' . time() . '_' . wp_rand( 1000, 9999 );
			}

			$builder_data->add_layer( $layer_data );
			$raw_layers = $builder_data->get_raw_layers();

			$response->status       = 'success';
			$response->data->layer  = $layer_data;
			$response->data->layers = $raw_layers['layers'];
			$response->message      = 'Layer added successfully!';
		} catch ( Throwable $e ) {
			$response->message = $e->getMessage();
		}

		wp_send_json( $response );
	}

	private static function load_images_helper( string $render_method = 'render_upload_items', int $default_per_page = 20 ) {
		$response = new LP_REST_Response();

		try {
			$data     = self::check_valid();
			$offset   = absint( $data['offset'] ?? 0 );
			$per_page = absint( $data['per_page'] ?? $default_per_page );

			if ( $per_page <= 0 ) {
				$per_page = $default_per_page;
			}

			$images = get_posts(
				[
					'post_type'      => 'attachment',
					'post_mime_type' => 'image',
					'posts_per_page' => $per_page,
					'orderby'        => 'date',
					'order'          => 'DESC',
					'offset'         => $offset,
				]
			);

			$template = AdminCertificateTemplate::instance();
			$html     = '';

			if ( ! empty( $images ) && method_exists( $template, $render_method ) ) {
				$html = $template->$render_method( $images );
			}

			$response->status            = 'success';
			$response->data->html        = $html;
			$response->data->next_offset = $offset + count( $images );
			$response->data->has_more    = count( $images ) === $per_page;
		} catch ( Throwable $e ) {
			$response->status  = 'error';
			$response->message = $e->getMessage();
		}

		wp_send_json( $response );
	}


	public static function certificate_builder_load_images() {
		self::load_images_helper( 'render_upload_items', 20 );
	}

	public static function certificate_builder_load_background_images() {
		self::load_images_helper( 'render_background_bg_items', 20 );
	}

	public static function certificate_load_more_course_certs() {
		$response = new LP_REST_Response();

		try {
			$data = self::check_valid();

			$course_id   = absint( $data['course_id'] ?? 0 );
			$cert_active = absint( $data['cert_active'] ?? 0 );
			$offset      = absint( $data['offset'] ?? 0 );
			$limit       = 6;

			global $wpdb;

			$where = $wpdb->prepare(
				"post_type = %s AND post_status = 'publish'",
				LP_ADDON_CERTIFICATES_CERT_CPT
			);

			if ( $cert_active ) {
				$where .= $wpdb->prepare( ' AND ID != %d', $cert_active );
			}

			//Todo: refactor code use CertificateService
			$certificates = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT ID, post_title, post_date FROM {$wpdb->posts} WHERE {$where} ORDER BY post_date DESC LIMIT %d OFFSET %d",
					$limit,
					$offset
				)
			);

			$total_cer         = (int) $wpdb->get_var(
				"SELECT COUNT(ID) FROM {$wpdb->posts} WHERE {$where}"
			);
			$is_course_builder = ! empty( $data['is_course_builder'] );
			$default_thumb     = plugins_url( '/assets/images/no-image.png', LP_ADDON_CERTIFICATES_FILE );

			ob_start();
			foreach ( $certificates as $cert_obj ) {
				$id         = (int) $cert_obj->ID;
				$cert_post  = get_post( $id );
				$thumb_rel  = get_post_meta( $id, '_lp_cert_thumbnail', true );
				$thumb_url  = ! empty( $thumb_rel ) ? home_url( $thumb_rel ) : $default_thumb;
				$cert_title = $cert_post ? $cert_post->post_title : __( 'Certificate', 'learnpress-certificates' );
				$certModel  = CertificatePostModel::find( $id, true );
				$edit_link  = $certModel ? $certModel->get_edit_link( $is_course_builder ) : admin_url( 'post.php?post=' . $id . '&action=edit' );
				?>
				<div class="theme" data-id="<?php echo esc_attr( $id ); ?>">
					<div class="theme-screenshot">
						<img src="<?php echo esc_url( $thumb_url ); ?>"
							alt="<?php echo esc_attr( $cert_title ); ?>" />
					</div>
					<div class="theme-id-container">
						<h2 class="theme-name"><?php echo esc_html( $cert_title ); ?></h2>
						<div class="theme-actions">
							<a class="button button-primary button-remove-certificate"
								href="#"
								data-popup-title="<?php esc_attr_e( 'Remove assigned certificate?', 'learnpress-certificates' ); ?>"
								data-popup-text="<?php esc_attr_e( 'Removing this certificate will also turn off the course detail showcase section.', 'learnpress-certificates' ); ?>"
								data-popup-confirm="<?php esc_attr_e( 'Remove', 'learnpress-certificates' ); ?>"
								data-popup-cancel="<?php esc_attr_e( 'Cancel', 'learnpress-certificates' ); ?>">
								<?php esc_html_e( 'Remove', 'learnpress-certificates' ); ?>
							</a>
							<a class="button button-primary button-assign-certificate" href="#">
								<?php esc_html_e( 'Assign', 'learnpress-certificates' ); ?>
							</a>
							<a class="button" target="_blank" href="<?php echo esc_url( $edit_link ); ?>">
								<?php esc_html_e( 'Edit', 'learnpress-certificates' ); ?>
							</a>
						</div>
					</div>
				</div>
				<?php
			}
			$html = ob_get_clean();

			$response->status         = 'success';
			$response->data->html     = $html;
			$response->data->has_more = ( $offset + count( $certificates ) ) < $total_cer;
		} catch ( Throwable $e ) {
			$response->status  = 'error';
			$response->message = $e->getMessage();
		}

		wp_send_json( $response );
	}

	/**
	 * Save certificate on edit course screen
	 *
	 * @since 4.2.3
	 * @version 1.0.1
	 * @throws Exception
	 */
	public static function save_course_cert_info() {
		$response = new LP_REST_Response();

		try {
			$data      = self::check_valid();
			$course_id = absint( $data['course_id'] ?? 0 );

			$courseModel = CourseModel::find( $course_id, true );
			if ( ! $courseModel ) {
				throw new Exception( __( 'Invalid course.', 'learnpress-certificates' ) );
			}

			$courseCerModel = new CourseCertificateInfo( $courseModel );

			$title        = LP_Helper::sanitize_params_submitted( $data['title'] ?? '' );
			$description  = LP_Helper::sanitize_params_submitted( $data['description'] ?? '', 'html' );
			$image        = LP_Helper::sanitize_params_submitted( $data['image'] ?? '', 'esc_url_raw' );
			$enable       = ! empty( $data['enable'] ) ? 1 : 0;
			$cerPostModel = $courseCerModel->get_cert_post_model();

			if ( $enable && ! $cerPostModel ) {
				throw new Exception( __( 'Cannot enable certificate showcase: this course has no assigned certificate.', 'learnpress-certificates' ) );
			}

			if ( $enable && '' === trim( $title ) ) {
				throw new Exception( __( 'Title is required when certificate showcase is enabled.', 'learnpress-certificates' ) );
			}

			$info      = compact( 'enable', 'image', 'title', 'description' );
			$data_save = wp_json_encode( $info, JSON_UNESCAPED_UNICODE );

			$courseCerPostModel = $courseCerModel->get_post_model();
			$courseCerPostModel->meta_data->{CourseCertificateInfo::META_KEY_CERT_INFO} = $data_save;
			$courseCerPostModel->save();

			$response->status  = 'success';
			$response->message = __( 'Saved successfully.', 'learnpress-certificates' );
		} catch ( Exception $e ) {
			$response->message = $e->getMessage();
		}

		wp_send_json( $response );
	}

	/**
	 * Assign or unassign certificate to course
	 *
	 * if cert_id > 0 assign, else unassign
	 *
	 * @since 4.2.3
	 * @version 1.0.0
	 */
	public static function cert_assign_to_course() {
		$response = new LP_REST_Response();

		try {
			$data      = self::check_valid();
			$course_id = absint( $data['course_id'] ?? 0 );
			$cert_id   = absint( $data['cert_id'] ?? 0 );

			$courseModel = CourseModel::find( $course_id, true );
			if ( ! $courseModel ) {
				throw new Exception( __( 'Invalid course.', 'learnpress-certificates' ) );
			}

			$coursePostModel = $courseModel->get_post_model();

			if ( $cert_id > 0 ) {
				$coursePostModel->meta_data->{ CourseCertificateInfo::META_KEY_CERT_ID } = $cert_id;
				$response->message = __( 'Assign certificate successfully', 'learnpress-certificates' );
			} else {
				unset( $coursePostModel->meta_data->{ CourseCertificateInfo::META_KEY_CERT_ID } );
				delete_post_meta( $course_id, CourseCertificateInfo::META_KEY_CERT_ID );
				$response->message = __( 'Remove certificate from course successfully', 'learnpress-certificates' );
			}

			$coursePostModel->save();
			$response->status = 'success';
		} catch ( Exception $e ) {
			$response->message = $e->getMessage();
		}

		wp_send_json( $response );
	}

	public static function lp_cert_save_draft() {
		$response = new LP_REST_Response();

		try {
			$data = self::check_valid();

			$cert_id       = absint( $data['cert_id'] ?? 0 );
			$title         = sanitize_text_field( $data['title'] ?? 'Certificate' );
			$template_type = sanitize_text_field( $data['template_type'] ?? 'blank' );

			if ( ! $cert_id ) {
				throw new Exception( __( 'Invalid certificate ID', 'learnpress-certificates' ) );
			}

			$post = get_post( $cert_id );

			if ( ! $post || $post->post_type !== LP_ADDON_CERTIFICATES_CERT_CPT ) {
				throw new Exception( __( 'Certificate not found', 'learnpress-certificates' ) );
			}

			if ( $post->post_status === 'auto-draft' ) {
				$update_result = wp_update_post(
					array(
						'ID'          => $cert_id,
						'post_status' => 'draft',
						'post_title'  => $title,
					),
					true
				);

				if ( is_wp_error( $update_result ) ) {
					throw new Exception( $update_result->get_error_message() );
				}
			}

			$is_course_builder = ! empty( $data['is_course_builder'] );
			$certificateModel  = CertificatePostModel::find( $cert_id, true );
			$edit_link         = $certificateModel ? $certificateModel->get_edit_link( $is_course_builder ) : '';
			$add_new_link      = admin_url( 'post-new.php?post_type=' . LP_ADDON_CERTIFICATES_CERT_CPT );

			if ( $is_course_builder && class_exists( '\LearnPress\CourseBuilder\CourseBuilder' ) ) {
				if ( method_exists( '\LearnPress\CourseBuilder\CourseBuilder', 'get_link_add_new' ) ) {
					$add_new_link = \LearnPress\CourseBuilder\CourseBuilder::get_link_add_new( 'certificates' );
				} elseif ( method_exists( '\LearnPress\CourseBuilder\CourseBuilder', 'get_link_course_builder' ) ) {
					$add_new_link = \LearnPress\CourseBuilder\CourseBuilder::get_link_course_builder( 'certificates/create' );
				}
			}

			$response->status  = 'success';
			$response->message = __( 'Certificate saved as draft', 'learnpress-certificates' );
			$response->data    = array(
				'cert_id'      => $cert_id,
				'edit_link'    => $edit_link,
				'add_new_link' => $add_new_link,
			);
		} catch ( Throwable $e ) {
			$response->status  = 'error';
			$response->message = $e->getMessage();
		}

		wp_send_json( $response );
	}

	public static function change_status_certificate() {
		$response       = new LP_REST_Response();
		$response->data = new \stdClass();

		try {
			$data              = self::check_valid();
			$cert_id           = absint( $data['certificate_id'] ?? 0 );
			$is_course_builder = ! empty( $data['is_course_builder'] );
			$status            = sanitize_text_field( $data['status'] ?? 'trash' );

			$post = get_post( $cert_id );
			if ( ! $post || $post->post_type !== LP_ADDON_CERTIFICATES_CERT_CPT ) {
				throw new Exception( __( 'Certificate not found', 'learnpress-certificates' ) );
			}

			if ( absint( $post->post_author ) !== get_current_user_id() && ! current_user_can( 'manage_options' ) ) {
				throw new Exception( __( 'You are not allowed to perform this action', 'learnpress-certificates' ) );
			}

			if ( $status === 'trash' ) {
				$result = wp_trash_post( $cert_id );
				if ( ! $result ) {
					throw new Exception( __( 'Cannot move this certificate to trash', 'learnpress-certificates' ) );
				}
				$message = __( 'Certificate moved to trash', 'learnpress-certificates' );
			} elseif ( $status === 'delete' ) {
				$result = wp_delete_post( $cert_id, true );
				if ( ! $result ) {
					throw new Exception( __( 'Cannot delete this certificate', 'learnpress-certificates' ) );
				}
				$message = __( 'Certificate deleted permanently', 'learnpress-certificates' );
			} elseif ( $status === 'publish' ) {
				$result = wp_update_post(
					[
						'ID'          => $cert_id,
						'post_status' => 'publish',
					]
				);
				if ( ! $result ) {
					throw new Exception( __( 'Cannot publish this certificate', 'learnpress-certificates' ) );
				}
				$message = __( 'Certificate published', 'learnpress-certificates' );
			} else {
				throw new Exception( __( 'Invalid status', 'learnpress-certificates' ) );
			}

			$response->data->status = $status;
			$response->status       = 'success';
			$response->message      = $message;
		} catch ( Throwable $e ) {
			$response->status  = 'error';
			$response->message = $e->getMessage();
		}

		wp_send_json( $response );
	}

	public static function check_local_image() {
		$response = new LP_REST_Response();

		try {
			if ( ! current_user_can( 'edit_' . LP_ADDON_CERTIFICATES_CERT_CPT . 's' ) ) {
				throw new \Exception( __( 'Permission denied', 'learnpress-certificates' ) );
			}

			$params = wp_unslash( $_REQUEST['data'] ?? '' );
			try {
				$data = LP_Helper::json_decode( $params, true );
			} catch ( \Exception $e ) {
				$data = array();
			}

			$url  = esc_url_raw( $data['url'] ?? '' );
			$path = '';

			if ( $url ) {
				$uploads    = wp_upload_dir();
				$plugin_url = plugins_url( '', LP_ADDON_CERTIFICATES_FILE );

				if ( 0 === strncmp( $url, $uploads['baseurl'], strlen( $uploads['baseurl'] ) ) ) {
					$path = $uploads['basedir'] . substr( $url, strlen( $uploads['baseurl'] ) );
				} elseif ( 0 === strncmp( $url, $plugin_url, strlen( $plugin_url ) ) ) {
					$path = LP_ADDON_CERTIFICATES_PATH . substr( $url, strlen( $plugin_url ) );
				}
			}

			$missing = $path && ( ! file_exists( $path ) || ! is_readable( $path ) );

			$response->status = 'success';
			$response->data   = array( 'status' => $missing ? 'missing' : 'exists' );
		} catch ( \Throwable $e ) {
			$response->message = $e->getMessage();
		}

		wp_send_json( $response );
	}

	/**
	 * Duplicate certificate
	 *
	 * @since 4.2.3
	 * @version 1.0.0
	 * @return void
	 */
	public static function duplicate_certificate() {
		$response = new LP_REST_Response();

		try {
			$data              = self::check_valid();
			$cert_id           = absint( $data['certificate_id'] ?? 0 );
			$is_course_builder = absint( $data['is_course_builder'] ?? 0 );

			$certificatePostModel = CertificatePostModel::find( $cert_id, true );
			if ( ! $certificatePostModel ) {
				throw new Exception( __( 'Certificate not found', 'learnpress-certificates' ) );
			}

			$certificatePostModelNew = CertificateService::instance()->duplicate( $certificatePostModel );

			// Redirect to edit certificate
			if ( $is_course_builder ) {
				$response->data->redirect_url = CourseBuilder::get_link_course_builder(
					CBCertificateTemplate::MENU_CERTIFICATES . "/{$certificatePostModelNew->get_id()}"
				);
			}

			$response->status  = 'success';
			$response->message = __( 'Certificate duplicated successfully', 'learnpress-certificates' );
		} catch ( Throwable $e ) {
			$response->message = $e->getMessage();
		}

		wp_send_json( $response );
	}
}
