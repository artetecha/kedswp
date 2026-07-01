<?php

namespace LP_PMS;

use LP_Abstract_Settings_Page;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Class LP_PMS_Setting
 */
class Settings extends LP_Abstract_Settings_Page {

	public function __construct() {
		$this->id   = 'membership';
		$this->text = esc_html__( 'Memberships', 'learnpress-paid-membership-pro' );

		parent::__construct();
	}

	public function output_section_general() {
		include LP_ADDON_PMPRO_PATH . '/inc/views/membership.php';
	}

	/**
	 * Get setting lp pms
	 *
	 * @param string $section
	 * @param string $tab
	 *
	 * @return array|array[]|bool|mixed
	 */
	public function get_settings( $section = '', $tab = '' ) {
		$link_levels_pms = '<a href="' . home_url( 'wp-admin/admin.php?page=pmpro-membershiplevels' ) . '">level</a>';

		$desc_auto_update_list_courses_on_level  = sprintf( '%s %s', __( 'LP Orders\'s users bought level PMS will update list courses when save', 'learnpress-paid-membership-pro' ), $link_levels_pms );
		$desc_auto_update_list_courses_on_level .= '<br><span style="color: red">' . __( 'Note: when remove courses on list, all progress of those courses of users will lose', 'learnpress-paid-membership-pro' ) . '</span>';

		return apply_filters(
			'lp-pms-fields-setting',
			array(
				array(
					'title' => __( 'Settings', 'learnpress-paid-membership-pro' ),
					'type'  => 'title',
				),
				array(
					'title'   => __( 'Always buy the course through membership', 'learnpress-paid-membership-pro' ),
					'id'      => 'buy_through_membership',
					'default' => 'no',
					'desc'    => __( 'Enable/Disable', 'learnpress-paid-membership-pro' ),
					'type'    => 'yes-no',
				),
				array(
					'type' => 'sectionend',
				),
				array(
					'title' => __( 'When membership level change', 'learnpress-paid-membership-pro' ),
					'type'  => 'title',
				),
				array(
					'title'   => __( 'Update access courses when level change list courses', 'learnpress-paid-membership-pro' ),
					'id'      => 'pmpro_update_access_course',
					'desc'    => $desc_auto_update_list_courses_on_level,
					'default' => 'no',
					'type'    => 'yes-no',
				),
				array(
					'title'   => __( 'Keep/Reset user\'s course progress', 'learnpress-paid-membership-pro' ),
					'id'      => 'pmpro_keep_course_progress',
					'desc'    => __( 'Controls what happens to a user’s existing course progress when they purchase a new Level that includes a course they have already started.', 'learnpress-paid-membership-pro' ),
					'default' => 'keep',
					'type'    => 'select',
					'options' => array(
						'keep'  => __( 'Keep progress', 'learnpress-paid-membership-pro' ),
						'reset' => __( 'Reset progress', 'learnpress-paid-membership-pro' ),
					),
				),
				array(
					'type' => 'sectionend',
				),
			)
		);
	}
}
