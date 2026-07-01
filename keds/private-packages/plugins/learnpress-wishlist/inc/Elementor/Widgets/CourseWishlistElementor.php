<?php
/**
 * Class CourseWishlistElementor
 *
 * @sicne 4.0.6
 * @version 1.0.1
 */
namespace LearnPress\Wishlist\Elementor\Widgets;

use LearnPress\ExternalPlugin\Elementor\LPElementorWidgetBase;
use LearnPress\ExternalPlugin\Elementor\Widgets\Course\SingleCourseBaseElementor;
use LearnPress\Wishlist\TemplateHooks\CourseWishlistTemplate;
use LP_Addon_Wishlist;
use LP_Debug;
use Throwable;

class CourseWishlistElementor extends LPElementorWidgetBase {
	use SingleCourseBaseElementor;

	public function __construct( $data = [], $args = null ) {
		$this->title    = esc_html__( 'Course Wishlist', 'learnpress-wishlist' );
		$this->name     = 'course_wishlist';
		$this->keywords = [ 'course wishlist', 'wishlist' ];
		$this->add_script_depends( 'lp-course-wishlist-script' );
		parent::__construct( $data, $args );
	}

	protected function register_controls() {
		$this->controls = require_once LP_Addon_Wishlist::instance()->plugin_folder_path . '/config/elementor/wishlist.php';
		parent::register_controls();
	}

	/**
	 * Show content of widget
	 *
	 * @return void
	 */
	protected function render() {
		try {
			$courseModel = $this->get_course_model();
			if ( ! $courseModel ) {
				return;
			}

			$settings = $this->get_settings_for_display();
			$layout   = $settings['layout'] ?? 'classic';

			echo CourseWishlistTemplate::instance()->html_button_action( $courseModel, [ 'layout' => $layout ] );

		} catch ( Throwable $e ) {
			LP_Debug::error_log( $e );
		}
	}
}
