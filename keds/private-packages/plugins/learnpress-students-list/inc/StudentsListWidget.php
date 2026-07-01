<?php

namespace LearnPress\StudentsList;

use LearnPress\Helpers\Singleton;
use LearnPress\Models\CourseModel;
use WP_Widget;

defined( 'ABSPATH' ) || exit;

/**
 * Students list widget class.
 *
 * @author   ThimPress
 * @package  LearnPress/Students-List/Classes
 * @version  3.0.2
 */
class StudentsListWidget extends WP_Widget {
	use Singleton;

	/**
	 * LP_Students_List constructor.
	 */
	public function __construct() {
		parent::__construct(
			'students_list_widget',
			__( 'Learnpress - Students List', 'learnpress-students-list' )
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
		wp_enqueue_style( 'addon-lp-students-list' );
		wp_enqueue_script( 'addon-lp-students-list' );

		if ( empty( $instance['course_id'] ) ) {
			echo __( 'Please enter Course ID.', 'learnpress-students-list' );
			return;
		}

		echo $args['before_widget'];

		if ( ! empty( $instance['title'] ) ) {
			printf( '<div>%s</div>', $instance['title'] );
		}

		$course_id  = sanitize_text_field( $instance['course_id'] );
		$course_ids = explode( ',', $course_id );
		foreach ( $course_ids as $course_id ) {
			$courseModel = CourseModel::find( $course_id, true );
			if ( ! $courseModel ) {
				continue;
			}

			printf(
				'%s: <a href="%s">%s</a>',
				esc_html__( 'Course', 'learnpress-students-list' ),
				$courseModel->get_permalink(),
				$courseModel->get_title()
			);

			do_action( 'lp-addon-students-list/students-list/layout', $courseModel );
		}

		echo $args['after_widget'];
	}

	/**
	 * @param array $instance
	 *
	 * @return void
	 */
	public function form( $instance ) {
		$title     = ! empty( $instance['title'] ) ? $instance['title'] : __( 'Students List', 'learnpress-students-list' );
		$course_id = ! empty( $instance['course_id'] ) ? $instance['course_id'] : '';
		$number    = ! empty( $instance['number_student'] ) ? $instance['number_student'] : '';
		?>
		<p>
			<label for="<?php echo $this->get_field_id( 'title' ); ?>">
				<?php _e( 'Title:', 'learnpress-students-list' ); ?>
			</label>
			<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>"
				name="<?php echo $this->get_field_name( 'title' ); ?>" type="text"
				value="<?php echo esc_attr( $title ); ?>">
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'course_id' ); ?>">
				<?php _e( 'Course ID:', 'learnpress-students-list' ); ?>
			</label>
			<input style="width: 100%" type="text" value="<?php echo esc_attr( $course_id ); ?>"
				id="<?php echo $this->get_field_id( 'course_id' ); ?>"
				name="<?php echo $this->get_field_name( 'course_id' ); ?>">
			<span class="field-description">
				<i><?php echo esc_html__( 'Course IDs separated by commas (,)', 'learnpress-students-list' ); ?></i>
			</span>
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'number_student' ); ?>">
				<?php _e( 'Number students to show:', 'learnpress-students-list' ); ?>
			</label>
			<input type="number" class="tiny-text" size="3" min="1" step="1"
				value="<?php echo esc_attr( $number ); ?>"
				id="<?php echo $this->get_field_id( 'number_student' ); ?>"
				name="<?php echo $this->get_field_name( 'number_student' ); ?>">
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
		$instance                   = array();
		$instance['title']          = ( ! empty( $new_instance['title'] ) ) ? strip_tags( $new_instance['title'] ) : '';
		$instance['course_id']      = ( ! empty( $new_instance['course_id'] ) ) ? strip_tags( $new_instance['course_id'] ) : '';
		$instance['number_student'] = ( ! empty( $new_instance['number_student'] ) ) ? strip_tags( $new_instance['number_student'] ) : '';

		return $instance;
	}
}
