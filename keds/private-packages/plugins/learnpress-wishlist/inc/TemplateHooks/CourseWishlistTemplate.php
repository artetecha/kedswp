<?php

namespace LearnPress\Wishlist\TemplateHooks;

use LearnPress\Helpers\Singleton;
use LearnPress\Helpers\Template;
use LearnPress\Models\CourseModel;
use LearnPress\Models\UserModel;
use LearnPress\TemplateHooks\TemplateAJAX;
use LP_Addon_Wishlist;
use LP_Addon_Wishlist_Preload;
use LP_Helper;
use LP_Profile;
use stdClass;

/**
 * Class CourseWishlistTemplate
 *
 * @package LearnPress\Wishlist\TemplateHooks
 * @since 4.0.9
 * @version 1.0.0
 */
class CourseWishlistTemplate {
	use Singleton;

	public static string $icon_add_to_wishlist;
	public static string $icon_remove_from_wishlist;

	public function init() {
		self::$icon_add_to_wishlist      = sprintf(
			'<i class="lp-icon-heart-o" title="%s"></i>',
			esc_html__( 'Add to Wishlist', 'learnpress-wishlist' )
		);
		self::$icon_remove_from_wishlist = sprintf(
			'<i class="lp-icon-heart" title="%s"></i>',
			esc_html__( 'Remove from Wishlist', 'learnpress-wishlist' )
		);

		// Allow callback for AJAX load content.
		add_filter( 'lp/rest/ajax/allow_callback', array( $this, 'allow_callback' ) );
	}

	/**
	 * Allow callback for AJAX load content.
	 *
	 * @param array $callbacks
	 *
	 * @return array
	 */
	public function allow_callback( array $callbacks ): array {
		$callbacks[] = self::class . ':render_button_action';

		return $callbacks;
	}

	/**
	 * HTML button add/remove to wishlist.
	 *
	 * @param CourseModel $courseModel
	 * @param array $data [ 'effect' => bool, 'layout' => string ]
	 *
	 * @return string
	 */
	public function html_button_action( CourseModel $courseModel, array $data = [] ): string {
		wp_enqueue_script( 'lp-course-wishlist-script' );
		wp_enqueue_style( 'lp-course-wishlist' );

		$args = [
			'id_url'    => 'wishlist-action',
			'course_id' => $courseModel->get_id(),
		];

		$args = $args + $data;

		$content_obj                     = self::render_button_action( $args );
		$args['html_no_load_ajax_first'] = $content_obj->content;

		$callBack = [
			'class'  => self::class,
			'method' => 'render_button_action',
		];

		return TemplateAJAX::load_content_via_ajax( $args, $callBack );
	}

	/**
	 * Render html button with layout.
	 *
	 * @param array $data [ 'course_id' => int, 'effect' => bool, 'layout' => string ]
	 * param effect bool Indicate to add or remove course from wishlist.
	 *
	 * @return stdClass
	 */
	public static function render_button_action( array $data = [] ): stdClass {
		$object          = new stdClass();
		$object->content = '';

		$course_id   = isset( $data['course_id'] ) ? absint( $data['course_id'] ) : 0;
		$courseModel = CourseModel::find( $course_id, true );
		if ( ! $courseModel ) {
			return $object;
		}

		$userModel = UserModel::find( get_current_user_id(), true );
		$user_id   = $userModel ? $userModel->get_id() : 0;

		$effect  = $data['effect'] ?? false;
		$message = '';

		$layout = $data['layout'] ?? 'modern';
		switch ( $layout ) {
			case 'classic':
				$button_content_arr = self::instance()->button_content_action_classic();
				break;
			case 'modern':
				$button_content_arr = self::instance()->button_content_action_modern();
				break;
			case 'icon-only':
				$button_content_arr = self::instance()->button_content_action_icon_only();
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

		$profile             = LP_Profile::instance( $user_id );
		$status_wishlisted   = false;
		$html_button_content = '';

		$section = apply_filters(
			'lp/wishlist/course/button/action',
			[
				'wrap'         => '<div class="course-button-wishlist__wrapper-no-css">',
				'btn-wrap'     => sprintf(
					'<button class="lp-button lp-button-wishlist-action %s" type="button">',
					esc_attr( $layout ),
				),
				'btn-content'  => $html_button_content,
				'btn-wrap-end' => '</button>',
				'wrap-end'     => '</div>',
			],
			$courseModel,
			$userModel,
			$data
		);

		if ( ! $userModel instanceof UserModel ) {
			$section['btn-content'] = $button_content_arr['add'] ?? '';
			$object->content        = Template::combine_components( $section );
			$object->message        = sprintf(
				esc_html__( 'Please %s to add this course to your wishlist.', 'learnpress-wishlist' ),
				sprintf(
					'<a href="%s" target="_blank">%s</a>',
					learn_press_get_login_url( $data['url_current'] ?? '' ),
					esc_html__( 'login', 'learnpress-wishlist' )
				)
			);
			$object->status         = 'warning';

			return $object;
		}

		if ( $effect ) {
			$status_wishlisted = LP_Addon_Wishlist::user_add_or_remove_wishlist_course( $userModel, $courseModel );
		} else {
			$status_wishlisted = LP_Addon_Wishlist_Preload::$addon->has_in_wishlist( $course_id, $userModel );
		}

		if ( $status_wishlisted ) {
			$section['btn-content'] = $button_content_arr['remove'] ?? '';
			$message                = sprintf(
				'%s %s',
				__( 'Course added to wishlist.', 'learnpress-wishlist' ),
				sprintf(
					'<a href="%s" target="_blank">%s</a>',
					$profile->get_tab_link( 'wishlist' ),
					__( 'View Wishlist', 'learnpress-wishlist' )
				)
			);
		} else {
			$section['btn-content'] = $button_content_arr['add'] ?? '';
			$message                = esc_html__( 'Course removed from wishlist.', 'learnpress-wishlist' );
		}

		$section['btn-wrap'] = sprintf(
			'<button class="lp-button lp-button-wishlist-action %s %s" type="button">',
			esc_attr( $layout ),
			$status_wishlisted ? 'wishlisted' : 'not-in-wishlist',
		);

		$object->content = Template::combine_components( $section );
		$object->message = wp_kses_post( $message );

		return $object;
	}

	/**
	 * Button content with classic layout.
	 *
	 * @return array
	 */
	public function button_content_action_classic(): array {
		$buttons = [
			'add'    => sprintf(
				'%s %s',
				self::$icon_add_to_wishlist,
				esc_html__( 'Add to Wishlist', 'learnpress-wishlist' )
			),
			'remove' => sprintf(
				'%s %s',
				self::$icon_remove_from_wishlist,
				esc_html__( 'Remove from Wishlist', 'learnpress-wishlist' )
			),
		];

		return apply_filters(
			'lp/wishlist/course/button/action/layout-classic',
			$buttons
		);
	}

	/**
	 * Button content with modern layout.
	 *
	 * @return array
	 */
	public function button_content_action_modern(): array {
		$buttons = [
			'add'    => sprintf(
				'%s %s',
				self::$icon_add_to_wishlist,
				esc_html__( 'Wishlist', 'learnpress-wishlist' )
			),
			'remove' => sprintf(
				'%s %s',
				self::$icon_remove_from_wishlist,
				esc_html__( 'Wishlist', 'learnpress-wishlist' )
			),
		];

		return apply_filters(
			'lp/wishlist/course/button/action/layout-modern',
			$buttons
		);
	}

	/**
	 * Button content with icon only layout.
	 *
	 * @return array
	 */
	public function button_content_action_icon_only(): array {
		$buttons = [
			'add'    => self::$icon_add_to_wishlist,
			'remove' => self::$icon_remove_from_wishlist,
		];

		return apply_filters(
			'lp/wishlist/course/button/action/layout-icon-only',
			$buttons
		);
	}
}
