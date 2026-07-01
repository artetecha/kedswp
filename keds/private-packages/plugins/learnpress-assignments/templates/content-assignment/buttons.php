<?php
/**
 * Template for displaying form action of assignment.
 *
 * This template can be overridden by copying it to yourtheme/learnpress/addons/assignments/content-assignment/buttons.php.
 *
 * @author   ThimPress
 * @package  Learnpress/Assignments/Templates
 * @version  3.0.0
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit(); ?>

<div class="lp-assignment-buttons">

	<?php do_action( 'learn-press/before-assignment-buttons' ); ?>

	<?php
	/**
	 * @see learn_press_assignment_nav_buttons - 5
	 * @see learn_press_assignment_start_button - 10
	 * @see learn_press_assignment_after_sent - 15
	 * @see learn_press_assignment_result - 15
	 * @see learn_press_assignment_retake - 20
	 */
	do_action( 'learn-press/assignment-buttons' );
	?>

	<?php do_action( 'learn-press/after-assignment-buttons' ); ?>

</div>
