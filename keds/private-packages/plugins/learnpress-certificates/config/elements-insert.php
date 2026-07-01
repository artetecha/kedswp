<?php
/**
 * List elements for certificate builder.
 *
 * Structure: category => [ list elements ]
 *
 * @version 4.2.0
 */

$list_general = apply_filters(
	'learn-press/certificate/builder/inserter-elements/general',
	[
		'text'    => [
			'class'      => 'lp-cert-insert-text',
			'label'      => __( 'Text Custom', 'learnpress-certificates' ),
			'icon'       => 'lp-cert-icon-text',
			'type_layer' => 'text-edit',
		],
		'qr_code' => [
			'class'      => 'lp-cert-insert-qr-code',
			'label'      => __( 'QR', 'learnpress-certificates' ),
			'icon'       => 'lp-cert-icon-qr',
			'type_layer' => 'qr_code',
		],
		'time'    => [
			'class'      => 'lp-cert-insert-time',
			'label'      => __( 'Current Time', 'learnpress-certificates' ),
			'icon'       => 'lp-cert-icon-time',
			'type_layer' => 'text-static',
		],
	]
);

$list_user = apply_filters(
	'learn-press/certificate/builder/inserter-elements/user',
	[
		'student_name'    => [
			'class'      => 'lp-cert-insert-student-name',
			'label'      => __( 'Student Name', 'learnpress-certificates' ),
			'icon'       => 'lp-cert-icon-student',
			'type_layer' => 'text-static',
		],
		'instructor_name' => [
			'class'      => 'lp-cert-insert-instructor-name',
			'label'      => __( 'Instructor Name', 'learnpress-certificates' ),
			'icon'       => 'lp-cert-icon-instructor',
			'type_layer' => 'text-static',
		],
	]
);

$list_course = apply_filters(
	'learn-press/certificate/builder/inserter-elements/course',
	[
		'course_title'             => [
			'class'      => 'lp-cert-insert-course-title',
			'label'      => __( 'Course Title', 'learnpress-certificates' ),
			'icon'       => 'lp-cert-icon-title',
			'type_layer' => 'text-static',
		],
		'course_description'       => [
			'class'      => 'lp-cert-insert-course-description',
			'label'      => __( 'Course Description', 'learnpress-certificates' ),
			'icon'       => 'lp-cert-icon-course-description',
			'type_layer' => 'text-static',
		],
		'course_short_description' => [
			'class'      => 'lp-cert-insert-course-short-description',
			'label'      => __( 'Short Description', 'learnpress-certificates' ),
			'icon'       => 'lp-cert-icon-course-short-description',
			'type_layer' => 'text-static',
		],
		'course_price'             => [
			'class'      => 'lp-cert-insert-course-price',
			'label'      => __( 'Course Price', 'learnpress-certificates' ),
			'icon'       => 'lp-cert-icon-course-price',
			'type_layer' => 'text-static',
		],
		'course_count_student'     => [
			'class'      => 'lp-cert-insert-course-count-student',
			'label'      => __( 'Student Count', 'learnpress-certificates' ),
			'icon'       => 'lp-cert-icon-course-student-count',
			'type_layer' => 'text-static',
		],
		'course_level'             => [
			'class'      => 'lp-cert-insert-course-level',
			'label'      => __( 'Course Level', 'learnpress-certificates' ),
			'icon'       => 'lp-cert-icon-course-level',
			'type_layer' => 'text-static',
		],
		'course_duration'          => [
			'class'      => 'lp-cert-insert-course-duration',
			'label'      => __( 'Course Duration', 'learnpress-certificates' ),
			'icon'       => 'lp-cert-icon-course-duration',
			'type_layer' => 'text-static',
		],
		'course_capacity'          => [
			'class'      => 'lp-cert-insert-course-capacity',
			'label'      => __( 'Max Students', 'learnpress-certificates' ),
			'icon'       => 'lp-cert-icon-course-max-students',
			'type_layer' => 'text-static',
		],
		'course_count_lesson'      => [
			'class'      => 'lp-cert-insert-course-count-lesson',
			'label'      => __( 'Lesson Count', 'learnpress-certificates' ),
			'icon'       => 'lp-cert-icon-course-lesson-count',
			'type_layer' => 'text-static',
		],
		'course_count_quiz'        => [
			'class'      => 'lp-cert-insert-course-count-quiz',
			'label'      => __( 'Quiz Count', 'learnpress-certificates' ),
			'icon'       => 'lp-cert-icon-course-quiz-count',
			'type_layer' => 'text-static',
		],
		'course_start_date'        => [
			'class'      => 'lp-cert-insert-course-start-date',
			'label'      => __( 'Course Start Date', 'learnpress-certificates' ),
			'icon'       => 'lp-cert-icon-date',
			'type_layer' => 'text-static',
		],
		'course_end_date'          => [
			'class'      => 'lp-cert-insert-course-end-date',
			'label'      => __( 'Course End Date', 'learnpress-certificates' ),
			'icon'       => 'lp-cert-icon-date',
			'type_layer' => 'text-static',
		],
	]
);

return apply_filters(
	'learn-press/certificate/builder/inserter-elements',
	[
		'generate' => [
			'label' => __( 'Generate', 'learnpress-certificates' ),
			'items' => $list_general,
		],
		'user'     => [
			'label' => __( 'User', 'learnpress-certificates' ),
			'items' => $list_user,
		],
		'course'   => [
			'label' => __( 'Course', 'learnpress-certificates' ),
			'items' => $list_course,
		],
	]
);
