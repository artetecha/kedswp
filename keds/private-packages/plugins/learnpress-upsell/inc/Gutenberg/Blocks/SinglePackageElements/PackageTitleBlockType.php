<?php

namespace LearnPress\Upsell\Gutenberg\Blocks\SinglePackageElements;

use LearnPress\TemplateHooks\Course\SingleCourseTemplate;
use LearnPress\Upsell\Gutenberg\Blocks\SinglePackageElements\AbstractSinglePackageBlockType;
use LearnPress\Upsell\TemplateHooks\SinglePackage;
use LP_Debug;
use Throwable;

/**
 * Class PackageTitleBlockType
 *
 * Handle register, render block template
 */
class PackageTitleBlockType extends AbstractSinglePackageBlockType {
	public $block_name      = 'package-title';
	public $path_block_json = LP_PLUGIN_PATH . 'assets/src/apps/js/blocks/course-elements/course-title';

	public function get_supports(): array {
		return array(
			'align'      => array( 'wide', 'full' ),
			'color'      => array(
				'gradients'  => true,
				'background' => true,
				'text'       => true,
			),
			'typography' => array(
				'fontSize'                      => true,
				'lineHeight'                    => false,
				'fontWeight'                    => true,
				'textTransform'                 => false,
				'__experimentalFontFamily'      => false,
				'__experimentalTextDecoration'  => false,
				'__experimentalFontStyle'       => true,
				'__experimentalFontWeight'      => true,
				'__experimentalLetterSpacing'   => false,
				'__experimentalTextTransform'   => true,
				'__experimentalDefaultControls' => array(
					'fontSize'      => true,
					'textTransform' => false,
				),
			),
			'spacing'    => array(
				'padding' => true,
				'margin'  => true,
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

			$single_package_template = SinglePackage::instance();
			$tag                     = sanitize_text_field( $attributes['tag'] ?? 'h3' );
			$allowed_aligns          = array( 'span', 'div', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6' );
			$tag                     = in_array( $tag, $allowed_aligns, true ) ? $tag : 'h3';
			$is_link                 = $attributes['isLink'] ?? false;
			$target                  = $attributes['target'] ?? false;

			$html_content = $single_package_template->html_title( $package );
			if ( $is_link ) {
				$html_content = sprintf(
					'<a class="package-permalink" href="%s" %s>%s</a>',
					esc_url( $package->get_permalink() ),
					$target ? 'target="_blank"' : '',
					$single_package_template->html_title( $package )
				);
			}

			$html = $this->get_output( $html_content, $tag );
		} catch ( Throwable $e ) {
			LP_Debug::error_log( $e );
		}

		return $html;
	}
}
