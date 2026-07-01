<?php
/**
 * Template for displaying Assignment show/hide content buttons
 *
 * This template can be overridden by copying it to yourtheme/learnpress/addons/assignments/content-assignment/buttons/show-hide-content.php.
 *
 * @author  ThimPress
 * @package  Learnpress/Assignments/Templates
 * @version  3.0.2
 * @editor tungnx
 * @modify 4.0.1
 */

defined( 'ABSPATH' ) || exit();
if ( ! $course ) {
	return;
}
if ( ! $user ) {
	return;
}
if ( ! $assignment ) {
	return;
}
$user_item_data = $user->get_item_data( $assignment->get_id(), $course->get_id() );
if ( ! $user_item_data ) {
	return;
}
$user_item_id = $user_item_data->get_user_item_id();
if ( ! $user_item_id ) {
	return;
}
$is_show_content = learn_press_get_user_item_meta( $user_item_id, 'show_assignment_content', true );
$button_text     = $is_show_content == 'yes' ? __( 'Show Task', 'learnpress-assignments' ) : __( 'Hide Task', 'learnpress-assignments' );
$data_show       = $is_show_content == 'yes' ? 'no' : 'yes';
?>
<button class="show-assignment-content" id="show-assignment-content" data-show="<?php esc_attr_e( $data_show ); ?>"><?php esc_html_e( $button_text ); ?></button>