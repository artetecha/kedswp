<?php
namespace LearnPress\Upsell\Gutenberg\Templates;

use LearnPress\Upsell\Gutenberg\Templates\AbstractPackageBlockTemplate;

/**
 * Class ArchivePackageBlockTemplate
 *
 * @since 4.0.6
 * @version 1.0.0
 */
class ArchivePackageBlockTemplate extends AbstractPackageBlockTemplate {
	public $slug                          = 'archive-learnpress_package';
	public $title                         = 'Archive Packages Template';
	public $description                   = 'Archive Packages Block Template';
	public $path_html_block_template_file = 'html/archive-package-template-default.html';
}
