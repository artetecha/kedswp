<?php
/**
 * Assignments list of students's answer shortcode.
 *
 * @version       3.1.0
 * @author        ThimPress
 * @package       Learnpress/Assignments/Shortcode
 * @deprecated   4.1.7
 */

defined( 'ABSPATH' ) || exit;

class LPASSIGNMENT_Shortcode_Students_manager extends LPASSIGNMENT_Shortcodes {

	public $shortcode = 'assignment_students_manager';

	public function __construct() {
		parent::__construct();
	}

	function add_shortcode( $atts, $content = null ) {
		$assignment_id         = LP_Request::get_int( 'assignment_id' );
		$manager_page          = get_option( 'assignment_students_man_page_id' );
		$atts                  = wp_parse_args(
			$atts,
			array(
				'assignment_id' => $assignment_id,
				'manager_page'  => get_page_link( $manager_page ),
			)
		);
		$template_args         = array();
		$template_args['atts'] = $atts;
		$template              = 'frontend-editor/manager/students.php';

		ob_start();
		do_action( 'lp_assignment_man_wrapper_start' );
		learn_press_assignment_get_template( $template, $template_args );
		do_action( 'lp_assignment_man_wrapper_end' );

		return ob_get_clean();
	}
}

new LPASSIGNMENT_Shortcode_Students_manager();
