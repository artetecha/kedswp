<?php
/**
 * Class LP_Assignment_BuddyPress.
 *
 * @author  ThimPress
 * @package LearnPress/Assignments/Classes
 * @version 3.0.0
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit();

if ( ! class_exists( 'LP_Assignment_BuddyPress' ) ) {

	/**
	 * Class LP_Assignment_BuddyPress
	 */
	class LP_Assignment_BuddyPress {

		/**
		 * LP_Assignment_BuddyPress constructor.
		 */
		public function __construct() {
			if ( ! class_exists( 'LP_Addon_BuddyPress_Preload' ) ) {
				return;
			}

			add_filter( 'learn-press/buddypress/profile-tabs', array( $this, 'assignment_tab' ) );

			add_action( 'learn-press/buddypress/bp-tab-content', array( $this, 'bp_tab_content' ) );
		}

		/**
		 * @param $tabs
		 *
		 * @return mixed
		 */
		public function assignment_tab( $tabs ) {

			$load = LP_Addon_BuddyPress::instance();

			foreach ( $tabs as $index => $tab ) {
				if ( isset( $tab['slug'] ) && $tab['slug'] == $load->get_tab_quizzes_slug() ) {
					array_splice(
						$tabs,
						$index + 1,
						0,
						array(
							array(
								'name'                    => __( 'Assignments', 'learnpress-assignments' ),
								'slug'                    => $this->get_tab_assignments_slug(),
								'show_for_displayed_user' => true,
								'screen_function'         => array( $load, 'bp_tab_content' ),
								'default_subnav_slug'     => 'all',
								'position'                => 100,
							),
						)
					);
					break;
				}
			}

			return $tabs;
		}

		/**
		 * @return mixed
		 */
		public function get_tab_assignments_slug() {
			$slugs = LearnPress::instance()->settings->get( 'profile_endpoints' );
			$slug  = '';
			if ( isset( $slugs['profile-assignments'] ) ) {
				$slug = $slugs['profile-assignments'];
			}
			if ( ! $slug ) {
				$slug = 'assignments';
			}

			return apply_filters( 'learn-press/bp-tab/assignments-slug', $slug );
		}

		/**
		 * @param $current_component
		 */
		public function bp_tab_content( $current_component ) {
			if ( $current_component == $this->get_tab_assignments_slug() ) {
				add_action( 'bp_template_content', array( $this, 'bp_tab_assignments_content' ) );
				bp_core_load_template( apply_filters( 'bp_core_template_plugin', 'members/single/plugins' ) );
			}
		}

		/**
		 * BuddyPress assignments profile content.
		 */
		public function bp_tab_assignments_content() {
			$args = array( 'user' => learn_press_get_current_user() );
			learn_press_assignment_get_template( 'compatible/learnpress-buddypress/profile/assignments.php', $args );
		}
	}
}

new LP_Assignment_BuddyPress();
