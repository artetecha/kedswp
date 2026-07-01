<?php
/**
 * LearnPress Co-Instructor Hook Template
 *
 * @since 4.0.4
 * @version 1.0.0
 */

namespace LP_Addon_Co_Instructor;

use LearnPress\Helpers\Singleton;
use LearnPress\Helpers\Template;
use LearnPress\Models\CourseModel;
use LearnPress\Models\UserModel;
use LearnPress\TemplateHooks\Instructor\SingleInstructorTemplate;
use LP_Addon_Co_Instructor;
use LP_Co_Instructor_Preload;

class CourseCoInstructorTemplate {
	use Singleton;

	/**
	 * @var LP_Addon_Co_Instructor $addon
	 */
	public $addon;

	public function init() {
		$this->addon = LP_Co_Instructor_Preload::$addon;
		$this->hooks();
	}

	/**
	 * Hooks
	 */
	public function hooks() {
		// Show on single course
		add_action( 'learn-press/after-single-course-instructor', array( $this, 'single_course_instructors' ) );
		// Show on single course offline
		add_filter( 'learn-press/single-course/offline/section-instructor', [ $this, 'section_list_co_instructor' ], 10, 3 );
		// Show on single course Modern
		add_filter( 'learn-press/single-course/modern/section-instructor', [ $this, 'section_list_co_instructor' ], 19, 3 );
	}

	/**
	 * Get html list co-instructors
	 *
	 * @param array $instructors
	 * @param $course
	 *
	 * @return string
	 */
	public function html_list_co_instructor( array $instructors, $course ): string {
		$singleInstructorTemplate = SingleInstructorTemplate::instance();
		$html_list_instructors    = '';

		if ( count( $instructors ) === 1 && empty( $instructors[0] ) ) {
			return '';
		}

		foreach ( $instructors as $instructor_id ) {
			$instructor_id = absint( $instructor_id );
			$instructor    = UserModel::find( $instructor_id, true );

			if ( $instructor ) {
				$section_li = apply_filters(
					'lp/addon/co-instructor/layout/instructor-item/section',
					[
						'wrapper'      => '<li class="lp-co-instructor">',
						'avatar'       => $singleInstructorTemplate->html_avatar( $instructor ),
						'display_name' => sprintf(
							'<a href="%s">%s</a>',
							$instructor->get_url_instructor(),
							$instructor->get_display_name()
						),
						'wrapper_end'  => '</li>',
					],
					$instructor,
					$course
				);

				$html_list_instructors .= Template::combine_components( $section_li );
			}
		}

		$section = apply_filters(
			'lp/addon/co-instructor/layout/instructors/section',
			[
				'wrapper'     => '<ul class="lp-co-instructors">',
				'avatar'      => $html_list_instructors,
				'wrapper_end' => '</ul>',
			],
			$instructors,
			$course
		);

		return Template::combine_components( $section );
	}

	/**
	 * Show on single course
	 */
	public function single_course_instructors() {
		$course = CourseModel::find( get_the_ID(), true );
		if ( ! $course ) {
			return;
		}

		$instructors = $this->addon->get_instructors( $course );
		if ( empty( $instructors ) ) {
			return;
		}

		echo $this->html_list_co_instructor( $instructors, $course );
	}

	/**
	 * Show on single course offline
	 *
	 * @param array $section
	 * @param CourseModel $courseModel
	 * @param UserModel|false $userModel
	 *
	 * @return array
	 * @since 4.0.4
	 * @version 1.0.1
	 */
	public function section_list_co_instructor( array $section, CourseModel $courseModel, $userModel ): array {
		$instructors = $this->addon->get_instructors( $courseModel );
		if ( empty( $instructors ) ) {
			return $section;
		}

		$html = $this->html_list_co_instructor( $instructors, $courseModel );

		return apply_filters(
			'learn-press/co-instructor/single-course/position',
			Template::insert_value_to_position_array( $section, 'after', 'wrapper_end', 'co-instructors', $html ),
			$html,
			$section,
			$courseModel,
			$userModel
		);
	}
}
