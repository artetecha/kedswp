<?php

namespace LearnPress\Wishlist\TemplateHooks;

use LearnPress\Databases\DataBase;
use LearnPress\Filters\Course\CourseJsonFilter;
use LearnPress\Helpers\Singleton;
use LearnPress\Helpers\Template;
use LearnPress\Models\CourseModel;
use LearnPress\Models\Courses;
use LearnPress\TemplateHooks\Course\FilterCourseTemplate;
use LearnPress\TemplateHooks\Course\ListCoursesTemplate;
use LearnPress\TemplateHooks\TemplateAJAX;
use LP_Addon_Wishlist;
use LP_Settings;
use stdClass;

/**
 * Class CourseWishlistTemplate
 *
 * @package LearnPress\Wishlist\TemplateHooks
 * @since 4.0.9
 * @version 1.0.0
 */
class CoursesWishlistTemplate {
	use Singleton;

	public function init() {
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
		$callbacks[] = self::class . ':render_courses';

		return $callbacks;
	}

	/**
	 * HTML button add/remove to wishlist.
	 *
	 * @param array $data []
	 *
	 * @return string
	 */
	public function html_list_courses( array $data = [] ): string {
		wp_enqueue_script( 'lp-course-wishlist-script' );
		wp_enqueue_style( 'lp-course-wishlist' );

		$args = [
			'id_url'                => 'wishlist-courses',
			'enableUpdateParamsUrl' => false,
		];

		$args = $args + $data;

		/**
		 * @uses self::render_courses
		 */
		$callBack = [
			'class'  => self::class,
			'method' => 'render_courses',
		];

		return sprintf(
			'%s%s',
			sprintf(
				'<h3>%s</h3>',
				__( 'My Wishlist Courses', 'learnpress-wishlist' )
			),
			TemplateAJAX::load_content_via_ajax( $args, $callBack )
		);
	}

	public static function render_courses( array $data = [] ): stdClass {
		$wishlist_course_of_user = LP_Addon_Wishlist::get_courses_wishlist( get_current_user_id() );
		$filter                  = new CourseJsonFilter();
		Courses::handle_params_for_query_list_courses( $filter, $data );
		// Wait LP v4.3.2.8 release, change it to $filter->ids
		$filter->limit   = 9;
		$filter->where[] = 'AND c.ID IN (' . implode( ',', array_map( 'absint', $wishlist_course_of_user ) ) . ')';

		$total_rows          = 0;
		$courses             = Courses::get_list_courses( $filter, $total_rows );
		$total_pages         = DataBase::get_total_pages( $filter->limit, $total_rows );
		$data['total_pages'] = $total_pages;
		$data['total_rows']  = $total_rows;
		$skin                = 'grid';
		$paged               = $data['paged'] ?? 1;
		$data['paged']       = $paged;
		$listCoursesTemplate = ListCoursesTemplate::instance();

		// HTML section courses.
		$html_courses = '';
		if ( empty( $courses ) ) {
			$html_courses = Template::print_message( __( 'No courses found', 'learnpress' ), 'info', false );
		} else {
			foreach ( $courses as $courseObj ) {
				$course        = CourseModel::find( $courseObj->ID, true );
				$html_courses .= $listCoursesTemplate::render_course( $course, $data );
			}
		}

		$section_courses = apply_filters(
			'learn-press/layout/list-courses/section/courses',
			[
				'wrap'     => sprintf(
					'<ul class="learn-press-courses wishlist lp-list-courses-no-css %1$s" data-layout="%1$s">',
					$skin
				),
				'courses'  => $html_courses,
				'wrap_end' => '</ul>',
			],
			$courses,
			$data
		);

		// Pagination html
		$data_pagination = [
			'total_pages' => $total_pages,
			'base'        => $data['url_current'] ?? '',
			'paged'       => $data['paged'] ?? 1,
		];
		$html_pagination = Template::instance()->html_pagination( $data_pagination );

		$section = apply_filters(
			'learn-press/layout/list-courses/section',
			[
				'courses'    => Template::combine_components( $section_courses ),
				'pagination' => $html_pagination,
			],
			$courses,
			$data
		);

		$content              = new stdClass();
		$content->content     = Template::combine_components( $section );
		$content->total_pages = $total_pages;
		$content->paged       = $paged;

		return $content;
	}
}
