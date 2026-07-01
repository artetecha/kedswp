<?php
namespace LearnPress\Certificate\TemplateHooks\CourseBuilder;

defined( 'ABSPATH' ) || exit;

use Exception;
use LearnPress\Certificate\Models\CertificatePostModel;
use LearnPress\Certificate\Services\CertificateService;
use LearnPress\Certificate\TemplateHooks\AdminCourseCertificatesNew;
use LearnPress\CourseBuilder\CourseBuilder;
use LearnPress\Models\PostModel;
use LearnPress\Models\UserModel;
use LP_Addon_Certificates;
use LP_WP_Filesystem;

/**
 * Class CBCertificateTemplate
 *
 * Template manager Certificate display on Course Builder screen
 */
class CBCertificateTemplate {
	private static $instance;

	const MENU_CERTIFICATES = 'certificates';

	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	public function __construct() {
		$this->init();
	}

	public function init() {
		add_action( 'learn-press/after-enqueue-scripts', [ $this, 'register_assets_for_cb' ] );
		add_filter( 'learn-press/course-builder/menus', [ $this, 'add_menu_item' ] );
		add_action( 'learn-press/course-builder/certificates/layout', [ $this, 'layout' ], 10 );
		// Check create certificate default draft if access link create, set priority -2 before hook return template on LearnPress.
		add_action( 'template_redirect', [ $this, 'check_create_cer_default_draft' ], -2 );
		// setting tab course - course-builder
		add_filter( 'learn-press/course-builder/edit-course/settings/tabs', [ $this, 'add_settings_tab_certificates' ] );
		add_action( 'learn-press/course-builder/edit-course/settings/tab/content', [ $this, 'course_builder_cert_tab_content' ] );
	}

	// Register assets for manager certificate on Course Builder screen
	public function register_assets_for_cb() {
		LP_Addon_Certificates::instance()->register_assets();

		$this->enqueue_course_builder_cert_css();
	}

	/**
	 * Add menu to course builder screen
	 *
	 * @param array $menus
	 *
	 * @return array
	 * @since 4.2.3
	 * @version 1.0.0
	 */
	public function add_menu_item( array $menus ): array {
		$icon = LP_WP_Filesystem::instance()->file_get_contents(
			LP_Addon_Certificates::instance()->get_plugin_url( 'assets/images/svg/title.svg' )
		);

		$menus['certificates'] = [
			'title'    => esc_html__( 'Certificates', 'learnpress-certificates' ),
			'slug'     => 'certificates',
			'icon'     => $icon,
			'sub_menu' => [],
			'priority' => 45,
		];

		return $menus;
	}

	public function add_settings_tab_certificates( array $tabs ): array {
		$tabs['certificates'] = [
			'label'    => esc_html__( 'Certificates', 'learnpress-certificates' ),
			'target'   => 'certificate-browser',
			'icon'     => 'dashicons-welcome-learn-more',
			'priority' => 60,
		];

		return $tabs;
	}

	public function course_builder_cert_tab_content( $post ) {
		if ( ! $post instanceof \WP_Post ) {
			$post = get_post( $post );
		}

		if ( ! $post ) {
			return;
		}

		echo '<div id="certificate-browser" class="wp-core-ui theme-browser lp-meta-box-course-panels">';
		AdminCourseCertificatesNew::render( $post );
		echo '</div>';
	}

	public function enqueue_course_builder_cert_css() {
		if ( ! lp_cert_is_course_builder() ) {
			return;
		}

		//wp_enqueue_style( 'learnpress-wp-themes-css' );
		wp_enqueue_style( 'learnpress-admin-certificates-css' );
		wp_enqueue_style( 'lp-certificate-builder-css' );
		wp_enqueue_style( 'lp-cert-builder-tab-editor' );
		wp_enqueue_script( 'cert-confirm-js' );
		wp_enqueue_script( 'lp-cert-builder-tab' );
		wp_enqueue_style( 'lp-certificate-builder-css' );
		wp_enqueue_script( 'edit-certificate-js' );
	}

	/**
	 * Layout manager certificate on Course Builder screen
	 *
	 * @return void
	 */
	public function layout( array $data = [] ) {
		$userModel = $data['userModel'] ?? false;
		if ( ! $userModel instanceof UserModel ) {
			return;
		}

		$certificate_id = (int) CourseBuilder::get_item_id();
		$html           = '';

		if ( ! empty( $certificate_id ) ) {
			// Edit certificate
			$data['item_id'] = $certificate_id;
			$html            = CBEditCertificateTemplate::instance()->layout( $data );
		} else {
			$html = CBListCertificatesTemplate::instance()->layout( $data );
		}

		echo $html;
	}

	/**
	 * Check if is link create, will create certificate default draft
	 *
	 * @since 4.2.3
	 * @version 1.0.0
	 * @return void
	 * @throws Exception
	 */
	public function check_create_cer_default_draft() {
		// Check permission
		$user_id   = get_current_user_id();
		$userModel = UserModel::find( $user_id, true );
		if ( ! $userModel || ! $userModel->is_instructor() ) {
			return;
		}

		$menu_current = CourseBuilder::get_menu_current();
		if ( $menu_current != self::MENU_CERTIFICATES
			|| CourseBuilder::get_item_id() != CourseBuilder::POST_NEW ) {
			return;
		}

		$cerNew = new CertificatePostModel();
		if ( ! $cerNew->check_capabilities_create() ) {
			return;
		}

		// Create cer with status Draft then redirect to edit page
		$data_new        = [
			'post_title'  => esc_html__( 'Certificate new', 'learnpress-certificates' ),
			'post_status' => PostModel::STATUS_DRAFT,
		];
		$cerPostModelNew = CertificateService::instance()->create( $data_new );
		$link            = CourseBuilder::get_link_course_builder( self::MENU_CERTIFICATES . "/{$cerPostModelNew->get_id()}" );
		wp_redirect( $link );
		die();
	}
}
