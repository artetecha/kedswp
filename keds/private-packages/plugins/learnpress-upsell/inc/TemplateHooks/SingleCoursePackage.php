<?php
/**
 * Template hooks single course, display packages.
 *
 * @since 1.0.0
 * @version 1.0.1
 */

namespace LearnPress\Upsell\TemplateHooks;

use LearnPress\Helpers\Singleton;
use LearnPress\Helpers\Template;
use LearnPress\Upsell\Package\Core_Functions;
use LearnPress\Upsell\Package\Package;
use LearnPress\Upsell\Package\Template_Functions;
use LP_Addon_Upsell_Preload;
use LP_Settings;

class SingleCoursePackage {
	use Singleton;

	public function init() {
		add_filter( 'learn-press/course-tabs', [ $this, 'course_tab_package' ] );
	}

	/**
	 * Add tab package in course.
	 *
	 * @param array $tabs
	 *
	 * @return array
	 */
	public function course_tab_package( $tabs ) {
		$enable = LP_Settings::instance()->get( 'package.is_course_tab', 'yes' );
		if ( $enable !== 'yes' ) {
			return $tabs;
		}

		$count_packages = Core_Functions::instance()->count_packages_by_course_id( get_the_ID() );
		if ( $count_packages <= 0 ) {
			return $tabs;
		}

		$tabs['package'] = array(
			'title'    => __( 'Package', 'learnpress-upsell' ),
			'priority' => 60,
			'callback' => [ $this, 'tab_package_content' ],
		);

		return $tabs;
	}

	/**
	 * Tab package in course.
	 *
	 * @return void
	 */
	public function tab_package_content() {
		$course_id = get_the_ID();
		if ( ! $course_id ) {
			return;
		}

		$query = Template_Functions::instance()->query_package_tab_in_course( $course_id );
		if ( empty( $query ) ) {
			return;
		}

		$data = [
			'packages'   => $query['packages'],
			'total_page' => $query['total_page'],
			'course_id'  => $course_id,
		];

		echo $this->render_packages( $data );
	}

	/**
	 * HTML show list packages.
	 *
	 * @param array $data
	 *
	 * @return string
	 */
	public function render_packages( array $data = [] ): string {
		$load_more = [];

		if ( ! empty( $data['total_page'] ) ) {
			$load_more = [
				'wrapper'     => '<div class="lp-course-packages__loadmore">',
				'button'      => sprintf(
					'<button class="lp-course-packages__loadmore__btn" data-course-id="%s" data-page="2">%s</button>',
					esc_attr( $data['course_id'] ),
					esc_html__( 'Load more', 'learnpress-upsell' )
				),
				'wrapper_end' => '</div>',
			];
		}

		ob_start();
		if ( ! empty( $data['packages'] ) ) {
			foreach ( $data['packages'] as $package ) {
				$package = new Package( $package );
				echo ArchivePackage::instance()->render_package( $package );
			}
		}
		$content = ob_get_clean();

		$section = apply_filters(
			'learn-press/upsell/single-course/list-package',
			[
				'wrapper'          => '<div class="lp-course-packages">',
				'package_list'     => '<ul class="lp-course-packages__list">',
				'package'          => $content,
				'package_list_end' => '</ul>',
				'load_more'        => Template::combine_components( $load_more ),
				'wrapper_end'      => '</div>',
			],
			$data
		);

		return Template::combine_components( $section );
	}
}
