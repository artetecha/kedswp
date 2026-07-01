<?php
/**
 * All template hooks for LearnPress Assignment templates.
 *
 * @author  ThimPress
 * @package LearnPress/Assignments/Hooks
 * @version 4.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * @see learn_press_content_item_assignment_duration
 * @see learn_press_content_item_assignment_title
 * @see learn_press_content_item_assignment_buttons
 */
add_action( 'learn-press/before-content-item-summary/lp_assignment', 'learn_press_content_item_assignment_duration', 5 );
add_action( 'learn-press/before-content-item-summary/lp_assignment', 'learn_press_content_item_assignment_title', 10 );
add_action( 'learn-press/before-content-item-summary/lp_assignment', 'learn_press_assignment_result', 15 );

/**
 * @see learn_press_content_item_assignment_buttons
 * @see learn_press_course_finish_button
 */
add_action( 'learn-press/after-content-item-summary/lp_assignment', 'learn_press_content_item_assignment_display', 10 );
add_action( 'learn-press/after-content-item-summary/lp_assignment', 'learn_press_content_item_assignment_buttons', 15 );
add_action( 'learn-press/after-content-item-summary/lp_assignment', LearnPress::instance()->template( 'course' )->func( 'course_finish_button' ), 15 );

/**
 * @see lp_assignment_task_introduction
 * @see learn_press_content_item_assignment_attachment
 * @see lp_assignment_intro_hook
 */
// add_action( 'learn-press/content-item-summary/lp_assignment', 'learn_press_assignment_result', 5 );
add_action( 'learn-press/content-item-summary/lp_assignment', 'lp_assignment_intro_hook', 10 );
add_action( 'learn-press/content-item-summary/lp_assignment', 'lp_assignment_task_introduction', 15 );
add_action( 'learn-press/content-item-summary/lp_assignment', 'lp_assignment_attachment', 20 );
// add_action( 'learn-press/content-item-summary/lp_assignment', 'learn_press_content_item_assignment_display', 25 );

/**
 * @see learn_press_assignment_nav_buttons
 * @see learn_press_assignment_start_button
 * @see learn_press_assignment_after_sent
 * @see learn_press_assignment_answer
 * @see learn_press_assignment_retake
 */
add_action( 'learn-press/assignment-buttons', 'learn_press_assignment_nav_buttons', 5 );
add_action( 'learn-press/assignment-buttons', 'learn_press_assignment_start_button', 10 );
add_action( 'learn-press/assignment-buttons', 'learn_press_assignment_after_sent', 15 );
add_action( 'learn-press/assignment-buttons', 'learn_press_assignment_answer', 20 );
add_action( 'learn-press/assignment-buttons', 'learn_press_assignment_retake', 25 );

/**
 * @see lp_assignments_setup_shortcode_page_content
 */
add_filter( 'the_content', 'lp_assignments_setup_shortcode_page_content' );

/**
 * @see lp_assignments_add_item_user_progress
 */
add_action( 'learn-press/user-item-progress', 'lp_assignments_add_item_user_progress', 10, 3 );
