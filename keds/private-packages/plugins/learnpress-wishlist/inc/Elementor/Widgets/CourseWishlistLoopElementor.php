<?php
/**
 * Class CourseWishlistElementor
 *
 * @sicne 4.0.6
 * @version 1.0.1
 */
namespace LearnPress\Wishlist\Elementor\Widgets;

use LearnPress\ExternalPlugin\Elementor\LPElementorWidgetBase;
use LearnPress\Models\CourseModel;
use LearnPress\Wishlist\TemplateHooks\CourseWishlistTemplate;
use LP_Addon_Wishlist;
use LP_Debug;
use Throwable;

class CourseWishlistLoopElementor extends LPElementorWidgetBase {

	public function __construct( $data = [], $args = null ) {
		$this->title    = esc_html__( 'Course Wishlist Loop', 'learnpress-wishlist' );
		$this->name     = 'course_wishlist_loop';
		$this->keywords = [ 'course wishlist', 'wishlist', 'loop' ];
		$this->add_script_depends( 'lp-course-wishlist-script' );
		parent::__construct( $data, $args );
	}

	public function is_dynamic_content(): bool {
		return true;
	}

	protected function register_controls() {
		$this->controls = require_once LP_Addon_Wishlist::instance()->plugin_folder_path . '/config/elementor/wishlist_loop.php';
		parent::register_controls();
	}

	/**
	 * Show content of widget
	 *
	 * @return void
	 */
	protected function render() {
		try {
			$course = CourseModel::find( get_the_ID(), true );
			if ( ! $course ) {
				return;
			}

			$this->set_render_attribute( '_wrapper', 'class', 'lp-wishlist-widget-loop' );

			$settings = $this->get_settings_for_display();
			$layout   = $settings['layout'] ?? 'classic';

			echo CourseWishlistTemplate::instance()->html_button_action( $course, [ 'layout' => $layout ] );

		} catch ( Throwable $e ) {
			LP_Debug::error_log( $e );
		}
	}
}
