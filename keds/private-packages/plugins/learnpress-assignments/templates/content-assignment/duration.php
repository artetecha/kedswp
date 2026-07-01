<?php
/**
 * Template for displaying duration of current assignment user are doing.
 *
 * This template can be overridden by copying it to yourtheme/learnpress/addons/assignments/single-assignment/duration.php.
 *
 * @author   ThimPress
 * @package  Learnpress/Assignments/Templates
 * @version  3.0.1
 */

defined( 'ABSPATH' ) || exit();

if ( ! isset( $duration ) || ! isset( $duration_time ) ) {
	return;
}
?>

<div class="assignment-status">
	<div class="progress-items">
		<div class="progress-item assignment-countdown">
			<span class="progress-label">
				<?php
				echo $duration ? esc_html__( 'Time remaining', 'learnpress-assignments' ) :
					( absint( $duration_time ) ? esc_html__( 'Time Up!', 'learnpress-assignments' ) :
						esc_html__( 'Unlimited Time', 'learnpress-assignments' ) );
				?>
			</span>
			<span class="progress-number"><?php echo $duration > 0 ? ' --:--:-- ' : ''; ?></span>
		</div>
	</div>
</div>
