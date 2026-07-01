<?php

namespace LearnPress\CourseReview;

use LearnPress\Helpers\Singleton;
use LearnPress\Helpers\Template;
use LearnPress\Models\CourseModel;
use LearnPress\Models\UserModel;
use Throwable;
use WP_Widget;

defined( 'ABSPATH' ) || exit;

/**
 * Students list widget class.
 *
 * @author   ThimPress
 * @package  LearnPress/CourseReviewWidget/CourseReviewWidget
 * @version  3.0.2
 */
class CourseReviewWidget extends WP_Widget {
	use Singleton;

	/**
	 * CourseReviewWidget constructor.
	 */
	public function __construct() {
		parent::__construct(
			'lpr_course_review',
			__( 'Learnpress - Course Review', 'learnpress-course-review' )
		);
	}

	public function init() {
	}

	/**
	 * Front-end display
	 *
	 * @param array $args
	 * @param array $instance
	 */
	public function widget( $args, $instance ) {

		try {
			$course_id = $instance['course_id'] ?? 0;
			if ( empty( $instance['course_id'] ) ) {
				echo __( 'Please enter Course ID.', 'learnpress-course-review' );
				return;
			}

			echo $args['before_widget'];

			if ( ! empty( $instance['title'] ) ) {
				printf( '<div>%s</div>', $instance['title'] );
			}

			$courseModel = CourseModel::find( $course_id, true );
			$userModel   = UserModel::find( get_current_user_id(), true );
			if ( ! $courseModel ) {
				Template::print_message(
					__( 'Course is invalid', 'learnpress-course-review' ),
					'warning'
				);
			} else {
				do_action( 'learn-press/course-review/rating-reviews', $courseModel, $userModel );
			}
		} catch ( Throwable $e ) {
			Template::print_message(
				$e->getMessage(),
				'error'
			);
		}

		echo $args['after_widget'];
	}

	/**
	 * @param array $instance
	 *
	 * @return void
	 */
	public function form( $instance ) {
		$title     = $instance['title'] ?? __( 'Course Review', 'learnpress-course-review' );
		$course_id = $instance['course_id'] ?? '';
		?>
		<p>
			<label for="<?php echo $this->get_field_id( 'title' ); ?>">
				<?php _e( 'Title:', 'learnpress-course-review' ); ?>
			</label>
			<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>"
				name="<?php echo $this->get_field_name( 'title' ); ?>" type="text"
				value="<?php echo esc_attr( $title ); ?>">
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'course_id' ); ?>">
				<?php _e( 'Course ID:', 'learnpress-course-review' ); ?>
			</label>
			<input style="width: 100%" type="number" value="<?php echo esc_attr( $course_id ); ?>"
				id="<?php echo $this->get_field_id( 'course_id' ); ?>"
				name="<?php echo $this->get_field_name( 'course_id' ); ?>">
		</p>
		<?php
	}

	/**
	 * Sanitize widget form values as they are saved.
	 *
	 * @param array $new_instance
	 * @param array $old_instance
	 *
	 * @return array
	 */
	public function update( $new_instance, $old_instance ) {
		$instance              = array();
		$instance['title']     = ( ! empty( $new_instance['title'] ) ) ? strip_tags( $new_instance['title'] ) : '';
		$instance['course_id'] = ( ! empty( $new_instance['course_id'] ) ) ? strip_tags( $new_instance['course_id'] ) : '';

		return $instance;
	}
}
