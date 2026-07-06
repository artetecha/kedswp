<?php
defined('ABSPATH') || exit;

$date_value = esc_attr( wp_date( get_option( 'date_format' ) ) );

$preview_fields = apply_filters(
	'learn-press/certificate/builder/preview-fields',
	[
		'student_name'             => [
			'label'       => __( 'Student Name', 'learnpress-certificates' ),
			'placeholder' => 'STUDENT_NAME',
			'value'       => 'John Doe',
		],
		'instructor_name'          => [
			'label'       => __( 'Instructor Name', 'learnpress-certificates' ),
			'placeholder' => 'INSTRUCTOR_NAME',
			'value'       => 'Mr. Tom',
		],
		'course_title'             => [
			'label'       => __( 'Course Title', 'learnpress-certificates' ),
			'placeholder' => 'COURSE_TITLE',
			'value'       => 'Web Development Masterclass',
		],
		'course_description'       => [
			'label'       => __( 'Course Description', 'learnpress-certificates' ),
			'placeholder' => 'COURSE_DESCRIPTION',
			'value'       => 'Learn web development from scratch with HTML, CSS, JavaScript and more.',
		],
		'course_short_description' => [
			'label'       => __( 'Short Description', 'learnpress-certificates' ),
			'placeholder' => 'COURSE_SHORT_DESCRIPTION',
			'value'       => 'A comprehensive web development course.',
		],
		'course_price'             => [
			'label'       => __( 'Course Price', 'learnpress-certificates' ),
			'placeholder' => 'COURSE_PRICE',
			'value'       => '$99.00',
		],
		'course_count_student'     => [
			'label'       => __( 'Student Count', 'learnpress-certificates' ),
			'placeholder' => 'COURSE_COUNT_STUDENT',
			'value'       => '150 Students',
		],
		'course_level'             => [
			'label'       => __( 'Course Level', 'learnpress-certificates' ),
			'placeholder' => 'COURSE_LEVEL',
			'value'       => 'Intermediate',
		],
		'course_duration'          => [
			'label'       => __( 'Course Duration', 'learnpress-certificates' ),
			'placeholder' => 'COURSE_DURATION',
			'value'       => '10 weeks',
		],
		'course_capacity'          => [
			'label'       => __( 'Max Students', 'learnpress-certificates' ),
			'placeholder' => 'COURSE_CAPACITY',
			'value'       => 'Unlimited',
		],
		'course_count_lesson'      => [
			'label'       => __( 'Lesson Count', 'learnpress-certificates' ),
			'placeholder' => 'COURSE_COUNT_LESSON',
			'value'       => '24 Lessons',
		],
		'course_count_quiz'        => [
			'label'       => __( 'Quiz Count', 'learnpress-certificates' ),
			'placeholder' => 'COURSE_COUNT_QUIZ',
			'value'       => '5 Quizzes',
		],
		'start_date'               => [
			'label'       => __( 'Course Start Date', 'learnpress-certificates' ),
			'placeholder' => 'COURSE_START_DATE',
			'value'       => $date_value,
		],
		'end_date'                 => [
			'label'       => __( 'Course End Date', 'learnpress-certificates' ),
			'placeholder' => 'COURSE_END_DATE',
			'value'       => $date_value,
		],
		'time'                     => [
			'label'       => __( 'Time', 'learnpress-certificates' ),
			'placeholder' => 'TIME',
			'value'       => $date_value,
		],
		'qr_code'                  => [
			'label'       => __( 'QR Code URL', 'learnpress-certificates' ),
			'placeholder' => 'QR_CODE',
			'value'       => 'https://thimpress.com/',
		],
	]
);
?>

<div class="lp-cert-preview-modal" style="display: none;">
	<div class="lp-cert-preview-modal__body">
		<div class="lp-cert-preview-modal__sidebar">
			<h3><?php esc_html_e('Preview Data', 'learnpress-certificates'); ?></h3>
			<div class="lp-cert-preview-fields">
				<?php foreach ( $preview_fields as $key => $field ) : ?>
					<div class="lp-cert-preview-field">
						<label for="preview-<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $field['label'] ?? '' ); ?></label>
						<input type="text"
							id="preview-<?php echo esc_attr( $key ); ?>"
							data-placeholder="<?php echo esc_attr( $field['placeholder'] ?? '' ); ?>"
							value="<?php echo esc_attr( $field['value'] ?? '' ); ?>">
					</div>
				<?php endforeach; ?>
			</div>
			<div class="lp-cert-preview-actions">
				<button type="button" class="lp-cert-preview-clear"><?php esc_html_e('Clear All', 'learnpress-certificates'); ?></button>
				<button type="button" class="lp-cert-preview-apply"><?php esc_html_e('Apply', 'learnpress-certificates'); ?></button>
			</div>
		</div>
		<div class="lp-cert-preview-modal__content">
			<div class="lp-cert-preview-content-header">
				<div class="lp-cert-download-dropdown">
					<button type="button" class="lp-cert-preview-download">
						<?php esc_html_e('Download', 'learnpress-certificates'); ?>
						<span class="dashicons dashicons-arrow-down-alt2"></span>
					</button>
					<ul class="lp-cert-download-menu">
						<li>
							<button type="button" class="lp-cert-download-option" data-format="png">
								<span class="dashicons dashicons-format-image"></span>
								<?php esc_html_e('PNG', 'learnpress-certificates'); ?>
							</button>
						</li>
						<li>
							<button type="button" class="lp-cert-download-option" data-format="pdf">
								<span class="dashicons dashicons-media-document"></span>
								<?php esc_html_e('PDF', 'learnpress-certificates'); ?>
							</button>
						</li>
					</ul>
				</div>
				<button type="button" class="lp-cert-preview-modal__close">
					<i class="lp-cert-icon lp-cert-icon-remove"></i>
				</button>
			</div>
			<div class="lp-cert-preview-canvas-wrapper">
				<canvas id="lp-cert-preview-canvas"></canvas>
			</div>
		</div>
	</div>
</div>
