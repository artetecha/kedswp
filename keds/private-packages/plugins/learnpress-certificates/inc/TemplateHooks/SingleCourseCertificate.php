<?php
namespace LearnPress\Certificate\TemplateHooks;

defined( 'ABSPATH' ) || exit;

use Exception;
use LearnPress\Certificate\Models\CourseCertificateInfo;
use LearnPress\Certificate\Upgrade\CertificateUpdater;
use LearnPress\Helpers\Template;
use LearnPress\Models\CourseModel;
use LP_Page_Controller;

/**
 * Class SingleCourseCertificate
 *
 * Display certificate info on the frontend single course.
 *
 * @since 4.2.3
 * @version 1.0.1
 */
class SingleCourseCertificate {

	public static function init() {
		if ( CertificateUpdater::get_current_db_version() === 1 ) {
			return;
		}

		add_filter( 'learn-press/single-course/modern/section-instructor', [ __CLASS__, 'add_before_instructor' ], 10, 2 );

		add_filter( 'learn-press/course-tabs', [ __CLASS__, 'add_classic_tab' ], 10000, 2 );
	}

	/**
	 * @param array $section
	 * @param CourseModel $courseModel
	 *
	 * @return array
	 * @throws \Exception
	 */
	public static function add_before_instructor( array $section, $courseModel ): array {
		if ( ! LP_Page_Controller::is_page_single_course() ) {
			return $section;
		}

		$courseCerModel = new CourseCertificateInfo( $courseModel );
		if ( ! $courseCerModel->is_enabled() ) {
			return $section;
		}

		return Template::insert_value_to_position_array(
			$section,
			'before',
			'wrapper',
			'lp_certificate',
			self::html( $courseCerModel )
		);
	}

	/**
	 * Show certificate info in tab on single course classic layout.
	 *
	 * @param array $tabs
	 * @param CourseModel $courseModel
	 *
	 * @return array
	 * @throws Exception
	 */
	public static function add_classic_tab( array $tabs, $courseModel ): array {
		$courseCerModel = new CourseCertificateInfo( $courseModel );
		if ( ! $courseCerModel->is_enabled() ) {
			return $tabs;
		}

		$tabs['lp_certificate'] = [
			'title'    => esc_html__( 'Certificate', 'learnpress-certificates' ),
			'priority' => 35,
			'icon'     => 'edu-certificate',
			'callback' => function () use ( $courseCerModel ) {
				echo self::html( $courseCerModel );
			},
		];

		return $tabs;
	}

	/**
	 * @param CourseCertificateInfo $courseCerModel
	 *
	 * @throws Exception
	 */
	public static function html( $courseCerModel ): string {
		$img_url = $courseCerModel->get_cert_image_url();
		$info    = $courseCerModel->get_certificate_info();
		$title   = $info['title'] ?? '';
		$desc    = $info['description'] ?? '';

		if ( empty( $title ) ) {
			$title = esc_html__( 'Showcase Your Certificate', 'learnpress-certificates' );
		}

		$desc_html = '';
		if ( ! empty( $desc ) ) {
			$desc_html = sprintf(
				'<p class="lp-course-certificate-section__desc">%s</p>',
				esc_html( $desc )
			);
		}

		$section = apply_filters(
			'learn-press/course/certificate-html',
			[
				'wrapper'     => '<div class="lp-course-certificate-section">',
				'heading'     => sprintf(
					'<h3 class="lp-course-certificate-section__heading section-title">%s</h3>',
					esc_html__( 'Certificate', 'learnpress-certificates' )
				),
				'card'        => '<div class="lp-course-certificate-section__card">',
				'image'       => sprintf(
					'<div class="lp-course-certificate-section__image"><img src="%s" alt="%s" /></div>',
					esc_url( $img_url ),
					esc_attr( $title )
				),
				'body'        => '<div class="lp-course-certificate-section__body">',
				'title'       => sprintf(
					'<h4 class="lp-course-certificate-section__title">%s</h4>',
					esc_html( $title )
				),
				'description' => $desc_html,
				'body_end'    => '</div>',
				'card_end'    => '</div>',
				'wrapper_end' => '</div>',
			],
			$courseCerModel
		);

		return Template::combine_components( $section );
	}
}
