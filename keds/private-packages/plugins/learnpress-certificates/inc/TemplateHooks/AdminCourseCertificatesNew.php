<?php
namespace LearnPress\Certificate\TemplateHooks;

use LearnPress\Certificate\Models\CertificatePostModel;
use LearnPress\Certificate\Models\CourseCertificateInfo;
use LearnPress\CourseBuilder\CourseBuilder;
use LearnPress\Models\CourseModel;
use LP_Certificate;
use LP_Certificate_DB;
use LP_Certificate_Filter;
use Throwable;

class AdminCourseCertificatesNew {

	const META_KEY_COURSE_CERT_INFO = 'course_certificate_info';

	/**
	 * @throws \Exception
	 */
	public static function render( $post ) {
		$course_id = $post->ID;

		wp_enqueue_style( 'lp-certificate-builder-css' );

		$courseModel = CourseModel::find( $course_id, true );
		if ( ! $courseModel ) {
			return;
		}

		$courseCerModel = new CourseCertificateInfo( $courseModel );

		try {
			$cert_id_of_course = LP_Certificate::get_course_certificate( $course_id );
			$limit             = $cert_id_of_course ? 4 : 5;
			$total_cer         = 0;

			$filter              = new LP_Certificate_Filter();
			$filter->limit       = $limit > 0 ? $limit : -1;
			$filter->page        = 1;
			$filter->only_fields = [ 'ID', 'post_title', 'post_date' ];
			$filter->order_by    = 'cer.post_date';

			if ( $cert_id_of_course ) {
				$filter->where[] = LP_Certificate_DB::getInstance()->wpdb->prepare( 'AND cer.ID != %d', $cert_id_of_course );
			}

			$certificates = LP_Certificate::query_certificates( $filter, $total_cer );

			$all_certs = [];
			if ( $cert_id_of_course ) {
				$active      = new \stdClass();
				$active->ID  = $cert_id_of_course;
				$all_certs[] = $active;
			}
			foreach ( $certificates as $cert ) {
				$all_certs[] = $cert;
			}
		} catch ( Throwable $e ) {
			echo '<p>' . esc_html( $e->getMessage() ) . '</p>';
			return;
		}

		$has_more        = $total_cer > $limit;
		$default_thumb   = plugins_url( '/assets/images/no-image.png', LP_ADDON_CERTIFICATES_FILE );
		$upload_icon_url = plugins_url( '/assets/images/svg/image-cer-course-builder.svg', LP_ADDON_CERTIFICATES_FILE );
		$info            = $courseCerModel->get_certificate_info();
		$has_cert        = ( new CourseCertificateInfo( [ 'ID' => $course_id ] ) )->get_cert_post_model();
		$is_enabled      = $has_cert && (int) ( $info['enable'] ?? 0 ) === 1;

		wp_enqueue_media();

		$min   = \LP_Debug::is_debug() ? '' : '.min';
		$addon = \LP_Addon_Certificates::instance();
		wp_register_style(
			'lp-cert-admin-course-css',
			$addon->get_plugin_url( "assets/dist/css/admin-course-certificates{$min}.css" ),
			[],
			LP_ADDON_CERTIFICATES_VER
		);
		wp_register_script(
			'lp-cert-admin-course-js',
			$addon->get_plugin_url( "assets/dist/js/backend/admin-edit-course-tab-cert{$min}.js" ),
			[ 'cert-confirm-js' ],
			LP_ADDON_CERTIFICATES_VER,
			[ 'in_footer' => true ]
		);
		wp_enqueue_style( 'lp-cert-admin-course-css' );
		wp_enqueue_script( 'lp-cert-admin-course-js' );
		?>
		<div class="lp-course-cert-browser-new"
			data-course-id="<?php echo esc_attr( $course_id ); ?>"
			data-is-course-builder="<?php echo esc_attr( lp_cert_is_course_builder() ? 1 : 0 ); ?>">

			<div class="themes wp-clearfix lp-certificates lp-certificates-new">
				<?php
				foreach ( $all_certs as $cert_obj ) :
					$id         = (int) $cert_obj->ID;
					$cert_post  = get_post( $id );
					$is_active  = ( $id === (int) $cert_id_of_course );
					$thumb_rel  = get_post_meta( $id, '_lp_cert_thumbnail', true );
					$thumb_url  = ! empty( $thumb_rel ) ? home_url( $thumb_rel ) : $default_thumb;
					$cert_title = $cert_post ? $cert_post->post_title : __( 'Certificate', 'learnpress-certificates' );
					$certModel  = CertificatePostModel::find( $id, true );
					$edit_link  = $certModel ? $certModel->get_edit_link() : admin_url( 'post.php?post=' . $id . '&action=edit' );
					?>
					<div class="theme<?php echo $is_active ? ' active' : ''; ?>"
						data-id="<?php echo esc_attr( $id ); ?>">
						<div class="theme-screenshot">
							<img src="<?php echo esc_url( $thumb_url ); ?>"
								alt="<?php echo esc_attr( $cert_title ); ?>" />
						</div>
						<div class="theme-id-container">
							<h2 class="theme-name">
								<?php echo esc_html( $cert_title ); ?>
							</h2>
						<div class="theme-actions">
							<a class="button button-primary button-remove-certificate lp-button"
								href="#"
								data-popup-title="<?php esc_attr_e( 'Remove assigned certificate?', 'learnpress-certificates' ); ?>"
								data-popup-text="<?php esc_attr_e( 'Removing this certificate will also turn off the course detail showcase section.', 'learnpress-certificates' ); ?>"
								data-popup-confirm="<?php esc_attr_e( 'Remove', 'learnpress-certificates' ); ?>"
								data-popup-cancel="<?php esc_attr_e( 'Cancel', 'learnpress-certificates' ); ?>">
								<?php esc_html_e( 'Remove', 'learnpress-certificates' ); ?>
							</a>
							<a class="button button-primary button-assign-certificate lp-button" href="#">
								<?php esc_html_e( 'Assign', 'learnpress-certificates' ); ?>
							</a>
							<a class="button" target="_blank" href="<?php echo esc_url( $edit_link ); ?>">
								<?php esc_html_e( 'Edit', 'learnpress-certificates' ); ?>
							</a>
						</div>
						</div>
					</div>
				<?php endforeach; ?>

				<div class="theme add-new-theme">
					<?php
					$add_new_link = admin_url( 'post-new.php?post_type=' . LP_ADDON_CERTIFICATES_CERT_CPT );
					if ( lp_cert_is_course_builder() ) {
						if ( class_exists( CourseBuilder::class ) && method_exists( CourseBuilder::class, 'get_link_add_new' ) ) {
							$add_new_link = CourseBuilder::get_link_add_new( 'certificates' );
						} elseif ( class_exists( CourseBuilder::class ) && method_exists( CourseBuilder::class, 'get_link_course_builder' ) ) {
							$add_new_link = CourseBuilder::get_link_course_builder( 'certificates/create' );
						}
					}
					?>
					<a target="_blank" href="<?php echo esc_url( $add_new_link ); ?>">
						<div class="theme-screenshot"><span></span></div>
						<h2 class="theme-name"><?php esc_html_e( 'Add new Certificate', 'learnpress-certificates' ); ?></h2>
					</a>
				</div>
			</div>

			<?php if ( $has_more ) : ?>
				<div class="lp-cert-load-more-wrap">
					<button class="button button-primary lp-cert-primary-btn lp-cer-btn-load-more-new"
							data-offset="<?php echo esc_attr( $limit ); ?>"
							data-course-id="<?php echo esc_attr( $course_id ); ?>"
							data-cert-active="<?php echo esc_attr( $cert_id_of_course ); ?>">
						<?php esc_html_e( 'Load more', 'learnpress-certificates' ); ?>
					</button>
				</div>
			<?php endif; ?>


		<hr class="lp-cert-course-info__separator" />

		<div class="lp-cert-course-info">
			<?php wp_nonce_field( 'lp_cert_course_info_save', 'lp_cert_course_info_nonce' ); ?>

			<div class="lp-cert-course-info__row lp-cert-course-info__row--toggle">
				<input type="checkbox"
						name="lp_cert_info_enable"
						id="lp_cert_info_enable"
						value="1"
						<?php checked( $is_enabled ); ?>
						<?php disabled( ! $has_cert ); ?> />
				<label for="lp_cert_info_enable" class="lp-cert-toggle-text">
					<?php esc_html_e( 'Allow showcase certificate on course detail page', 'learnpress-certificates' ); ?>
				</label>
				<div class="lp-cert-toggle-text__sub">
					<?php esc_html_e( 'Students can see what certificate they receive upon course completion.', 'learnpress-certificates' ); ?>
				</div>
			</div>

			<div class="lp-cert-course-info__row<?php echo ! $is_enabled ? ' lp-option-disabled' : ''; ?>" id="lp-cert-info-row-image">
				<div class="lp-cert-course-info__label">
					<?php esc_html_e( 'Certificate image', 'learnpress-certificates' ); ?>
				</div>
				<div class="lp-cert-course-info__field">
					<input type="hidden"
							id="lp_cert_info_image"
							name="lp_cert_info_image"
							value="<?php echo esc_attr( $info['image'] ?? '' ); ?>" />
					<div class="lp-cert-info-upload-wrap" id="lp-cert-info-upload-wrap"
						data-upload-icon-url="<?php echo esc_attr( $upload_icon_url ); ?>"
						data-popup-title="<?php esc_attr_e( 'Are you sure?', 'learnpress-certificates' ); ?>"
						data-popup-text="<?php esc_attr_e( 'This image will be removed.', 'learnpress-certificates' ); ?>"
						data-popup-confirm="<?php esc_attr_e( 'Yes', 'learnpress-certificates' ); ?>"
						data-popup-cancel="<?php esc_attr_e( 'Cancel', 'learnpress-certificates' ); ?>"
						data-text-click-to-upload="<?php esc_attr_e( 'Click to upload', 'learnpress-certificates' ); ?>"
						data-text-or-drag-drop="<?php esc_attr_e( 'or drag and drop', 'learnpress-certificates' ); ?>"
						data-text-jpg-png="<?php esc_attr_e( 'JPG, JPEG, PNG less than 1MB', 'learnpress-certificates' ); ?>"
						data-text-select-image="<?php esc_attr_e( 'Select Image', 'learnpress-certificates' ); ?>"
						data-text-use-this-image="<?php esc_attr_e( 'Use this image', 'learnpress-certificates' ); ?>"
						data-text-remove-image-aria="<?php esc_attr_e( 'Remove image', 'learnpress-certificates' ); ?>">
						<div class="lp-cert-info-upload-area" id="lp-cert-info-upload-area">
							<?php if ( ! empty( $info['image'] ?? '' ) ) : ?>
								<img src="<?php echo esc_url( $info['image'] ?? '' ); ?>" alt="" />
								<button type="button" class="lp-cert-info-remove-image" aria-label="<?php esc_attr_e( 'Remove image', 'learnpress-certificates' ); ?>">&times;</button>
							<?php else : ?>
								<div class="lp-cert-info-upload-area__placeholder">
									<img class="lp-cert-info-upload-area__icon"
										src="<?php echo esc_url( $upload_icon_url ); ?>"
										alt="" />
									<div class="lp-cert-info-upload-area__text">
										<span class="lp-cert-info-upload-area__link"><?php esc_html_e( 'Click to upload', 'learnpress-certificates' ); ?></span>
										<span class="lp-cert-info-upload-area__drag"><?php esc_html_e( 'or drag and drop', 'learnpress-certificates' ); ?></span>
									</div>
									<div class="lp-cert-info-upload-area__hint">
										<?php esc_html_e( 'JPG, JPEG, PNG less than 1MB', 'learnpress-certificates' ); ?>
									</div>
								</div>
							<?php endif; ?>
						</div>
					</div>
				</div>
			</div>

			<div class="lp-cert-course-info__row<?php echo ! $is_enabled ? ' lp-option-disabled' : ''; ?>" id="lp-cert-info-row-title">
				<div class="lp-cert-course-info__label">
					<label for="lp_cert_info_title"><?php esc_html_e( 'Title', 'learnpress-certificates' ); ?></label>
				</div>
				<div class="lp-cert-course-info__field">
					<input type="text"
							id="lp_cert_info_title"
							name="lp_cert_info_title"
							value="<?php echo esc_attr( $info['title'] ?? '' ); ?>"
							placeholder="<?php esc_attr_e( 'Earn a Certificate & Showcase Your Skills', 'learnpress-certificates' ); ?>" />
					<div class="lp-cert-course-info__hint">
						<?php esc_html_e( 'Title for preview certificate.', 'learnpress-certificates' ); ?>
					</div>
				</div>
			</div>

			<div class="lp-cert-course-info__row<?php echo ! $is_enabled ? ' lp-option-disabled' : ''; ?>" id="lp-cert-info-row-description">
				<div class="lp-cert-course-info__label">
					<label for="lp_cert_info_description"><?php esc_html_e( 'Description', 'learnpress-certificates' ); ?></label>
				</div>
				<div class="lp-cert-course-info__field">
					<textarea id="lp_cert_info_description"
								name="lp_cert_info_description"
								placeholder="<?php esc_attr_e( 'Complete this course and earn a verified certificate to showcase your new skills. Share it on LinkedIn, add it to your resume, or download it for your records.', 'learnpress-certificates' ); ?>"><?php echo esc_textarea( $info['description'] ?? '' ); ?></textarea>
					<div class="lp-cert-course-info__hint">
						<?php esc_html_e( 'Description for preview certificate.', 'learnpress-certificates' ); ?>
					</div>
				</div>
			</div>

			<div class="lp-cert-course-info__row" id="lp-cert-info-row-save">
				<div class="lp-cert-course-info__label"></div>
				<div class="lp-cert-course-info__field">
					<div class="lp-cert-info-actions">
						<button type="button" class="button button-primary lp-cert-primary-btn"
							id="lp-cert-info-save-btn"
							data-text-saving="<?php esc_attr_e( 'Saving...', 'learnpress-certificates' ); ?>"
							data-text-title-required="<?php esc_attr_e( 'Title is required', 'learnpress-certificates' ); ?>"
							data-text-saved="<?php esc_attr_e( 'Certificate settings have been saved successfully.', 'learnpress-certificates' ); ?>"
							data-text-error-save="<?php esc_attr_e( 'Error saving certificate settings.', 'learnpress-certificates' ); ?>">
							<?php esc_html_e( 'Save', 'learnpress-certificates' ); ?>
						</button>
					</div>
				</div>
			</div>
		</div>
		</div>
		<?php
	}

	private static function get_submitted_course_cert_info(): array {
		$action = ! empty( $_REQUEST['lp-load-ajax'] ) ? sanitize_key( wp_unslash( $_REQUEST['lp-load-ajax'] ) ) : '';

		if ( $action ) {
			if ( ! in_array( $action, [ 'save_courses', 'save_course_settings' ], true ) ) {
				return [];
			}

			$nonce = sanitize_text_field( wp_unslash( $_REQUEST['nonce'] ?? '' ) );
			if ( ! wp_verify_nonce( $nonce, 'wp_rest' ) ) {
				return [];
			}

			$raw_data = wp_unslash( $_REQUEST['data'] ?? '' );
			try {
				if ( is_string( $raw_data ) && class_exists( '\LP_Helper' ) && method_exists( '\LP_Helper', 'json_decode' ) ) {
					$data = \LP_Helper::json_decode( $raw_data, true );
				} elseif ( is_string( $raw_data ) ) {
					$data = json_decode( $raw_data, true );
					if ( json_last_error() !== JSON_ERROR_NONE ) {
						return [];
					}
				} else {
					$data = [];
				}
			} catch ( \Exception $e ) {
				return [];
			}
			if ( ! is_array( $data ) ) {
				return [];
			}
		} else {
			if ( ! isset( $_POST['lp_cert_course_info_nonce'] ) ) {
				return [];
			}

			if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['lp_cert_course_info_nonce'] ) ), 'lp_cert_course_info_save' ) ) {
				return [];
			}

			$data = wp_unslash( $_POST );
		}

		if ( ! isset( $data['lp_cert_info_title'], $data['lp_cert_info_image'], $data['lp_cert_info_description'] ) ) {
			return [];
		}

		$enable_raw = sanitize_text_field( $data['lp_cert_info_enable'] ?? '' );

		return [
			'enable'      => in_array( $enable_raw, [ '1', 'yes', 'on' ], true ) ? 1 : 0,
			'image'       => esc_url_raw( $data['lp_cert_info_image'] ?? '' ),
			'title'       => sanitize_text_field( $data['lp_cert_info_title'] ?? '' ),
			'description' => sanitize_textarea_field( $data['lp_cert_info_description'] ?? '' ),
		];
	}

	public static function save( int $course_id ) {
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $course_id ) ) {
			return;
		}

		$info = self::get_submitted_course_cert_info();
		if ( empty( $info ) ) {
			return;
		}

		$has_cert = ( new CourseCertificateInfo( [ 'ID' => $course_id ] ) )->get_cert_post_model();

		if ( $info['enable'] && ( ! $has_cert || '' === trim( $info['title'] ) ) ) {
			$info['enable'] = 0;
		}

		update_post_meta( $course_id, self::META_KEY_COURSE_CERT_INFO, wp_json_encode( $info, JSON_UNESCAPED_UNICODE ) );
	}
}
