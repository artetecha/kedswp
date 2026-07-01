<?php

namespace LearnPress\Upsell\Gutenberg;

use LearnPress\Helpers\Singleton;
use LearnPress\Upsell\Gutenberg\Blocks\Legacy\ArchivePackageBlockLegacy;
use LearnPress\Upsell\Gutenberg\Blocks\Legacy\SinglePackageBlockLegacy;
use LearnPress\Upsell\Gutenberg\Blocks\SinglePackageElements\PackageTitleBlockType;
use LearnPress\Upsell\Gutenberg\Templates\ArchivePackageBlockTemplate;
use LearnPress\Upsell\Gutenberg\Templates\SingleCollectionLearningBlockTemplate;
use LearnPress\Upsell\Gutenberg\Templates\SinglePackageBlockTemplate;
use WP_Post;

/**
 * Class GutenbergHandleMain
 *
 * Handle register, render block template
 * @since 4.0.6 Convert from old class Block_Template_Handle
 * @version 1.0.0
 */
class GutenbergHandleMain {
	use Singleton;

	/**
	 * Hooks handle block template
	 */
	public function init() {
		if ( ! wp_is_block_theme() ) {
			return;
		}

		add_filter( 'learn-press/config/block-templates', array( $this, 'add_block_templates' ), 25, 1 );
		add_filter( 'learn-press/config/block-elements', array( $this, 'add_block_elements' ), 15, 1 );
	}

	public function add_block_templates( $templates ) {
		$templates[] = new ArchivePackageBlockTemplate();
		$templates[] = new SinglePackageBlockTemplate();
		return $templates;
	}

	public function add_block_elements( $elements ) {
		$elements[] = new ArchivePackageBlockLegacy();
		$elements[] = new SinglePackageBlockLegacy();
		// $elements[] = new PackageButtonBlockType();
		// $elements[] = new PackageDescriptionBlockType();
		// $elements[] = new PackageImageBlockType();
		// $elements[] = new PackagePriceBlockType();
		// $elements[] = new PackageTitleBlockType();
		// $elements[] = new PackageTotalCoursesBlockType();

		return $elements;
	}
}
