<?php
/**
 * All functions for LearnPress Assignment templates.
 *
 * @author  ThimPress
 * @package LearnPress/Assignments/Functions
 * @version 3.0.1
 * @deprecated 4.1.6
 */

use LearnPress\Models\CourseModel;
use LearnPress\Models\UserModel;
use LearnPressAssignment\Models\AssignmentPostModel;
use LearnPressAssignment\Models\UserAssignmentModel;
use LearnPressAssignment\TemplateHooks\SingleAssignmentTemplate;

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'learn_press_content_item_assignment_duration' ) ) {
	/**
	 * Assignment duration.
	 *
	 * @version 1.0.1
	 * @deprecated 4.1.6
	 */
	function learn_press_content_item_assignment_duration() {
		_deprecated_function( __FUNCTION__, '4.1.6' );
		return;
		$course              = CourseModel::find( get_the_ID(), true );
		$user                = UserModel::find( get_current_user_id(), true );
		$assignment          = LP_Global::course_item();
		$userAssignmentModel = UserAssignmentModel::find( $user->get_id(), $course->get_id(), $assignment->get_id(), true );
		if ( ! $userAssignmentModel || ! in_array(
			$userAssignmentModel->get_status(),
			[ $userAssignmentModel::STATUS_STARTED, $userAssignmentModel::STATUS_DOING ]
		) ) {
			return;
		}

		$duration      = lp_user_assignment_get_time_remaining( $userAssignmentModel );
		$duration_time = get_post_meta( $assignment->get_id(), '_lp_duration', true );

		LP_Addon_Assignment_Preload::$addon->get_template(
			'content-assignment/duration.php',
			array(
				'duration'      => $duration,
				'duration_time' => $duration_time,
			)
		);
	}
}

if ( ! function_exists( 'learn_press_content_item_assignment_title' ) ) {
	/**
	 * Assignment title.
	 * @deprecated 4.1.6
	 */
	function learn_press_content_item_assignment_title() {
		_deprecated_function( __FUNCTION__, '4.1.6' );
		return;
		LP_Addon_Assignment_Preload::$addon->get_template( 'content-assignment/title.php' );
	}
}

if ( ! function_exists( 'lp_assignment_intro_hook' ) ) {
	/**
	 * Assignment introduction.
	 * @deprecated 4.1.6
	 */
	function lp_assignment_intro_hook() {
		_deprecated_function( __FUNCTION__, '4.1.6' );
		return;
		$course          = CourseModel::find( get_the_ID(), true );
		$user            = UserModel::find( get_current_user_id(), true );
		$assignment      = LP_Global::course_item();
		$assignmentModel = AssignmentPostModel::find( $assignment->get_id(), true );
		if ( ! $assignmentModel ) {
			return;
		}

		echo SingleAssignmentTemplate::instance()->html_information( $assignmentModel );
		echo SingleAssignmentTemplate::instance()->html_description( $assignmentModel );
	}
}

if ( ! function_exists( 'learn_press_content_item_assignment_buttons' ) ) {
	/**
	 * Assignment buttons.
	 * @deprecated 4.1.6
	 */
	function learn_press_content_item_assignment_buttons() {
		_deprecated_function( __FUNCTION__, '4.1.6' );
		return;
		learn_press_assignment_get_template( 'content-assignment/buttons.php' );
	}
}

if ( ! function_exists( 'lp_assignment_task_introduction' ) ) {
	/**
	 * Assignment content.
	 * @depreacted 4.1.6
	 */
	function lp_assignment_task_introduction() {
		_deprecated_function( __FUNCTION__, '4.1.6' );
		return;
		$course          = CourseModel::find( get_the_ID(), true );
		$assignment      = LP_Global::course_item();
		$assignmentModel = AssignmentPostModel::find( $assignment->get_id(), true );

		if ( ! $course ) {
			return;
		}

		$user_id = get_current_user_id();
		if ( ! $user_id ) {
			return;
		}

		$userAssignmentModel = UserAssignmentModel::find( get_current_user_id(), $course->get_id(), $assignment->get_id(), true );
		if ( ! $userAssignmentModel ) {
			return;
		}

		echo SingleAssignmentTemplate::instance()->html_introduction_task( $assignmentModel );
	}
}

if ( ! function_exists( 'lp_assignment_attachment' ) ) {
	/**
	 * Assignment attachment.
	 * @depreacted 4.1.6
	 */
	function lp_assignment_attachment() {
		_deprecated_function( __FUNCTION__, '4.1.6' );
		return;
		$course          = CourseModel::find( get_the_ID(), true );
		$assignment      = LP_Global::course_item();
		$assignmentModel = AssignmentPostModel::find( $assignment->get_id(), true );

		if ( ! $course ) {
			return;
		}

		$user_id = get_current_user_id();
		if ( ! $user_id ) {
			return;
		}

		$userAssignmentModel = UserAssignmentModel::find( get_current_user_id(), $course->get_id(), $assignment->get_id(), true );
		if ( ! $userAssignmentModel ) {
			return;
		}

		echo SingleAssignmentTemplate::instance()->html_attachments_file( $assignmentModel );
	}
}
if ( ! function_exists( 'learn_press_content_item_assignment_display' ) ) {
	/**
	 * Assignment attachment.
	 * @depreacted 4.1.6
	 */
	function learn_press_content_item_assignment_display() {
		_deprecated_function( __FUNCTION__, '4.1.6' );
		return;

		$course     = learn_press_get_course();
		$user       = learn_press_get_current_user();
		$assignment = LP_Global::course_item();

		if ( ! $course ) {
			return;
		}

		if ( $user->has_course_status( $course->get_id(), array( 'finished' ) ) || $user->has_item_status(
			array(
				'started',
				'doing',
				'completed',
				'evaluated',
			),
			$assignment->get_id(),
			$course->get_id()
		)
		) {
			learn_press_assignment_get_template( 'content-assignment/buttons/show-hide-content.php', compact( 'user', 'course', 'assignment' ) );
		}
	}
}
if ( ! function_exists( 'learn_press_assignment_start_button' ) ) {
	/**
	 * Start button.
	 * @depreacted 4.1.6
	 */
	function learn_press_assignment_start_button() {
		_deprecated_function( __FUNCTION__, '4.1.6' );
		return;

		$course     = learn_press_get_course();
		$user       = learn_press_get_current_user();
		$assignment = LP_Global::course_item();

		if ( ! $course ) {
			return;
		}

		if ( $user->has_course_status( $course->get_id(), array( 'finished' ) ) || ! $user->has_course_status( $course->get_id(), array( 'enrolled' ) ) || $user->has_item_status(
			array(
				'started',
				'doing',
				'completed',
				'evaluated',
			),
			$assignment->get_id(),
			$course->get_id()
		)
		) {
			return;
		}
		learn_press_assignment_get_template( 'content-assignment/buttons/start.php' );
	}
}


if ( ! function_exists( 'learn_press_assignment_nav_buttons' ) ) {
	/**
	 * Nav button.
	 * @depreacted 4.1.6
	 */
	function learn_press_assignment_nav_buttons() {
		_deprecated_function( __FUNCTION__, '4.1.6' );
		return;

		$course = learn_press_get_course();
		if ( ! $course ) {
			return;
		}

		$user = learn_press_get_current_user();
		if ( ! $user ) {
			return;
		}

		$assignment = LP_Global::course_item();

		if ( ! $user->has_item_status( array( 'started', 'doing' ), $assignment->get_id(), $course->get_id() ) ) {
			return;
		}

		learn_press_assignment_get_template( 'content-assignment/buttons/controls.php' );
	}
}


if ( ! function_exists( 'learn_press_assignment_after_sent' ) ) {
	/**
	 * Sent button.
	 * @depreacted 4.1.6
	 */
	function learn_press_assignment_after_sent() {
		_deprecated_function( __FUNCTION__, '4.1.6' );
		return;

		$course = learn_press_get_course();

		if ( ! $course ) {
			return;
		}

		$user       = learn_press_get_current_user();
		$assignment = LP_Global::course_item();
		if ( ! $user->has_item_status(
			array(
				'completed',
			),
			$assignment->get_id(),
			$course->get_id()
		) ) {
			return;
		}

		learn_press_assignment_get_template( 'content-assignment/buttons/sent.php' );
	}
}

if ( ! function_exists( 'learn_press_assignment_result' ) ) {
	/**
	 * Result button.
	 * @depreacted 4.1.6
	 */
	function learn_press_assignment_result() {
		_deprecated_function( __FUNCTION__, '4.1.6' );
		return;

		$course     = learn_press_get_course();
		$user       = learn_press_get_current_user();
		$assignment = LP_Global::course_item();

		if ( ! $course ) {
			return;
		}

		if ( ! $user->has_item_status(
			array(
				'evaluated',
			),
			$assignment->get_id(),
			$course->get_id()
		) ) {
			return;
		}

		learn_press_assignment_get_template( 'content-assignment/result.php' );
	}
}
if ( ! function_exists( 'learn_press_assignment_answer' ) ) {
	/**
	 * Result button.
	 * @depreacted 4.1.6
	 */
	function learn_press_assignment_answer() {
		_deprecated_function( __FUNCTION__, '4.1.6' );
		return;
		$course     = learn_press_get_course();
		$user       = learn_press_get_current_user();
		$assignment = LP_Global::course_item();

		if ( ! $course ) {
			return;
		}

		if ( ! $user->has_item_status(
			array(
				'evaluated',
			),
			$assignment->get_id(),
			$course->get_id()
		) ) {
			return;
		}

		learn_press_assignment_get_template( 'content-assignment/buttons/answer.php' );
	}
}

if ( ! function_exists( 'learn_press_assignment_retake' ) ) {
	/**
	 * Retake button.
	 * @deprecated 4.1.6
	 */
	function learn_press_assignment_retake() {
		_deprecated_function( __FUNCTION__, '4.1.6' );
		return;

		$user_item_id = 0;
		$course       = learn_press_get_course();
		$user         = learn_press_get_current_user();
		$assignment   = LP_Global::course_item();
		$retake_count = $assignment->get_data( 'retake_count' );

		if ( ! $course ) {
			return;
		}

		if ( ! $retake_count ) {
			return;
		}

		$course_data = $user->get_course_data( $course->get_id() );
		if ( ! $course_data ) {
			return;
		}

		if ( $course_data->is_finished() ) {
			return;
		}

		$assignment_item = $course_data->get_item( $assignment->get_id() );
		if ( $assignment_item ) {
			$user_item_id = $assignment_item->get_user_item_id();
		}

		$redo_time = learn_press_get_user_item_meta( $user_item_id, '_lp_assignment_retaken', true );
		$redo_time = ( $redo_time ) ? $redo_time : 0;

		if ( ! $user->has_item_status(
			array(
				'completed',
				'evaluated',
			),
			$assignment->get_id(),
			$course->get_id()
		) || $retake_count <= $redo_time ) {
			return;
		}

		learn_press_assignment_get_template( 'content-assignment/buttons/retake.php' );
	}
}
//add_filter( 'learn-press/can-view-assignment', 'learn_press_assignment_filter_can_view_item', 10, 4 );
/**
 * @deprecated 4.1.6
 */
function learn_press_assignment_filter_can_view_item( $view, $assignment_id, $user_id, $course_id ) {
	_deprecated_function( __FUNCTION__, '4.1.6' );
	return $view;

	$user           = learn_press_get_user( $user_id );
	$_lp_submission = get_post_meta( $course_id, '_lp_submission', true );

	if ( $_lp_submission === 'yes' ) {
		if ( ! $user->is_logged_in() ) {
			return 'not-logged-in';
		} elseif ( ! $user->has_enrolled_course( $course_id ) ) {
			return 'not-enrolled';
		}
	}

	return $view;
}

if ( ! function_exists( 'lp_assignments_setup_shortcode_page_content' ) ) {
	/**
	 * @deprecated 4.1.6
	 */
	function lp_assignments_setup_shortcode_page_content( $content ) {
		_deprecated_function( __FUNCTION__, '4.1.6' );
		return $content;

		global $post;

		if ( ! $post ) {
			return $content;
		}

		$page_id = $post->ID;

		if ( ! $page_id ) {
			return $content;
		}

		if ( get_option( 'assignment_students_man_page_id' ) == $page_id ) {
			$current_content = get_post( $page_id )->post_content;
			if ( strpos( $current_content, '[assignment_students_manager' ) === false ) {
				$content = '[' . apply_filters( 'assignment_students_manager_shortcode_tag', 'assignment_students_manager' ) . ']';
			}
		} elseif ( get_option( 'assignment_evaluate_page_id' ) == $page_id ) {
			$current_content = get_post( $page_id )->post_content;
			if ( strpos( $current_content, '[assignment_evaluate_form' ) === false ) {
				$content = '[' . apply_filters( 'assignment_students_evaluate_shortcode_tag', 'assignment_evaluate_form' ) . ']';
			}
		}

		return do_shortcode( $content );
	}
}


/**
 * Add item assignment by user progress in sidebar course
 */
if ( ! function_exists( 'lp_assignments_add_item_user_progress' ) ) {
	/**
	 * @deprecated 4.1.6
	 */
	function lp_assignments_add_item_user_progress( $course_results, $course_data, $user ) {
		_deprecated_function( __FUNCTION__, '4.1.6' );
		return;

		learn_press_assignment_get_template(
			'single-course/user-progress.php',
			array(
				'course_results' => $course_results,
				'course_data'    => $course_data,
				'user'           => $user,
			)
		);
	}
}
