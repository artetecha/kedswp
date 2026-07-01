<?php

namespace LearnPress\Upsell\Gutenberg\Blocks\SinglePackageElements;

use LearnPress\Upsell\Gutenberg\Blocks\SinglePackageElements\AbstractSinglePackageBlockType;
use LearnPress\Upsell\TemplateHooks\SinglePackage;
use LP_Debug;
use Throwable;

/**
 * Class PackageTotalCoursesBlockType
 *
 * Handle register, render block template
 */
class PackageTotalCoursesBlockType extends AbstractSinglePackageBlockType {
	public $block_name = 'package-total-courses';

	public function get_supports(): array {
		return array(
			'align'                => array( 'wide', 'full' ),
			'color'                => array(
				'gradients'  => true,
				'background' => true,
				'text'       => true,
				'heading'    => true,
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
			$package = $this->get_package( $attributes, $block );
			if ( ! $package ) {
				return $html;
			}

			$html_total = SinglePackage::instance()->html_count_courses( $package );

			$html = $this->get_output( $html_total );
		} catch ( Throwable $e ) {
			LP_Debug::error_log( $e );
		}

		return $html;
	}
}
