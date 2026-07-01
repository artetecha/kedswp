<?php
namespace LearnPress\Upsell;

class Settings extends \LP_Abstract_Settings_Page {

	public function __construct() {
		$this->id   = 'upsell';
		$this->text = __( 'Upsell', 'learnpress-upsell' );

		parent::__construct();
	}

	public function get_settings( $section = '', $tab = '' ) {
		return apply_filters(
			'learn-press/admin/upsell-settings/general',
			array(
				array(
					'type'  => 'title',
					'title' => esc_html__( 'Package', 'learnpress-upsell' ),
				),
				array(
					'title'       => esc_html__( 'Package slug', 'learnpress-upsell' ),
					'id'          => 'package[slug]',
					'default'     => 'package',
					'type'        => 'text',
					'placeholder' => 'package',
				),
				array(
					'title'   => esc_html__( 'Archive Package page', 'learnpress-upsell' ),
					'id'      => 'package[archive]',
					'default' => '',
					'type'    => 'pages-dropdown',
				),
				array(
					'title'             => esc_html__( 'Archive Package per page', 'learnpress-upsell' ),
					'id'                => 'package[per_page]',
					'default'           => '10',
					'custom_attributes' => array(
						'min' => '1',
					),
					'type'              => 'number',
				),
				array(
					'title'   => esc_html__( 'Enable Course Tab', 'learnpress-upsell' ),
					'desc'    => esc_html__( 'Enable Packages tab in single course.', 'learnpress-upsell' ),
					'id'      => 'package[is_course_tab]',
					'default' => 'yes',
					'type'    => 'checkbox',
				),
				array(
					'title'             => esc_html__( 'Course tab Packages per page', 'learnpress-upsell' ),
					'desc'              => esc_html__( 'Packages per page in single course tab.', 'learnpress-upsell' ),
					'id'                => 'package[course_tab_limit]',
					'default'           => '4',
					'type'              => 'number',
					'custom_attributes' => array(
						'min' => '1',
					),
				),
				array(
					'type' => 'sectionend',
				),
				array(
					'type'  => 'title',
					'title' => esc_html__( 'Coupon', 'learnpress-upsell' ),
				),
				array(
					'title'   => esc_html__( 'Enable coupons', 'learnpress-upsell' ),
					'id'      => 'coupon[enable]',
					'default' => 'no',
					'type'    => 'checkbox',
				),
				array(
					'title'   => esc_html__( 'Show list coupons', 'learnpress-upsell' ),
					'id'      => 'coupon[show_list_coupons_checkout]',
					'default' => 'no',
					'type'    => 'checkbox',
				),
				array(
					'type' => 'sectionend',
				),
			)
		);
	}
}

return new Settings();
