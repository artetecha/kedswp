<?php
namespace LearnPress\Certificate\TemplateHooks\CourseBuilder;

defined( 'ABSPATH' ) || exit;

use Exception;
use LearnPress\Certificate\Models\CertificatePostModel;
use LearnPress\CourseBuilder\CourseBuilder;
use LearnPress\CourseBuilder\CourseBuilderAccessPolicy;
use LearnPress\Helpers\Singleton;
use LearnPress\Helpers\Template;
use LearnPress\Models\CourseModel;
use LearnPress\Models\CoursePostModel;
use LearnPress\Models\PostModel;
use LP_Addon_Certificates;
use Throwable;

/**
 * Template hooks Course Builder.
 *
 * @since 4.3.0
 * @version 1.0.0
 */
class CBEditCertificateTemplate {
	use Singleton;

	public function init() {}

	public function layout( array $data = [] ) {
		ob_start();
		$certificate_id = $data['item_id'] ?? 0;
		$this->html_certificate_editor( $certificate_id );
		return ob_get_clean();
	}

	private function get_tab_link( string $tab, $post_id = null ): string {
		if ( ! class_exists( CourseBuilder::class ) || ! method_exists( CourseBuilder::class, 'get_tab_link' ) ) {
			return '';
		}
		return CourseBuilder::get_tab_link( $tab, $post_id );
	}

	private function is_post_new( $post_id ): bool {
		if ( ! class_exists( CourseBuilder::class ) ) {
			return false;
		}

		if ( ! defined( CourseBuilder::class . '::POST_NEW' ) ) {
			return false;
		}

		return (string) $post_id === (string) constant( CourseBuilder::class . '::POST_NEW' );
	}

	private function html_certificate_editor( int $cert_id ) {
		try {
			$certificatePostModel = CertificatePostModel::find( $cert_id, true );
			if ( ! $certificatePostModel ) {
				throw new Exception( __( 'Certificate not found.', 'learnpress-certificates' ) );
			}

			$addon         = LP_Addon_Certificates::instance();
			$price         = $certificatePostModel->get_price();
			$thumbnail     = $certificatePostModel->get_thumbnail();
			$status        = $certificatePostModel->post_status;
			$cert_post     = get_post( $cert_id );
			$status_labels = [
				'draft'   => __( 'Draft', 'learnpress-certificates' ),
				'pending' => __( 'Pending Review', 'learnpress-certificates' ),
				'publish' => __( 'Published', 'learnpress-certificates' ),
				'future'  => __( 'Scheduled', 'learnpress-certificates' ),
				'private' => __( 'Private', 'learnpress-certificates' ),
			];

			wp_enqueue_media();

			$min = \LP_Debug::is_debug() ? '' : '.min';
			wp_register_script(
				'lp-cert-cb-editor-js',
				$addon->get_plugin_url( "assets/dist/js/backend/cb-certificate-editor{$min}.js" ),
				[ 'cert-confirm-js' ],
				LP_ADDON_CERTIFICATES_VER,
				[ 'in_footer' => true ]
			);
			wp_enqueue_script( 'lp-cert-cb-editor-js' );
			?>
			<?php
			$status_badge_class = 'cb-cert-status-badge--' . $status;
			$status_label_text  = $status === 'private'
				? esc_html__( 'Privately Published', 'learnpress-certificates' )
				: esc_html( $status_labels[ $status ] ?? $status );
			?>
			<div class="cb-cert-editor-header"
				data-cert-id="<?php echo esc_attr( $cert_id ); ?>"
				data-original-post-status="<?php echo esc_attr( $status ); ?>"
				data-text-error="<?php esc_attr_e( 'Error', 'learnpress-certificates' ); ?>"
				data-text-request-failed="<?php esc_attr_e( 'Request failed', 'learnpress-certificates' ); ?>">
				<div class="cb-cert-editor-header__left">
					<h3 class="cb-cert-title-text"
						data-text-placeholder-title="<?php esc_attr_e( 'Certificate Title', 'learnpress-certificates' ); ?>"><?php echo esc_html( $certificatePostModel->post_title ); ?></h3>
					<span class="cb-cert-status-badge <?php echo esc_attr( $status_badge_class ); ?>"
						data-label-publish="<?php esc_attr_e( 'Published', 'learnpress-certificates' ); ?>"
						data-label-draft="<?php esc_attr_e( 'Draft', 'learnpress-certificates' ); ?>"
						data-label-pending="<?php esc_attr_e( 'Pending Review', 'learnpress-certificates' ); ?>"
						data-label-future="<?php esc_attr_e( 'Scheduled', 'learnpress-certificates' ); ?>"
						data-label-private="<?php esc_attr_e( 'Privately Published', 'learnpress-certificates' ); ?>">
						<?php echo esc_html( $status_label_text ); ?>
					</span>
				</div>
				<div class="cb-cert-editor-header__right">
					<button type="button" class="cb-cert-header-btn cb-cert-header-btn--icon lp-btn-cert-undo" disabled title="<?php esc_attr_e( 'Undo', 'learnpress-certificates' ); ?>">
						<span class="lp-cert-icon-undo"></span>
					</button>
					<button type="button" class="cb-cert-header-btn cb-cert-header-btn--icon lp-btn-cert-redo" disabled title="<?php esc_attr_e( 'Redo', 'learnpress-certificates' ); ?>">
						<span class="lp-cert-icon-redo"></span>
					</button>
					<button type="button" class="cb-cert-header-btn cb-cert-header-btn--icon lp-btn-cert-builder-open-full-screen" title="<?php esc_attr_e( 'Fullscreen', 'learnpress-certificates' ); ?>">
						<span class="lp-cert-icon-fullscreen"></span>
					</button>
					<button type="button" class="cb-cert-header-btn lp-hidden lp-btn-cert-builder-close-full-screen">
						<span class="dashicons dashicons-no-alt"></span>
					</button>
					<button type="button" class="cb-cert-header-btn lp-btn-cert-preview">
						<?php esc_html_e( 'Preview', 'learnpress-certificates' ); ?>
					</button>
					<button type="button" class="lp-button cb-cert-header-btn cb-cert-header-btn--primary cb-cert-btn-publish"
						data-text-saving="<?php esc_attr_e( 'Saving...', 'learnpress-certificates' ); ?>"
						data-text-title-required="<?php esc_attr_e( 'Certificate title is required', 'learnpress-certificates' ); ?>"
						data-back-url="<?php echo esc_url( $this->get_tab_link( 'certificates' ) ); ?>">
						<?php esc_html_e( 'Update', 'learnpress-certificates' ); ?>
					</button>
					<div class="cb-cert-more-menu">
						<button type="button" class="cb-cert-header-btn cb-cert-header-btn--icon cb-cert-more-menu__trigger" title="<?php esc_attr_e( 'More', 'learnpress-certificates' ); ?>">
							<span class="dashicons dashicons-ellipsis"></span>
						</button>
						<div class="cb-cert-more-menu__dropdown">
							<button type="button"
									class="cb-cert-more-menu__item cb-cert-more-menu__item--danger submitdelete"
									data-cert-id="<?php echo esc_attr( $cert_id ); ?>"
									data-back-url="<?php echo esc_url( $this->get_tab_link( 'certificates' ) ); ?>"
									data-popup-native-confirm="<?php esc_attr_e( 'Are you sure you want to move this certificate to trash?', 'learnpress-certificates' ); ?>"
									data-popup-title="<?php esc_attr_e( 'Are you sure?', 'learnpress-certificates' ); ?>"
									data-popup-text="<?php esc_attr_e( 'This certificate will be moved to trash.', 'learnpress-certificates' ); ?>"
									data-popup-confirm="<?php esc_attr_e( 'Yes', 'learnpress-certificates' ); ?>"
									data-popup-cancel="<?php esc_attr_e( 'Cancel', 'learnpress-certificates' ); ?>">
								<?php esc_html_e( 'Move to trash', 'learnpress-certificates' ); ?>
							</button>
						</div>
					</div>
				</div>
			</div>

			<div class="cb-cert-editor-layout">
				<div class="cb-cert-editor-main">
					<input type="text" class="cb-cert-title-input" value="<?php echo esc_attr( $certificatePostModel->post_title ); ?>"
							placeholder="<?php esc_attr_e( 'Certificate title', 'learnpress-certificates' ); ?>"
							data-cert-id="<?php echo esc_attr( $cert_id ); ?>"
							data-text-placeholder-title="<?php esc_attr_e( 'Certificate Title', 'learnpress-certificates' ); ?>" />
					<div class="lp-certificate-edit-wrapper" data-cert-id="<?php echo esc_attr( $cert_id ); ?>">
						<div class="lp-certificate-edit-builder"
							data-status="<?php echo esc_attr( $status ); ?>"
							data-cert-id="<?php echo esc_attr( $cert_id ); ?>">
							<div class="lp-cert-builder-top-actions">
								<button type="button" class="cb-cert-header-btn cb-cert-header-btn--icon lp-btn-cert-undo" disabled title="<?php esc_attr_e( 'Undo', 'learnpress-certificates' ); ?>">
									<span class="lp-cert-icon-undo"></span>
								</button>
								<button type="button" class="cb-cert-header-btn cb-cert-header-btn--icon lp-btn-cert-redo" disabled title="<?php esc_attr_e( 'Redo', 'learnpress-certificates' ); ?>">
									<span class="lp-cert-icon-redo"></span>
								</button>
								<button type="button" class="cb-cert-header-btn lp-btn-cert-preview" title="<?php esc_attr_e( 'Preview', 'learnpress-certificates' ); ?>">
									<?php esc_html_e( 'Preview', 'learnpress-certificates' ); ?>
								</button>
								<button type="button" class="button button-primary lp-btn-cert-builder-open-full-screen">
									<span class="dashicons dashicons-editor-expand"></span>
									<?php esc_html_e( 'Fullscreen editor', 'learnpress-certificates' ); ?>
								</button>
								<button type="button" class="cb-cert-header-btn lp-hidden lp-btn-cert-builder-close-full-screen">
									<span class="dashicons dashicons-no-alt"></span>
								</button>
							</div>
							<?php
							$addon->get_admin_template(
								'certificate-template-selection.php',
								compact( 'certificatePostModel' )
							);
							$addon->get_admin_template(
								'edit-certificate-builder.php',
								compact( 'certificatePostModel' )
							);
							$addon->get_admin_template(
								'certificate-preview-modal.php'
							);
							?>
						</div>
					</div>
				</div>

				<div class="cb-cert-editor-sidebar">
					<?php
					if ( $status === 'private' ) {
						$visibility = 'private';
					} elseif ( ! empty( $cert_post->post_password ) ) {
						$visibility = 'password';
					} else {
						$visibility = 'public';
					}
					$visibility_labels_map = [
						'public'   => __( 'Public', 'learnpress-certificates' ),
						'private'  => __( 'Private', 'learnpress-certificates' ),
						'password' => __( 'Password protected', 'learnpress-certificates' ),
					];
					$visibility_label      = $visibility_labels_map[ $visibility ] ?? $visibility_labels_map['public'];
					$publish_date          = get_the_date( 'M j, Y \a\t H:i', $cert_id );
					$thumbnail_url         = ! empty( $thumbnail ) ? home_url( $thumbnail ) : '';
					?>
					<?php
					$cert_post_date = $cert_post->post_date;
					$date_mm        = mysql2date( 'm', $cert_post_date, false );
					$date_jj        = mysql2date( 'd', $cert_post_date, false );
					$date_aa        = mysql2date( 'Y', $cert_post_date, false );
					$date_hh        = mysql2date( 'H', $cert_post_date, false );
					$date_mn        = mysql2date( 'i', $cert_post_date, false );
					$date_ss        = mysql2date( 's', $cert_post_date, false );
					$is_published   = in_array( $status, [ 'publish', 'private', 'future' ], true );
					?>
					<div id="cb-submitdiv" class="postbox">
						<div class="postbox-header"><h2 class="hndle"><?php esc_html_e( 'Publish', 'learnpress-certificates' ); ?></h2></div>
						<div class="inside">
							<div class="cb-publish-row">
								<label class="cb-publish-row__label">
									<span class="lp-cert-icon-status"></span>
									<?php esc_html_e( 'Status', 'learnpress-certificates' ); ?>
								</label>
								<?php
								$is_future_date = strtotime( $cert_post->post_date_gmt ?? '' ) > time();
								$current_status = in_array( $status, [ 'auto-draft' ], true ) ? 'draft' : $status;
								?>
								<select id="cb-cert-status" class="cb-publish-row__select"
								<?php
								if ( $status === 'private' ) {
									echo ' disabled';}
								?>
								>
									<?php if ( $status === 'private' ) : ?>
										<option value="private" selected><?php esc_html_e( 'Privately Published', 'learnpress-certificates' ); ?></option>
										<?php
									else :
										$publish_label = $is_future_date
											? [ 'future', __( 'Scheduled', 'learnpress-certificates' ) ]
											: [ 'publish', __( 'Published', 'learnpress-certificates' ) ];
										?>
										<option value="<?php echo esc_attr( $publish_label[0] ); ?>" <?php selected( in_array( $current_status, [ 'publish', 'future' ], true ) ); ?>><?php echo esc_html( $publish_label[1] ); ?></option>
										<option value="draft" <?php selected( $current_status, 'draft' ); ?>><?php esc_html_e( 'Draft', 'learnpress-certificates' ); ?></option>
										<option value="pending" <?php selected( $current_status, 'pending' ); ?>><?php esc_html_e( 'Pending Review', 'learnpress-certificates' ); ?></option>
									<?php endif; ?>
								</select>
							</div>
							<div class="cb-publish-row">
								<label class="cb-publish-row__label">
									<span class="lp-cert-icon-visibility"></span>
									<?php esc_html_e( 'Visibility', 'learnpress-certificates' ); ?>
								</label>
								<select id="cb-cert-visibility" class="cb-publish-row__select">
									<option value="public" <?php selected( $visibility, 'public' ); ?>><?php esc_html_e( 'Public', 'learnpress-certificates' ); ?></option>
									<option value="password" <?php selected( $visibility, 'password' ); ?>><?php esc_html_e( 'Password protected', 'learnpress-certificates' ); ?></option>
									<option value="private" <?php selected( $visibility, 'private' ); ?>><?php esc_html_e( 'Private', 'learnpress-certificates' ); ?></option>
								</select>
							</div>
							<div id="password-visibility-field" class="cb-publish-row"<?php echo $visibility !== 'password' ? ' style="display:none;"' : ''; ?>>
								<label class="cb-publish-row__label"><?php esc_html_e( 'Password', 'learnpress-certificates' ); ?></label>
								<input type="text" id="cb-cert-post-password" class="cb-publish-row__input" value="<?php echo esc_attr( $cert_post->post_password ?? '' ); ?>" maxlength="255" />
							</div>
							<input type="hidden" id="mm" value="<?php echo esc_attr( $date_mm ); ?>" />
							<input type="hidden" id="jj" value="<?php echo esc_attr( $date_jj ); ?>" />
							<input type="hidden" id="aa" value="<?php echo esc_attr( $date_aa ); ?>" />
							<input type="hidden" id="hh" value="<?php echo esc_attr( $date_hh ); ?>" />
							<input type="hidden" id="mn" value="<?php echo esc_attr( $date_mn ); ?>" />
							<input type="hidden" id="ss" value="<?php echo esc_attr( $date_ss ); ?>" />
						</div>
					</div>

					<div class="postbox">
						<div class="postbox-header"><h2 class="hndle"><?php esc_html_e( 'Price', 'learnpress-certificates' ); ?></h2></div>
						<div class="inside">
							<div class="lp-cert-metabox-price">
								<input type="number" step="0.01" min="0" id="_lp_certificate_price"
										name="_lp_certificate_price" value="<?php echo esc_attr( $price ); ?>" class="widefat" />
								<p class="description"><?php esc_html_e( 'Set 0 for free certificate.', 'learnpress-certificates' ); ?></p>
							</div>
						</div>
					</div>

					<div class="postbox">
						<div class="postbox-header"><h2 class="hndle"><?php esc_html_e( 'Thumbnail', 'learnpress-certificates' ); ?></h2></div>
						<div class="inside">
							<div class="lp-cert-metabox-thumbnail"
								data-upload-icon-url="<?php echo esc_url( LP_Addon_Certificates::instance()->get_plugin_url( 'assets/images/svg/image-cer-course-builder.svg' ) ); ?>"
								data-text-select-thumbnail="<?php esc_attr_e( 'Select Thumbnail', 'learnpress-certificates' ); ?>"
								data-text-remove="<?php esc_attr_e( 'Remove', 'learnpress-certificates' ); ?>"
								data-text-click-to-upload="<?php esc_attr_e( 'Click to upload', 'learnpress-certificates' ); ?>"
								data-text-or-drag-drop="<?php esc_attr_e( 'or drag and drop', 'learnpress-certificates' ); ?>"
								data-text-jpg-png="<?php esc_attr_e( 'JPG, JPEG, PNG less than 1MB', 'learnpress-certificates' ); ?>">
								<input type="hidden" id="_lp_cert_thumbnail" name="_lp_cert_thumbnail"
										value="<?php echo esc_attr( $thumbnail_url ); ?>" />
								<?php if ( $thumbnail_url ) : ?>
									<div class="lp-cert-thumbnail-preview">
										<img src="<?php echo esc_url( $thumbnail_url ); ?>" alt="" />
										<button type="button" class="lp-cert-remove-thumbnail" title="<?php esc_attr_e( 'Remove', 'learnpress-certificates' ); ?>">&times;</button>
									</div>
								<?php else : ?>
									<div class="lp-cert-thumbnail-upload-area lp-cert-upload-thumbnail">
										<span class="upload-icon">
											<img src="<?php echo esc_url( LP_Addon_Certificates::instance()->get_plugin_url( 'assets/images/svg/image-cer-course-builder.svg' ) ); ?>" width="32" height="32" alt="" />
										</span>
										<p class="upload-text"><span class="upload-link"><?php esc_html_e( 'Click to upload', 'learnpress-certificates' ); ?></span> <?php esc_html_e( 'or drag and drop', 'learnpress-certificates' ); ?></p>
										<p class="upload-hint"><?php esc_html_e( 'JPG, JPEG, PNG less than 1MB', 'learnpress-certificates' ); ?></p>
									</div>
								<?php endif; ?>
							</div>
						</div>
					</div>
				</div>
			</div>

			<?php
		} catch ( Throwable $e ) {
			Template::print_message( $e->getMessage(), 'error' );
		}
	}
}
