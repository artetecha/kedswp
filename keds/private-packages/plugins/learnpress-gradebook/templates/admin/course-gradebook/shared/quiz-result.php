<?php
/**
 * Quiz result summary.
 *
 * @var array $template_args Template arguments.
 */

defined( 'ABSPATH' ) || exit;

$finish_time      = $template_args['finish_time'] ?? '';
$question_correct = $template_args['question_correct'] ?? 0;
$question_count   = $template_args['question_count'] ?? 0;
$time_spent       = $template_args['time_spent'] ?? '';
$user_mark        = $template_args['user_mark'] ?? 0;
$mark             = $template_args['mark'] ?? 0;
$passing_grade    = $template_args['passing_grade'] ?? '';
$result           = $template_args['result'] ?? 0;
?>
<div class="lp-gradebook-quiz_detail">
	<h2><?php esc_html_e( 'Quiz Result', 'learnpress-gradebook' ); ?></h2>
	<p><?php esc_html_e( 'Finish time', 'learnpress-gradebook' ); ?>: <strong><?php echo esc_html( $finish_time ); ?></strong></p>
	<p><?php esc_html_e( 'Questions', 'learnpress-gradebook' ); ?>: <strong><?php echo esc_html( $question_correct ); ?> / <?php echo esc_html( $question_count ); ?></strong></p>
	<p><?php esc_html_e( 'Time spent', 'learnpress-gradebook' ); ?>: <strong><?php echo esc_html( $time_spent ); ?></strong></p>
	<p><?php esc_html_e( 'Marks', 'learnpress-gradebook' ); ?>: <strong><?php echo esc_html( $user_mark ); ?> / <?php echo esc_html( $mark ); ?></strong></p>
	<p><?php esc_html_e( 'Passing grade', 'learnpress-gradebook' ); ?>: <strong><?php echo esc_html( $passing_grade ); ?></strong></p>
	<p><?php esc_html_e( 'Result', 'learnpress-gradebook' ); ?>: <strong><?php echo esc_html( $result ); ?>%</strong></p>
</div>
