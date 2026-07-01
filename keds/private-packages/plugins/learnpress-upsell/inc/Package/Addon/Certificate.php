<?php
/**
 * Handle with Addon certificate.
 *
 * @author nhamdv
 * @since 4.0.0
 * @version 1.0.0
 */
namespace LearnPress\Upsell\Package\Addon;

use LearnPress;
use LearnPress\Upsell\Package\Core_Functions;
use LP_Addon_Certificates;
use LP_Addon_Certificates_Preload;

class Certificate {

	protected static $instance = null;

	/**
	 * Certificate constructor.
	 */
	public function __construct() {
		add_action( 'wp_head', array( $this, 'header_google_fonts' ), 100 );
		add_action( 'wp_footer', array( $this, 'show_certificate_popup' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
	}

	public function enqueue_scripts() {
		if ( class_exists( 'LP_Addon_Certificates' ) && is_singular( LP_PACKAGE_CPT ) && $this->check_can_view_certificate() ) {
			LP_Addon_Certificates_Preload::$addon->wp_scripts();

			// What is this? I don't know. I just copy from LP_Addon_Certificates::wp_scripts()
			wp_enqueue_style( 'fontawesome-css' );
			wp_enqueue_style( 'certificates-css' );

			wp_enqueue_script( 'pdfjs' );
			wp_enqueue_script( 'fabric' );
			wp_enqueue_script( 'downloadjs' );
			wp_enqueue_script( 'certificates-js' );
		}
	}

	// Show google fonts in header use for render certificate.
	public function header_google_fonts() {
		if ( class_exists( 'LP_Addon_Certificates' ) && is_singular( LP_PACKAGE_CPT ) && $this->check_can_view_certificate() ) {
			LP_Addon_Certificates_Preload::$addon->header_google_fonts();
		}
	}

	public function show_certificate_popup() {
		if ( class_exists( 'LP_Addon_Certificates' ) && is_singular( LP_PACKAGE_CPT ) ) {
			$package_id = get_the_ID();

			$is_show = LearnPress::instance()->settings()->get( 'lp_cer_show_popup', 'yes' );

			// TODO: Check certificate is not free.

			// If setting is show certificate popup.
			if ( $is_show === 'yes' && $this->check_can_view_certificate() ) {
				$certificate = $this->get_certificate( $package_id );

				if ( ! $certificate ) {
					return;
				}

				$template_id = $certificate->get_uni_id();
				Core_Functions::instance()->get_template(
					'addons/certificate/popup-certificate.php',
					compact( 'certificate', 'template_id' )
				);
			}
		}
	}

	public function get_certificate( $package_id ) {
		if ( ! class_exists( '\LP_Certificate' ) ) {
			return false;
		}

		$user_id = get_current_user_id();
		$cert_id = get_post_meta( $package_id, '_lp_package_certificate', true );

		if ( ! $cert_id ) {
			return false;
		}

		$cert_key = \LP_Certificate::get_cert_key( $user_id, 0, $cert_id, false );

		update_option(
			"user_cert_{$cert_key}",
			array(
				'user_id'   => $user_id,
				'course_id' => 0,
				'cert_id'   => $cert_id,
			)
		);

		$certificate = \LP_Certificate::get_cert_by_key( $cert_key );

		if ( ! $certificate ) {
			return false;
		}

		return $certificate;
	}

	public function check_can_view_certificate() {
		if ( ! is_singular( LP_PACKAGE_CPT ) ) {
			return false;
		}

		$package_id = get_the_ID();

		if ( ! $package_id ) {
			return false;
		}

		$check_course = Core_Functions::instance()->check_all_courses_is_finished( $package_id );

		if ( ! $check_course ) {
			return false;
		}

		return true;
	}

	/**
	 * @return Certificate|null
	 */
	public static function instance() {
		if ( ! self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}
}
