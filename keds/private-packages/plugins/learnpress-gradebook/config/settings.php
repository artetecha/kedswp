<?php
/**
 * Fields settings Gradebook.
 *
 * @package LearnPress\Gradebook
 * @since   4.1.1
 */

defined( 'ABSPATH' ) || exit;

return apply_filters(
	'learn-press/gradebook/settings',
	array(
		array(
			'type' => 'title',
		),
		array(
			'title'   => esc_html__( 'Instructor access', 'learnpress-gradebook' ),
			'id'      => 'gradebook_allow_instructors',
			'default' => 'no',
			'type'    => 'checkbox',
			'desc'    => esc_html__( 'Allow instructors and co-instructors to view Gradebook data', 'learnpress-gradebook' ),
		),
		array(
			'type' => 'sectionend',
		),
	)
);
