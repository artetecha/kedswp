<?php
namespace LearnPress\Upsell\Gutenberg\Blocks;

use LearnPress\Gutenberg\Blocks\AbstractBlockType;

/**
 * Class AbstractPackageBlockType
 *
 * Handle register, render block template
 */
abstract class AbstractPackageBlockType extends AbstractBlockType {
	public function get_source_js() {
		return LP_ADDON_UPSELL_URL . 'build/blocks/' . $this->block_name . '.js';
	}
}
