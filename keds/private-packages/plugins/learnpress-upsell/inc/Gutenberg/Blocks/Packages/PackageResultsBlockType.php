<?php

namespace LearnPress\Upsell\Gutenberg\Blocks\Packages;

use LearnPress\TemplateHooks\Course\ListCoursesTemplate;
use LearnPress\Upsell\Gutenberg\Blocks\AbstractPackageBlockType;
use LearnPress\Upsell\TemplateHooks\ArchivePackage;
use LP_Debug;
use Throwable;

/**
 * Class PackageResultsBlockType
 *
 * Handle register, render block template
 */
class PackageResultsBlockType extends AbstractPackageBlockType {
	public $block_name = 'package-results';

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
			'__experimentalBorder' => array(
				'color'  => true,
				'radius' => true,
				'width'  => true,
			),
			'spacing'              => array(
				'padding'                       => true,
				'margin'                        => true,
				'__experimentalDefaultControls' => array(
					'margin'  => false,
					'padding' => false,
				),
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
			global $wp_query;
			$data = array(
				'total_page'    => (int) $GLOBALS['wp_query']->max_num_pages,
				'current'       => max( 1, $GLOBALS['wp_query']->get( 'paged', 1 ) ),
				'per_page'      => $wp_query->get( 'posts_per_page' ),
				'total_package' => $wp_query->found_posts,
			);

			$html_results = ArchivePackage::instance()->html_result_count( $data );
			$html         = $this->get_output( $html_results );
		} catch ( Throwable $e ) {
			LP_Debug::error_log( $e );
		}

		return $html;
	}
}
