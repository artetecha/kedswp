<?php
/**
 * Template for displaying Start quiz button.
 *
 * This template can be overridden by copying it to yourtheme/learnpress/addons/assignments/content-assignment/buttons/controls.php.
 *
 * @author  ThimPress
 * @package  Learnpress/Assignments/Templates
 * @version  3.0.2
 */

defined( 'ABSPATH' ) || exit();

$assignment_db = LP_Assigment_DB::getInstance();
$course        = learn_press_get_course();
/** @var LP_Assignment $current_assignment */
$current_assignment = LP_Global::course_item();
$user               = learn_press_get_current_user();
$course_data        = $user->get_course_data( $course->get_id() );
if ( ! $course_data ) {
	return;
}

$user_item = $course_data->get_item( $current_assignment->get_id() );
if ( ! $user_item ) {
	return;
}

$user_item_id = $user_item->get_user_item_id();
$content      = $assignment_db->get_extra_value( $user_item_id, $assignment_db::$answer_note_key );
if ( is_null( $content ) ) {
	$content = '';
}
$uploaded_files        = learn_press_assignment_get_uploaded_files( $user_item_id );
$file_extensions       = $current_assignment->get_file_extension();
$file_accept           = '.' . str_replace( ',', ',.', $file_extensions );
$file_amount           = (float) $current_assignment->get_files_amount();
$disable               = '';
$uploaded_files_amount = $uploaded_files ? count( $uploaded_files ) : 0;
$max_file_size         = get_post_meta( $current_assignment->get_id(), '_lp_upload_file_limit', true );
$max_file_size         = $max_file_size ?? 2;

if ( $file_amount - $uploaded_files_amount < 1 ) {
	$disable = 'disabled="true"';
}
?>

<?php do_action( 'learn-press/before-assignment-controls-button' ); ?>

<form name="save-assignment" class="save-assignment" method="post" enctype="multipart/form-data">

	<h2 class="save-assignment-title"><?php esc_html_e( 'Answer', 'learnpress-assignments' ); ?></h2>

	<?php wp_editor( $content, 'assignment-editor-frontend', array( 'media_buttons' => false ) ); ?>

	<div class="assignment-upload">
		<?php if ( $file_amount != 0 ) : ?>
			<input name="_lp_upload_file[]" <?php echo esc_attr( $disable ); ?> class="form-control" accept="<?php echo esc_attr( $file_accept ); ?>" id="_lp_upload_file" type="file" multiple="true"/>
			<div class="assignment-upload-note">
				<p><?php echo sprintf( __( 'File number limit: %s', 'learnpress-assignments' ), $file_amount - $uploaded_files_amount ); ?></p>
				<p><?php echo sprintf( __( 'Maximum file size: %sMB', 'learnpress-assignments' ), $max_file_size ); ?></p>
				<p><?php echo sprintf( __( 'Allowed file types: %s', 'learnpress-assignments' ), strpos( '*', $file_extensions ) === false ? $file_extensions : '*' ); ?></p>
			</div>
			<span class="assignments-notice-filetype" style="display: none!important;">
			<?php
			echo '( ' . __( 'Maximum amount of files you can upload more: ', 'learnpress-assignments' ) . '<strong id="assignment-file-amount-allow" max-file-size="' . esc_attr( $max_file_size ) . '">' . ( $file_amount - $uploaded_files_amount ) . '</strong>.';
			if ( strpos( '*', $file_extensions ) === false ) {
				?>
				<?php echo __( ' And allow upload only these types: ', 'learnpress-assignments' ) . $file_extensions; ?>
				<?php
			}
			echo sprintf( __( '. And file size is smaller than <b>%sMB</b> )', 'learnpress-assignments' ), $max_file_size );
			?>
			</span>
		<?php endif; ?>

		<?php if ( $uploaded_files_amount ) : ?>
			<h4 class="assignment-uploaded-files"><?php esc_html_e( 'Your Uploaded File(s):', 'learnpress-assignments' ); ?></h4>

			<div class="learn-press-assignment-uploaded">
				<ul class="assignment-files assignment-uploaded">
					<?php
					foreach ( $uploaded_files as $key_file => $file ) {
						$filetype = $file->type;
						$mime     = 'file';

						if ( $filetype != '' ) {
							$mime = preg_replace( '#[^\/]*\/#', '', $filetype );
						}
						?>

						<li class="assignment-uploaded-file" id="assignment-uploaded-file-<?php echo esc_attr( $key_file ); ?>">
							<div class="assignment_file_thumb assignment_thumb_error">
								<div class="assignment_file_dummy ui-widget-content">
									<span class="ui-state-disabled"><?php echo $mime; ?>
										<span class="assignment-file-size"><?php echo '( ' . learn_press_assignment_filesize_format( $file->size ) . ' )'; ?></span>
									</span>
								</div>
							</div>

							<div class="assignment_file_name" title="<?php echo $file->filename; ?>">
								<span class="assignment_file_name_wrapper">
									<a href="<?php echo esc_url( get_site_url() . $file->url ); ?>" target="_blank"><?php echo $file->filename; ?>
									</a>
								</span>
								<span class="file-size"><?php echo size_format( filesize( ABSPATH . $file->file ), 2 ); ?></span>
							</div>
							<div class="assignment_file_action">
								<a href="#" data-confirm="<?php esc_attr_e( 'Do you want to remove this file?', 'learnpress-assignments' ); ?>" useritem_id="<?php echo esc_attr( $user_item_id ); ?>" ajax_url="<?php echo esc_attr( admin_url( 'admin-ajax.php' ) ); ?>" title="remove" name="<?php echo esc_attr( $file->file ); ?>" order="<?php echo esc_attr( $key_file ); ?>" class="assignment_action_icon"></a>
								<input type="hidden" name="assignment-nonce" id="assignment-file-nonce-<?php echo $key_file; ?>" value="<?php echo wp_create_nonce( 'delete_assignment_upload_file_' . $key_file ); ?>" />
							</div>
							<div class="assignment_file_uploaded_time"><?php if ( $file->saved_time ) { ?>
									<span class="saved-time"><?php echo '( ' . $file->saved_time . ' )'; ?></span>
								<?php } ?>
							</div>
						</li>
					<?php } ?>
				</ul>
			</div>
		<?php endif; ?>
	</div>

	<div class="assignment-buttons-area">
		<?php do_action( 'learn-press/begin-assignment-save-button' ); ?>
		<button id="assignment-button-left"
				data-confirm="<?php esc_attr_e( 'Do you want to save the answer? Your uploaded files will be replaced by the new ones, if any.', 'learnpress-assignments' ); ?>"
				type="submit"
				name="controls-button" value="Save"
				class="button assignment-button-left lp-button"><?php _e( 'Save Draft', 'learnpress-assignments' ); ?></button>
		<?php do_action( 'learn-press/end-assignment-save-button' ); ?>

		<?php do_action( 'learn-press/begin-assignment-send-button' ); ?>
		<button id="assignment-button-right"
				data-confirm="<?php esc_attr_e( 'Do you want to submit the answer? After submission, you can not change the answer or resubmit.', 'learnpress-assignments' ); ?>"
				type="submit"
				name="controls-button" value="Send"
				class="button assignment-button-right lp-button"><?php _e( 'Submit', 'learnpress-assignments' ); ?></button>
		<?php do_action( 'learn-press/end-assignment-send-button' ); ?>
	</div>

	<?php learnpress_assignment_action( 'controls', $current_assignment->get_id(), $course->get_id(), true ); ?>
	<input type="hidden" name="noajax" value="yes">

</form>

<?php do_action( 'learn-press/after-assignment-controls-button' ); ?>
