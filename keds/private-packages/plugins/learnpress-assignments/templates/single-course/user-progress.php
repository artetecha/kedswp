<?php
/**
 * Template for displaying item assignment progress of single course.
 *
 * @author   ThimPress
 * @package  Learnpress/Templates
 * @version  1.0.0
 * @sicne 4.0.2
 * @author Minhpd
 */

if ( empty( $course_results['items']['assignment'] ) || empty( $user ) || empty( $course_data ) ) {
	return;
}

if ( ! $course_results['items']['assignment']['total'] ) {
	return;
}

$total     = $course_results['items']['assignment']['total'];
$items     = $course_data->get_items();
$course_id = $course_data->get_id();
$user_id   = $user->get_id();
$completed = 0;
$evaluated = 0;

foreach ( $items as $item_course ) {

	$item_type = $item_course->get_data( 'item_type' );
	$item_id   = $item_course->get_id();

	if ( $item_type == LP_ASSIGNMENT_CPT ) {
		$result = learn_press_assignment_get_result( $item_id, $user_id, $course_id );
		if ( $result['status'] == 'evaluated' ) {
			++$evaluated;
			++$completed;
		}
		if ( $result['status'] == 'completed' ) {
			++$completed;
		}
	}
}

if ( $completed && $completed <= $total ) {
	$label = esc_html__( 'Assignments completed', 'learnpress-assignments' ); ?>
	<div class="items-progress">
		<h4 class="items-progress__heading">
			<?php echo wp_sprintf( '%s:', $label ); ?>
		</h4>
		<span class="number"><?php printf( '%1$d/%2$d', $completed, $total ); ?></span>
	</div>

	<?php
}

if ( $evaluated && $evaluated <= $total ) {
	$label = esc_html__( 'Assignments evaluated', 'learnpress-assignments' );
	?>
	<div class="items-progress">
		<h4 class="items-progress__heading">
			<?php echo wp_sprintf( '%s:', $label ); ?>
		</h4>
		<span class="number"><?php printf( '%1$d/%2$d', $evaluated, $total ); ?></span>
	</div>
	<?php
}


