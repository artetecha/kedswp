<?php

namespace LearnPress\Upsell\Gutenberg\Blocks\SinglePackageElements;

use LearnPress\Upsell\TemplateHooks\SinglePackage;
use LP_Debug;
use Throwable;
use WP_Block;

/**
 * Class PackageDescriptionBlockType
 *
 * Handle register, render block template
 */
class PackageDescriptionBlockType extends AbstractSinglePackageBlockType {
	public $block_name = 'package-description';

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

			$description = SinglePackage::instance()->html_description( $package );

			if ( empty( $description ) ) {
				return $html;
			}
			$html = $this->get_output( $description );
		} catch ( Throwable $e ) {
			LP_Debug::error_log( $e );
		}

		return $html;
	}
}
