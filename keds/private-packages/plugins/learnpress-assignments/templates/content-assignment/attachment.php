<?php
/**
 * Template for displaying attachment of assignment.
 *
 * This template can be overridden by copying it to yourtheme/learnpress/addons/assignments/single-assignment/attachment.php.
 *
 * @author   ThimPress
 * @package  Learnpress/Assignments/Templates
 * @version  3.0.1
 */

defined( 'ABSPATH' ) || exit();

if ( ! isset( $assignmentModel ) ) {
	return;
}

$list_attachments = $assignmentModel->get_attachments_assignment();
$list_attachments = apply_filters( 'learn-press/assignment/student-attachment-list', $list_attachments, $assignmentModel );
?>

<?php if ( ! empty( $list_attachments ) ) : ?>
	<div class="learn_press_assignment_attachment">
		<h4 class="assignment-files-title"><?php echo esc_html__( 'Attachment Files', 'learnpress-assignments' ); ?></h4>

		<ul class="assignment-files assignment-documentations">
			<?php foreach ( $list_attachments as $att_id ) : ?>
				<li>
					<div class="assignment-file">
						<?php echo wp_get_attachment_link( $att_id, $size = 'none' ); ?>
						<span class="file-size"><?php echo size_format( filesize( get_attached_file( $att_id ) ), 2 ); ?></span>
					</div>
				</li>
			<?php endforeach; ?>
		</ul>
	</div>
<?php endif; ?>
