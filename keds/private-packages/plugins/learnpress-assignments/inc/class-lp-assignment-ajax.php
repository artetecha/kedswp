<?php
/**
 * Class LP_Assignment_AJAX.
 *
 * @author  ThimPress
 * @version 1.0.0
 * @since 4.1.2
 */

use LearnPress\Models\CourseModel;
use LearnPress\Models\UserItems\UserCourseModel;
use LearnPress\Models\UserModel;
use LearnPressAssignment\Models\AssignmentPostModel;
use LearnPressAssignment\Models\UserAssignmentModel;

defined( 'ABSPATH' ) || exit();

/**
 * Class LP_Assignment
 */
class LP_Assignment_AJAX {
	/**
	 * Constructor gets the post object and sets the ID for the loaded course.
	 *
	 * @param mixed $the_assignment
	 * @param mixed $args
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'register_ajax' ) );
	}

	/**
	 * Register ajax
	 *
	 * @see start_assignment
	 * @see retake_assignment
	 * @see process_assignment
	 * @see lpae_evaluate
	 */
	public function register_ajax() {
		$actions = array(
			'lpae-evaluate'       => 'lpae_evaluate',
		);

		foreach ( $actions as $action => $function ) {
			LP_Request::register_ajax( $action, array( __CLASS__, $function ) );
			//LP_Request::register( "lp-{$action}", array( __CLASS__, $function ) );
		}
	}

	/**
	 * Evaluate result
	 *
	 * @editor tungnx
	 * @modify 4.0.1
	 * @version 4.0.1
	 */
	public static function lpae_evaluate() {
		$page          = LP_Request::get_param( 'evaluate-page' );
		$assignment_id = LP_Request::get_param( 'assignment_id', 0, 'int' );
		$user_id       = LP_Request::get_param( 'user_id', 0, 'int' );
		$evaluate_page = get_page_link( get_option( 'assignment_evaluate_page_id' ) );
		if ( ! ( $evaluate_page === $page ) || ! $assignment_id || ! $user_id || 'post' !== strtolower( $_SERVER['REQUEST_METHOD'] ) ) {
			return;
		}

		$action       = LP_Request::get_param( 'action' );
		$user_item_id = LP_Request::get_param( 'user_item_id', 0, 'int' );
		$assignment   = LP_Assignment::get_assignment( $assignment_id );

		if ( ! $action || ! $user_item_id ) {
			return;
		}

		$mark = LP_Request::get_param( '_lp_evaluate_assignment_mark', 0 );

		if ( $action != 're-evaluate' ) {
			learn_press_update_user_item_meta( $user_item_id, '_lp_assignment_mark', $mark );

			learn_press_update_user_item_meta(
				$user_item_id,
				'_lp_assignment_instructor_note',
				LP_Request::get( '_lp_evaluate_assignment_instructor_note' )
			);

			$document = isset( $_POST['_lp_evaluate_assignment_document'] ) ? wp_unslash( array_filter( explode( ',', $_POST['_lp_evaluate_assignment_document'] ) ) ) : array();

			learn_press_update_user_item_meta(
				$user_item_id,
				'_lp_assignment_evaluate_upload',
				$document
			);

			learn_press_update_user_item_meta(
				$user_item_id,
				'_lp_assignment_evaluate_author',
				learn_press_get_current_user()->get_id()
			);
		}

		$course = learn_press_get_item_courses( $assignment_id );
		//$lp_course = learn_press_get_course( $course[0]->ID );
		//$user      = learn_press_get_user( $user_id );
		//$course_data = $user->get_course_data( $lp_course->get_id() );

		$user_curd = new LP_User_CURD();

		switch ( $action ) {
			case 'evaluate':
				learn_press_update_user_item_field( array( 'graduation' => ( $mark >= $assignment->get_data( 'passing_grade' ) ? 'passed' : 'failed' ) ), array( 'user_item_id' => $user_item_id ) );

				$user_curd->update_user_item_status( $user_item_id, 'evaluated' );
				//$course_data->calculate_course_results();
				do_action( 'learn-press/instructor-evaluated-assignment', $assignment_id, $user_id );
				break;
			case 're-evaluate':
				$user_curd->update_user_item_status( $user_item_id, 'completed' );
				do_action( 'learn-press/instructor-re-evaluated-assignment', $assignment_id, $user_id );
				break;
			default:
				break;
		}

		do_action( 'learn-press/save-evaluate-form', $action );
	}
}
