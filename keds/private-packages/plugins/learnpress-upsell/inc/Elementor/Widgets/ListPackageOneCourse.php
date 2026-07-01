<?php
/**
 * Class ListPackage
 *
 * @sicne 4.0.4
 * @version 1.0.0
 */
namespace LP_Addon_Upsell\Elementor\Widgets;

use LearnPress\Helpers\Template;
use LearnPress\ExternalPlugin\Elementor\LPElementorWidgetBase;
use LearnPress\ExternalPlugin\Elementor\Widgets\Course\SingleCourseBaseElementor;
use LearnPress\Upsell\TemplateHooks\SingleCoursePackage;
use LearnPress\Upsell\Package\Template_Functions;

class ListPackageOneCourse extends LPElementorWidgetBase {

	public function __construct( $data = array(), $args = null ) {
		$this->title    = esc_html__( 'List Package', 'learnpress-upsell' );
		$this->name     = 'list_package';
		$this->keywords = array( 'list package' );
		$this->add_style_depends( 'learnpress-package' );
		parent::__construct( $data, $args );
	}

	protected function register_controls() {
		$this->controls = require LP_ADDON_UPSELL_PATH . '/inc/Elementor/config/listpackage.php';
		parent::register_controls();
	}

	/**
	 * Show content of widget
	 *
	 * @return void
	 */
	protected function render() {
		try {
			$settings = $this->get_settings_for_display();

			$course_id = get_the_ID();
			if ( ! $course_id ) {
				return;
			}

			$query = Template_Functions::instance()->query_package_tab_in_course( $course_id );

			if ( empty( $query ) ) {
				return;
			}

			$tag   = $settings['tag'] ?? '';
			$title = $settings['title'] ?? '';

			$top = [
				'wrapper'     => '<div class="lp-course-packages__top">',
				'tag'         => sprintf( '<span>%s</span>', $tag ),
				'title'       => sprintf( '<h4>%s</h4>', $title ),
				'wrapper_end' => '</div>',
			];

			$data = [
				'packages'   => $query['packages'],
				'total_page' => $query['total_page'],
				'course_id'  => $course_id,
			];

			$singleCoursePackage = SingleCoursePackage::instance();

			$section = [
				'wrapper'     => '<div class="lp-course-packages-elementor">',
				'top'         => Template::combine_components( $top ),
				'content'     => $singleCoursePackage->render_packages( $data ),
				'wrapper_end' => '</div>',
			];

			echo Template::combine_components( $section );

		} catch ( \Throwable $e ) {
			error_log( $e->getMessage() );
		}
	}
}
