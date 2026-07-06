<?php
namespace LearnPress\Certificate\TemplateHooks;

use LearnPress\Certificate\Models\CourseCertificateInfo;
use LearnPress\Models\CourseModel;

defined( 'ABSPATH' ) || exit;

class CourseCertificateBadge {

	public static function init() {
		add_filter( 'learn-press/single-course/modern/info-meta', [ __CLASS__, 'add_modern_info_meta' ], 20, 2 );
		add_filter( 'learn-press/single-course/classic/meta-secondary/sections', [ __CLASS__, 'add_classic_meta_secondary' ], 20 );
		add_action( 'learn-press/course-meta-secondary-left', [ __CLASS__, 'render_classic_meta_secondary' ], 30 );
	}

	public static function add_modern_info_meta( array $info_meta, $course ): array {
		$course_id = self::get_course_id( $course );
		if ( ! $course_id || ! self::should_display( $course_id ) ) {
			return $info_meta;
		}

		$info_meta['certificate'] = [
			'label' => sprintf(
				'%s%s',
				self::icon_html(),
				esc_html( self::label_text() )
			),
			'value' => '',
		];

		return $info_meta;
	}

	public static function add_classic_meta_secondary( array $sections ): array {
		$course_id = self::get_classic_course_id();
		if ( ! $course_id || ! self::should_display( $course_id ) ) {
			return $sections;
		}

		$html = sprintf(
			'<div class="meta-item meta-item-certificate">%s<span>%s</span></div>',
			self::icon_html(),
			esc_html( self::label_text() )
		);

		$new_item = [ 'certificate' => $html ];
		$pos      = array_search( 'wrapper_end', array_keys( $sections ), true );
		if ( false === $pos ) {
			return array_merge( $sections, $new_item );
		}

		return array_merge(
			array_slice( $sections, 0, $pos, true ),
			$new_item,
			array_slice( $sections, $pos, null, true )
		);
	}

	public static function render_classic_meta_secondary(): void {
		$course    = function_exists( 'learn_press_get_course' ) ? learn_press_get_course() : false;
		$course_id = self::get_course_id( $course );
		if ( ! $course_id || ! self::should_display( $course_id ) ) {
			return;
		}

		echo sprintf(
			'<div class="meta-item meta-item-certificate">%s<span>%s</span></div>',
			self::icon_html(),
			esc_html( self::label_text() )
		);
	}

	public static function render_gutenberg_badge( CourseModel $course ): string {
		$course_id = (int) $course->get_id();
		if ( ! $course_id || ! self::should_display( $course_id ) ) {
			return '';
		}

		return sprintf(
			'<div class="info-meta-item info-meta-certificate">
				<span class="info-meta-left">%s%s</span>
			</div>',
			self::icon_html(),
			esc_html( self::label_text() )
		);
	}

	public static function should_display( int $course_id ): bool {
		$show = ( new CourseCertificateInfo( [ 'ID' => $course_id ] ) )->get_cert_post_model();
		return (bool) apply_filters( 'learn-press/certificate/badge/show', $show, $course_id );
	}

	private static function get_course_id( $course ): int {
		if ( $course instanceof CourseModel ) {
			return (int) $course->get_id();
		}
		if ( is_object( $course ) && method_exists( $course, 'get_id' ) ) {
			return (int) $course->get_id();
		}
		return (int) get_the_ID();
	}

	private static function get_classic_course_id(): int {
		$course_id = (int) get_queried_object_id();
		if ( $course_id ) {
			return $course_id;
		}

		if ( function_exists( 'learn_press_get_course_id' ) ) {
			$course_id = (int) learn_press_get_course_id();
			if ( $course_id ) {
				return $course_id;
			}
		}

		return (int) get_the_ID();
	}

	public static function label_text(): string {
		return apply_filters(
			'learn-press/certificate/badge/label',
			__( 'Certificate on completion', 'learnpress-certificates' )
		);
	}

	public static function icon_html(): string {
		static $cached = null;
		if ( null !== $cached ) {
			return $cached;
		}

		$path = LP_ADDON_CERTIFICATES_PATH . '/assets/images/svg/lp-icon-cer.svg';
		$svg  = '';
		if ( file_exists( $path ) ) {
			$svg = (string) file_get_contents( $path );
		}

		if ( empty( $svg ) ) {
			$cached = '<i class="lp-cert-badge-icon"></i>';
			return $cached;
		}

		$cached = sprintf(
			'<span class="lp-cert-badge-icon" aria-hidden="true">%s</span>',
			$svg
		);

		return $cached;
	}
}
