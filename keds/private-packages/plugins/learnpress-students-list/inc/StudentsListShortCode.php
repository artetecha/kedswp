<?php
/**
 * Students list shortcode class.
 *
 * @author   ThimPress
 * @package  LearnPress\StudentsList\StudentsListShortCode
 * @version  4.0.3
 */

namespace LearnPress\StudentsList;

use LearnPress\Helpers\Singleton;
use LearnPress\Models\CourseModel;
use LearnPress\Shortcodes\AbstractShortcode;
use LP_Debug;
use Throwable;

defined( 'ABSPATH' ) || exit;

/**
 * Class StudentsListShortCode.
 */
class StudentsListShortCode extends AbstractShortcode {
	use singleton;

	protected $shortcode_name = 'students_list';

	public function render( $attrs ): string {
		$html = '';

		try {
			$courseModel = CourseModel::find( $attrs['course_id'] ?? 0, true );
			if ( ! $courseModel ) {
				return __( 'Course ID invalid, please check it again.', 'learnpress-students-list' );
			}

			ob_start();
			do_action( 'lp-addon-students-list/students-list/layout', $courseModel );
			$html = ob_get_clean();
		} catch ( Throwable $e ) {
			LP_Debug::error_log( $e );
		}

		return $html;
	}
}
