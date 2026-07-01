<?php
/**
 * Template for displaying header of single course popup.
 *
 * This template can be overridden by copying it to yourtheme/learnpress/single-course/header.php.
 *
 * @author   ThimPress
 * @package  Learnpress/Templates
 * @version  4.0.4
 */

defined( 'ABSPATH' ) || exit();

if ( ! isset( $course ) || ! isset( $user ) || ! isset( $percentage ) ||
	! isset( $completed_items ) || ! isset( $total_items )
) {
	return;
}

?>

<div id="popup-header">
	<div class="popup-header__inner">
		<?php if ( $user->has_enrolled_or_finished( $course->get_id() ) ) : ?>
			<div class="items-progress" data-total-items="<?php echo esc_attr( $total_items ); ?>">
			<span class="number">
				<?php
				printf(
					__(
						'<span class="items-completed">%1$s</span> of %2$d items',
						'learnpress'
					),
					esc_html( $completed_items ),
					esc_html( $course->count_items() )
				);
				?>
			</span>
				<div class="learn-press-progress">
					<div class="learn-press-progress__active" data-value="<?php echo esc_attr( $percentage ); ?>%;">
					</div>
				</div>
			</div>
		<?php endif; ?>
		<div class="thim-course-item-popup-right">
			<input type="checkbox" id="sidebar-toggle" class="toggle-content-item"/>
			<a href="<?php echo esc_url_raw( $course->get_permalink() ); ?>" class="back_course"><i
					class="edu-times"></i></a>
		</div>
	</div>
</div>
