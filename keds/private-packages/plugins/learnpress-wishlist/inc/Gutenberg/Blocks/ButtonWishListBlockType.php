<?php

namespace LearnPress\Wishlist\Gutenberg\Blocks;

use LearnPress\Gutenberg\Blocks\SingleCourseElements\AbstractCourseBlockType;
use LearnPress\Helpers\Template;
use LearnPress\Models\CourseModel;
use LearnPress\Models\UserModel;
use LearnPress\TemplateHooks\TemplateAJAX;
use LearnPress\Wishlist\TemplateHooks\CourseWishlistTemplate;
use LP_Addon_Wishlist;
use LP_Addon_Wishlist_Preload;
use LP_Debug;
use LP_Helper;
use LP_Profile;
use stdClass;
use Throwable;
use WP_Block;

/**
 * Class ButtonWishListBlockType
 *
 * Handle register, render block template
 *
 * @since 4.0.9
 * @version 1.0.0
 */
class ButtonWishListBlockType extends AbstractCourseBlockType {
	public $block_name = 'course-button-wishlist';
	public $path_block_json;

	public function __construct() {
		$this->path_block_json = LP_Addon_Wishlist_Preload::$addon->plugin_folder_path . '/assets/src/js/gutenberg/blocks/course-button-wishlist';
		add_filter( 'lp/rest/ajax/allow_callback', [ $this, 'allow_callback' ] );
		parent::__construct();
	}

	/**
	 * Allow callback for block
	 *
	 * @param array $callbacks .
	 *
	 * @return array
	 */
	public function allow_callback( array $callbacks ): array {
		/**
		 * @uses render_button
		 */
		$callbacks[] = get_class( $this ) . ':render_button';

		return $callbacks;
	}

	/**
	 * Get Block Type
	 *
	 * @return string
	 */
	public function get_source_js() {
		$ver = LP_ADDON_WISHLIST_VER;
		$min = '.min';
		if ( LP_Debug::is_debug() ) {
			$min = '';
			$ver = uniqid();
		}
		$is_rtl = is_rtl() ? '-rtl' : '';
		return LP_Addon_Wishlist_Preload::$addon->get_plugin_url( "assets/dist/js/blocks/{$this->block_name}{$min}.js" );
	}

	public function get_supports(): array {
		return [
			'align'                => [ 'wide', 'full' ],
			'html'                 => false,
			'typography'           => [
				'fontSize'                      => true,
				'__experimentalDefaultControls' => [ 'fontSize' => true ],
			],
			'color'                => [
				'background'                    => true,
				'text'                          => true,
				'__experimentalDefaultControls' => [
					'background' => true,
					'text'       => true,
				],
			],
			'__experimentalBorder' => [
				'color'                         => true,
				'radius'                        => true,
				'width'                         => true,
				'__experimentalDefaultControls' => [
					'width'  => false,
					'color'  => false,
					'radius' => false,
				],
			],
			'spacing'              => [
				'margin'                        => true,
				'padding'                       => true,
				'content'                       => true,
				'__experimentalDefaultControls' => [
					'margin'  => false,
					'padding' => false,
					'content' => true,
				],
			],
		];
	}

	/**
	 * Get block attributes.
	 *
	 * @return array
	 */
	public function get_attributes(): array {
		return [
			'textAlign'      => [
				'type'    => 'string',
				'default' => 'center',
			],
			'justifyContent' => [
				'type'    => 'string',
				'default' => 'center',
			],
			'alignItems'     => [
				'type'    => 'string',
				'default' => 'center',
			],
			'width'          => [
				'type'    => 'string',
				'default' => '100',
			],
			'layout'         => [
				'type'    => 'string',
				'default' => 'modern',
				'enum'    => [ 'classic', 'modern', 'icon-only' ],
			],
		];
	}

	/**
	 * Render content of block tag
	 *
	 * @param array $attributes | Attributes of block tag.
	 * @param string $content
	 * @param WP_Block $block
	 *
	 * @return string
	 */
	public function render_content_block_template( array $attributes, $content, $block ): string {
		$html = '';

		try {
			$courseModel = $this->get_course( $attributes, $block );
			if ( ! $courseModel ) {
				return $html;
			}

			$userModel = $this->get_user();

			wp_enqueue_style( 'lp-course-wishlist' );
			wp_enqueue_script( 'lp-course-wishlist-script' );

			$callback = [
				'class'  => get_class( $this ),
				'method' => 'render_button',
			];

			$args = [
				'id_url'       => 'gutenberg-course-button-wishlist',
				'course_id'    => $courseModel->get_id(),
				'user_id'      => $userModel ? $userModel->get_id() : 0,
				'attributes'   => $attributes,
				'parsed_block' => $block->parsed_block,
			];

			if ( ! isset( $args['buttons_both_status'] ) ) {
				$args['buttons_both_status'] = self::html_buttons_with_style( $args );
			}

			$contentObj                      = self::render_button( $args );
			$args['html_no_load_ajax_first'] = $contentObj->content;

			return TemplateAJAX::load_content_via_ajax( $args, $callback );
		} catch ( Throwable $e ) {
			LP_Debug::error_log( $e );
		}

		return $html;
	}

	/**
	 * Render html button with layout.
	 *
	 * @param array $data [ 'course_id' => int, 'user_id' => int, 'effect' => bool, 'layout' => string ]
	 * param effect bool Indicate to add or remove course from wishlist.
	 *
	 * @return stdClass
	 */
	public static function render_button( array $data = [] ): stdClass {
		$object = new stdClass();

		$course_id   = $data['course_id'] ?? 0;
		$courseModel = CourseModel::find( $course_id, true );

		$user_id   = $data['user_id'] ?? 0;
		$userModel = UserModel::find( $user_id, true );
		if ( ! $userModel ) {
			$object->content = $data['buttons_both_status']['add'];
			$object->message = sprintf(
				esc_html__( 'Please %s to add this course to your wishlist.', 'learnpress-wishlist' ),
				sprintf(
					'<a href="%s" target="_blank">%s</a>',
					learn_press_get_login_url( $data['url_current'] ?? '' ),
					esc_html__( 'login', 'learnpress-wishlist' )
				)
			);
			$object->status  = 'warning';

			return $object;
		}

		$effect = $data['effect'] ?? false;
		if ( $effect ) {
			$status_wishlisted = LP_Addon_Wishlist::user_add_or_remove_wishlist_course( $userModel, $courseModel );
		} else {
			$status_wishlisted = LP_Addon_Wishlist_Preload::$addon->has_in_wishlist( $course_id, $userModel );
		}

		$profile = LP_Profile::instance( $user_id );
		if ( $status_wishlisted ) {
			$object->content = $data['buttons_both_status']['remove'] ?? '';
			$object->message = sprintf(
				'%s %s',
				__( 'Course added to wishlist.', 'learnpress-wishlist' ),
				sprintf(
					'<a href="%s" target="_blank">%s</a>',
					$profile->get_tab_link( 'wishlist' ),
					__( 'View Wishlist', 'learnpress-wishlist' )
				)
			);
		} else {
			$object->content = $data['buttons_both_status']['add'] ?? '';
			$object->message = esc_html__( 'Course removed from wishlist.', 'learnpress-wishlist' );
		}

		return $object;
	}

	/**
	 * Generate html buttons.
	 * Button add and remove wishlist.
	 *
	 * @param array $data
	 *
	 * @return array
	 */
	public static function html_buttons_with_style( array $data ): array {
		$courseModel = CourseModel::find( $data['course_id'] ?? 0, true );
		$userModel   = UserModel::find( $data['user_id'] ?? 0, true );
		$attributes  = $data['attributes'] ?? [];
		$layout      = $attributes['layout'] ?? 'modern';
		$align_items = $attributes['alignItems'] ?? 'center';

		// Get wrapper styles from block attributes
		$map_align_items = [
			'top'    => 'flex-start',
			'center' => 'center',
			'bottom' => 'flex-end',
		];

		$text_align      = sanitize_text_field( $attributes['textAlign'] ?? 'center' );
		$align_items     = $map_align_items[ $align_items ] ?? 'center';
		$justify_content = sanitize_text_field( $attributes['justifyContent'] ?? 'center' );
		$width           = absint( $attributes['width'] ?? 100 );
		$width           = min( max( $width, 0 ), 100 ) . '%';
		$width           = $layout === 'icon-only' ? 'auto' : $width;
		$width_wrap      = $layout === 'icon-only' ? 'auto' : '100%';

		// Create wrapper div with flex layout
		$wrapper_style = sprintf(
			'display:flex;
			width:%s;
			justify-content:%s;',
			$width_wrap,
			esc_attr( $justify_content ),
		);

		$style_button = sprintf(
			'width:%s;
			justify-content:%s;
			align-items:%s;',
			$width,
			$text_align,
			$align_items
		);

		$config_button_wrap = [
			'class' => "lp-button lp-button-wishlist-action {$layout}",
			'style' => $style_button,
		];
		$button_wrap        = get_block_wrapper_attributes( $config_button_wrap );

		// Style button.
		$courseWishlistTemplate = CourseWishlistTemplate::instance();
		switch ( $layout ) {
			case 'classic':
				$button_content_arr = $courseWishlistTemplate->button_content_action_classic();
				break;
			case 'modern':
				$button_content_arr = $courseWishlistTemplate->button_content_action_modern();
				break;
			case 'icon-only':
				$button_content_arr = $courseWishlistTemplate->button_content_action_icon_only();
				break;
			default:
				$button_content_arr = apply_filters(
					'lp/wishlist/course/button/action/layout-modern',
					[],
					$courseModel,
					$userModel,
					$data
				);
				break;
		}

		$section_btn = [
			'wrap'       => sprintf(
				'<div class="course-button-wishlist__wrapper-no-css" style="%s">',
				esc_attr( $wrapper_style )
			),
			'button'     => sprintf(
				'<button %s type="button">',
				$button_wrap,
			),
			'button-end' => '</button>',
			'wrap-end'   => '</div>',
		];

		$section_btn_add    = Template::insert_value_to_position_array(
			$section_btn,
			'after',
			'button',
			'btn-content',
			$button_content_arr['add']
		);
		$section_btn_remove = Template::insert_value_to_position_array(
			$section_btn,
			'after',
			'button',
			'btn-content',
			$button_content_arr['remove']
		);

		return [
			'add'    => Template::combine_components( $section_btn_add ),
			'remove' => Template::combine_components( $section_btn_remove ),
		];
	}
}
