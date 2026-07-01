<?php
namespace LearnPress\Upsell\Gutenberg\Templates;
use LearnPress\Gutenberg\Templates\AbstractBlockTemplate;

/**
 * Class AbstractPackageBlockTemplate
 *
 * @since 4.0.6
 */
class AbstractPackageBlockTemplate extends AbstractBlockTemplate {
	public function __construct() {
		$this->id      = $this->theme . '//' . $this->slug;
		$template_file = '';

		if ( ! empty( $this->path_html_block_template_file ) ) {
			$template_file = LP_ADDON_UPSELL_PATH . '/templates/block/' . $this->path_html_block_template_file;
		}

		if ( file_exists( $template_file ) ) {
			$content = file_get_contents( $template_file );
			if ( version_compare( get_bloginfo( 'version' ), '6.4-beta', '>=' ) ) {
				$this->content = traverse_and_serialize_blocks( parse_blocks( $content ) );
			} else {
				$this->content = _inject_theme_attribute_in_block_template_content( $content );
			}
		}
	}
}
