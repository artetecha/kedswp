<?php
/**
 * Assignments Evaluate Form shortcode.
 *
 * @version       3.1.0
 * @author        ThimPress
 * @package       Learnpress/Assignments/Shortcode
 * @deprecated   4.1.7
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit;

class LPASSIGNMENT_Shortcode_Evaluate_Form extends LPASSIGNMENT_Shortcodes {

	public $shortcode = 'assignment_evaluate_form';

	public function __construct() {
		parent::__construct();
	}

	function add_shortcode( $atts, $content = null ) {
		$assignment_id         = LP_Request::get_int( 'assignment_id' );
		$user_id               = LP_Request::get_int( 'user_id' );
		$evaluate_page         = get_option( 'assignment_evaluate_page_id' );
		$atts                  = wp_parse_args(
			$atts,
			array(
				'assignment_id'    => $assignment_id,
				'user_id'          => $user_id,
				'manager_page'     => get_page_link( $evaluate_page ),
				'evaluate_page_id' => $evaluate_page,
			)
		);
		$template_args         = array();
		$template_args['atts'] = $atts;
		$template              = 'frontend-editor/manager/evaluate.php';

		ob_start();
		do_action( 'lp_assignment_eval_wrapper_start' );
		learn_press_assignment_get_template( $template, $template_args );
		do_action( 'lp_assignment_eval_wrapper_end' );

		return ob_get_clean();
	}
}

new LPASSIGNMENT_Shortcode_Evaluate_Form();
