<?php

namespace LearnPress\Upsell\Gutenberg\Blocks\SinglePackageElements;

use LearnPress\TemplateHooks\Course\SingleCourseTemplate;
use LearnPress\Upsell\TemplateHooks\SinglePackage;
use LP_Debug;
use Throwable;

/**
 * Class PackageImageBlockType
 *
 * Handle register, render block template
 */
class PackageImageBlockType extends AbstractSinglePackageBlockType {
	public $block_name = 'package-image';

	public function get_supports(): array {
		return array(
			'align'                => array( 'wide', 'full' ),
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
			'shadow'               => true,
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

			$is_link = $attributes['isLink'] ?? true;
			$new_tab = $attributes['target'] ?? false;

			$width  = ! empty( $attributes['customWidth'] ) ? absint( $attributes['customWidth'] ) : 500;
			$height = ! empty( $attributes['customHeight'] ) ? absint( $attributes['customHeight'] ) : 300;

			$size = $attributes['size'] ?? 'custom';
			if ( $size === 'custom' ) {
				$size = array(
					$width,
					$height,
				);
			}

			$data_size = array(
				'size' => $size,
			);

			$html_image = SinglePackage::instance()->html_image( $package, $data_size );

			if ( $is_link ) {
				$attribute_target = ! empty( $new_tab ) ? 'target="_blank"' : '';
				$html_image       = sprintf( '<a href="%s" %s>%s</a>', $package->get_permalink(), $attribute_target, $html_image );
			}

			if ( empty( $html_image ) ) {
				return $html;
			}

			$html = $this->get_output( $html_image );
		} catch ( Throwable $e ) {
			LP_Debug::error_log( $e );
		}

		return $html;
	}
}
