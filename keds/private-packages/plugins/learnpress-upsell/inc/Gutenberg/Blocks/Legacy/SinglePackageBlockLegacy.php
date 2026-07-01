<?php

namespace LearnPress\Upsell\Gutenberg\Blocks\Legacy;
use LearnPress\Upsell\Gutenberg\Blocks\AbstractPackageBlockType;

/**
 * Class SinglePackageBlockLegacy
 *
 * Handle register, render block template
 */
class SinglePackageBlockLegacy extends AbstractPackageBlockType {
	public $block_name = 'single-package-legacy';

	/**
	 * Render content of block tag
	 *
	 * @param array $attributes | Attributes of block tag.
	 *
	 * @return false|string
	 */
	public function render_content_block_template( array $attributes, $content, $block ): string {
		ob_start();
		do_action( 'lp/upsell/layout/single-package' );
		return ob_get_clean();
	}
}
