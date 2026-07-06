<?php
/**
 * Class GradebookTemplate
 */

namespace LearnPress\Gradebook\TemplateHooks;

use LearnPress\Helpers\Template;

class GradebookTemplate {
	public static function html_micromodal( array $args = array() ): string {
		$id           = $args['id'] ?? '';
		$html_content = $args['content'] ?? '';
		$title        = $args['title'] ?? '';
		$html_footer  = $args['footer'] ?? sprintf(
			'<button class="lp-button button-secondary"
					data-micromodal-close
					aria-label="Close this dialog window">%s
				</button>',
			esc_html__( 'Close', 'learnpress-gradebook' )
		);

		$section = array(
			'wrap'          => sprintf(
				'<div id="%s" class="lp-micromodal-slide lp-micromodal" aria-hidden="true">',
				esc_attr( $id )
			),
			'overlay'       => '<div class="lp-micromodal__overlay" tabindex="-1" data-micromodal-close>',
			'container'     => '<div class="lp-micromodal__container" role="dialog" aria-modal="true">',
			'header'        => '<header class="lp-micromodal__header">',
			'title'         => sprintf(
				'<h2 class="lp-micromodal__title">%s</h2>',
				$title
			),
			'icon-close'    => '<i class="lp-icon-close" data-micromodal-close></i>',
			'header_end'    => '</header>',
			'content'       => sprintf(
				'<div class="lp-micromodal__content">%s</div>',
				$html_content
			),
			'footer'        => sprintf(
				'<footer class="lp-micromodal__footer">%s</footer>',
				$html_footer
			),
			'container_end' => '</div>',
			'overlay_end'   => '</div>',
			'wrap_end'      => '</div>',
		);

		return Template::combine_components( $section );
	}
}
