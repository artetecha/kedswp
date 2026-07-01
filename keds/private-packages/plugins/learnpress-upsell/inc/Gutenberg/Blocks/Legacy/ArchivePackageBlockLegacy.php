<?php

namespace LearnPress\Upsell\Gutenberg\Blocks\Legacy;

use LearnPress\Upsell\Gutenberg\Blocks\AbstractPackageBlockType;

/**
 * Class ArchivePackageBlockLegacy
 *
 * Handle register, render block template
 */
class ArchivePackageBlockLegacy extends AbstractPackageBlockType {
	public $block_name = 'archive-package-legacy';

	/**
	 * Render content of block tag
	 *
	 * @param array $attributes | Attributes of block tag.
	 *
	 * @return false|string
	 */
	public function render_content_block_template( array $attributes, $content, $block ): string {
		ob_start();
		do_action( 'lp/upsell/layout/archive-package' );
		return ob_get_clean();
	}
}
