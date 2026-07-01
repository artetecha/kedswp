<?php
/**
 * Template for displaying price of single course.
 *
 * This template can be overridden by copying it to yourtheme/learnpress/single-course/price.php.
 *
 * @author   ThimPress
 * @package  Learnpress/Templates
 * @version  3.0.0
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit();

$course = learn_press_get_course();
?>

<div class="course-price">
	<span class="label"><?php echo esc_html__( 'Price', 'eduma' ); ?></span>

	<?php echo wp_kses_post( $course->get_course_price_html() ); ?>

</div>

