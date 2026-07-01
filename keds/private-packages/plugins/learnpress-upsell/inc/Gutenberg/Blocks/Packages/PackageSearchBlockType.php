<?php

namespace LearnPress\Upsell\Gutenberg\Blocks\Packages;

use LearnPress\TemplateHooks\Course\ListCoursesTemplate;
use LearnPress\Upsell\Gutenberg\Blocks\AbstractPackageBlockType;
use LearnPress\Upsell\TemplateHooks\ArchivePackage;
use LP_Debug;
use Throwable;

/**
 * Class PackageSearchBlockType
 *
 * Handle register, render block template
 */
class PackageSearchBlockType extends AbstractPackageBlockType {
	public $block_name = 'package-search';

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
			$html_search = ArchivePackage::instance()->html_search_form();
			$html        = $this->get_output( $html_search );
		} catch ( Throwable $e ) {
			LP_Debug::error_log( $e );
		}

		return $html;
	}
}
