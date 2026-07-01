<?php
namespace LearnPress\Certificate\Gutenberg;

use LearnPress\Certificate\TemplateHooks\CourseCertificateBadge;
use LearnPress\Helpers\Template;
use LP_Debug;
use Throwable;

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( '\LearnPress\Gutenberg\Blocks\SingleCourseElements\AbstractCourseBlockType' ) ) {
	return;
}

class CourseCertificateBadgeBlockType extends \LearnPress\Gutenberg\Blocks\SingleCourseElements\AbstractCourseBlockType {
	public $block_name = 'course-certificate-badge';

	protected function get_source_js(): string {
		return plugins_url( 'assets/dist/js/blocks/course-certificate-badge.js', LP_ADDON_CERTIFICATES_FILE );
	}

	public function get_supports(): array {
		return [
			'align'      => [ 'wide', 'full' ],
			'typography' => [
				'fontSize'   => true,
				'fontWeight' => true,
			],
			'color'      => [
				'background' => false,
				'text'       => true,
				'link'       => false,
			],
			'spacing'    => [
				'padding' => true,
				'margin'  => true,
			],
		];
	}

	public function render_content_block_template( array $attributes, $content, $block ): string {
		try {
			$courseModel = $this->get_course( $attributes, $block );
			if ( ! $courseModel ) {
				return '';
			}

			$course_id = (int) $courseModel->get_id();
			if ( ! CourseCertificateBadge::should_display( $course_id ) ) {
				return '';
			}

			$show_icon  = $attributes['showIcon'] ?? true;
			$show_label = $attributes['showLabel'] ?? true;

			$left = '';
			if ( $show_icon || $show_label ) {
				$left = sprintf(
					'<span class="info-meta-left">%s%s</span>',
					$show_icon ? CourseCertificateBadge::icon_html() : '',
					$show_label ? esc_html( CourseCertificateBadge::label_text() ) : ''
				);
			}

			$section = [
				'wrap'       => '<div class="info-meta-item info-meta-certificate">',
				'info-left'  => $left,
				'wrap_end'   => '</div>',
			];

			return $this->get_output( Template::combine_components( $section ) );
		} catch ( Throwable $e ) {
			if ( class_exists( 'LP_Debug' ) ) {
				LP_Debug::error_log( $e );
			}
			return '';
		}
	}

	public static function register() {
		add_filter(
			'learn-press/config/block-elements',
			static function ( array $blocks ) {
				$blocks[] = new self();
				return $blocks;
			}
		);
	}
}
