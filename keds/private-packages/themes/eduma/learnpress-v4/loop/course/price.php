<?php
/**
 * Template for displaying price of course within the loop.
 *
 * This template can be overridden by copying it to yourtheme/learnpress/loop/course/price.php.
 *
 * @author  ThimPress
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
$class = ( $course->has_sale_price() ) ? ' has-origin' : '';
if ( $course->is_free() ) {
	$class .= ' free';
}

?>

<div class="course-price<?php echo esc_attr( $class ); ?>" itemprop="offers" itemscope
	itemtype="https://schema.org/Offer">

	<?php echo wp_kses_post( $course->get_course_price_html() ); ?>

</div>
