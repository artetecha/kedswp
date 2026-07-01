<?php

namespace LearnPress\Upsell\Gutenberg\Blocks\Packages;

use LearnPress\TemplateHooks\Course\FilterCourseTemplate;
use LearnPress\TemplateHooks\Course\ListCoursesTemplate;
use LearnPress\Upsell\Gutenberg\Blocks\AbstractPackageBlockType;
use LearnPress\Upsell\TemplateHooks\ArchivePackage;
use LP_Debug;
use Throwable;

/**
 * Class PackageOrderByBlockType
 *
 * Handle register, render block template
 */
class PackageOrderByBlockType extends AbstractPackageBlockType {
	public $block_name = 'package-order-by';

	public function get_supports(): array {
		return array(
			'color'                => array(
				'gradients'  => true,
				'background' => true,
				'text'       => true,
			),
			'typography'           => array(
				'fontSize'                    => true,
				'__experimentalFontWeight'    => true,
				'__experimentalTextTransform' => true,
			),
			'spacing'              => array(
				'padding' => true,
				'margin'  => true,
			),
			'__experimentalBorder' => array(
				'color'  => true,
				'radius' => true,
				'width'  => true,
			),
		);
	}

	/**
	 * Render content of block tag
	 *
	 * @param array $attributes | Attributes of block tag.
	 *
	 * @return false|string
	 */
	public function render_content_block_template( array $attributes, $content, $block ): string {
		$html = '';

		try {
			$html = $this->get_output( ArchivePackage::instance()->html_ordering() );
		} catch ( Throwable $e ) {
			LP_Debug::error_log( $e );
		}

		return $html;
	}
}
