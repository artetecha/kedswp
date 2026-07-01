<?php
/**
 * Template for displaying instructor of single course.
 *
 * This template can be overridden by copying it to yourtheme/learnpress/single-course/instructor.php.
 *
 * @author   ThimPress
 * @package  Learnpress/Templates
 * @version  4.0.0
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit();

$course = learn_press_get_course();
if ( ! $course ) {
	return;
}
$instructor = $course->get_instructor();
if ( ! $instructor instanceof LP_User ) {
	return;
}
?>

<div class="course-author">
	<?php echo wp_kses_post( $instructor->get_profile_picture() ); ?>
	<div class="author-contain">
		<label itemprop="jobTitle"><?php esc_html_e( 'Teacher', 'eduma' ); ?></label>

		<div class="value" itemprop="name">
			<?php echo wp_kses_post( $course->get_instructor_html() ); ?>
		</div>
	</div>
</div>
