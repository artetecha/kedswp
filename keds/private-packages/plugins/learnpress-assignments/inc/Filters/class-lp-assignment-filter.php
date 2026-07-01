<?php
/**
 * Class LP_Course_Filter
 *
 * @author  ThimPress
 * @package LearnPress/Classes/Filters
 * @version 1.0.0
 * @since 4.1.2
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit();

class LP_Assignment_Filter extends LP_Post_Type_Filter {
	/**
	 * @var string
	 */
	public $post_type = LP_ASSIGNMENT_CPT;
}
