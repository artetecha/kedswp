<?php
/**
 * Gradebook admin template renderer.
 *
 * @package LearnPress\Gradebook\TemplateHooks\Admin
 */

namespace LearnPress\Gradebook\TemplateHooks\Admin;

use LP_Addon_Gradebook;
use RuntimeException;
use Throwable;
/**
 * Renderer for theme-overridable Gradebook admin TemplateHook partials.
 */
class GradebookTemplateRenderer {
	/**
	 * Template directory relative to the add-on templates directory.
	 */
	private const TEMPLATE_DIRECTORY = 'admin/course-gradebook/';

	/**
	 * Get allowed HTML for nested Gradebook component output.
	 *
	 * Extends the WordPress post allowlist with interactive admin controls used
	 * by Gradebook and TemplateAJAX. Script elements are intentionally excluded.
	 *
	 * @return array
	 */
	public static function allowed_html(): array {
		$allowed_html      = wp_kses_allowed_html( 'post' );
		$global_attributes = array(
			'aria-controls'    => true,
			'aria-current'     => true,
			'aria-describedby' => true,
			'aria-details'     => true,
			'aria-expanded'    => true,
			'aria-hidden'      => true,
			'aria-label'       => true,
			'aria-labelledby'  => true,
			'aria-live'        => true,
			'class'            => true,
			'data-*'           => true,
			'dir'              => true,
			'hidden'           => true,
			'id'               => true,
			'lang'             => true,
			'role'             => true,
			'style'            => true,
			'title'            => true,
		);
		$interactive_tags  = array(
			'button' => array(
				'disabled' => true,
				'name'     => true,
				'type'     => true,
				'value'    => true,
			),
			'canvas' => array(
				'height' => true,
				'width'  => true,
			),
			'form'   => array(
				'action'       => true,
				'autocomplete' => true,
				'enctype'      => true,
				'method'       => true,
				'name'         => true,
				'novalidate'   => true,
				'target'       => true,
			),
			'input'  => array(
				'autocomplete' => true,
				'checked'      => true,
				'disabled'     => true,
				'max'          => true,
				'maxlength'    => true,
				'min'          => true,
				'minlength'    => true,
				'multiple'     => true,
				'name'         => true,
				'placeholder'  => true,
				'readonly'     => true,
				'step'         => true,
				'type'         => true,
				'value'        => true,
			),
			'label'  => array(
				'for' => true,
			),
			'option' => array(
				'disabled' => true,
				'label'    => true,
				'selected' => true,
				'value'    => true,
			),
			'select' => array(
				'disabled' => true,
				'multiple' => true,
				'name'     => true,
				'size'     => true,
			),
		);

		foreach ( $interactive_tags as $tag => $attributes ) {
			$allowed_html[ $tag ] = array_merge(
				$allowed_html[ $tag ] ?? array(),
				$global_attributes,
				$attributes
			);
		}

		return $allowed_html;
	}

	/**
	 * Render a template from templates/admin/course-gradebook.
	 *
	 * Variables that contain pre-rendered safe HTML should use a *_html suffix.
	 *
	 * @param string $template Relative template path.
	 * @param array  $args     Template variables.
	 *
	 * @return string
	 *
	 * @throws RuntimeException When the template cannot be resolved or rendered.
	 * @throws Throwable When the add-on template resolver fails.
	 */
	public static function render( string $template, array $args = array() ): string {
		$template_name = self::resolve_template_name( $template );
		if ( ! self::template_exists( $template_name ) ) {
			throw new RuntimeException( esc_html__( 'Gradebook template file does not exist.', 'learnpress-gradebook' ) );
		}

		$buffer_level = ob_get_level();
		ob_start();
		try {
			LP_Addon_Gradebook::instance()->get_template(
				$template_name,
				array(
					'template_args' => $args,
				)
			);
			$output = ob_get_clean();
			if ( false === $output ) {
				throw new RuntimeException( esc_html__( 'Unable to capture gradebook template output.', 'learnpress-gradebook' ) );
			}

			return apply_filters( 'lp/gradebook/admin/template/render', $output, $template_name, $args );
		} catch ( Throwable $e ) {
			while ( ob_get_level() > $buffer_level ) {
				ob_end_clean();
			}

			throw $e;
		}
	}

	/**
	 * Resolve and validate a template name relative to the add-on templates directory.
	 *
	 * @param string $template Relative template path.
	 *
	 * @return string
	 *
	 * @throws RuntimeException When the relative template path is invalid.
	 */
	protected static function resolve_template_name( string $template ): string {
		$template = wp_normalize_path( trim( $template ) );
		if (
			'' === $template
			|| false !== strpos( $template, '..' )
			|| 0 === strpos( $template, '/' )
			|| preg_match( '/^[A-Za-z]:\//', $template )
		) {
			throw new RuntimeException( esc_html__( 'Invalid gradebook template path.', 'learnpress-gradebook' ) );
		}

		return self::TEMPLATE_DIRECTORY . ltrim( $template, '/' );
	}

	/**
	 * Check a template through the standard add-on resolver without rendering it.
	 *
	 * LP_Addon::get_template() does not return the resolved path when include is
	 * false, but missing templates emit an error message.
	 *
	 * @param string $template_name Add-on-relative template name.
	 *
	 * @return bool
	 *
	 * @throws Throwable When the add-on template resolver fails.
	 */
	protected static function template_exists( string $template_name ): bool {
		$buffer_level = ob_get_level();
		ob_start();
		try {
			LP_Addon_Gradebook::instance()->get_template( $template_name, array(), false );
			$output = ob_get_clean();

			return false !== $output && '' === trim( $output );
		} catch ( Throwable $e ) {
			while ( ob_get_level() > $buffer_level ) {
				ob_end_clean();
			}

			throw $e;
		}
	}
}
