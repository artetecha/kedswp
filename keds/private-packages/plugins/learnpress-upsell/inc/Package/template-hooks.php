<?php
use \LearnPress\Upsell\Package\Template_Functions;
use \LearnPress\Upsell\Package\Core_Functions;

//require_once LP_ADDON_UPSELL_PATH . '/inc/package/template_hooks/ArchivePackage.php';
//require_once LP_ADDON_UPSELL_PATH . '/inc/package/template_hooks/SinglePackage.php';
//require_once LP_ADDON_UPSELL_PATH . '/inc/package/template_hooks/SingleCoursePackage.php';

$template_class = Template_Functions::instance();

// Start Archive.
//add_action( 'learnpress_package/before_package_loop', array( $template_class, 'before_archive_package_html_start' ), 10 );
//
//add_action( 'learnpress_package/before_package_loop', array( $template_class, 'archive_result_count' ), 20 );
//
//add_action( 'learnpress_package/before_package_loop', array( $template_class, 'archive_ordering' ), 30 );
//
//add_action( 'learnpress_package/before_package_loop', array( $template_class, 'archive_search_form' ), 40 );
//
//add_action( 'learnpress_package/before_package_loop', array( $template_class, 'after_archive_package_html_end' ), 50 );

//add_action( 'learnpress_package/after_package_loop', array( $template_class, 'archive_pagination' ), 10 );

//add_action( 'learnpress_package/no_packages_found', array( $template_class, 'no_packages_found' ), 10 );
// End Archive.

// Start item image.
//add_action( 'learnpress_package/before_archive_item_image', array( $template_class, 'before_archive_item_image' ), 10 );

//add_action( 'learnpress_package/archive_item_image', array( $template_class, 'package_item_title_image' ), 20 );

//add_action( 'learnpress_package/after_archive_item_image', array( $template_class, 'after_archive_item_image' ), 30 );
// End item image.

// Start item meta.
//add_action( 'learnpress_package/before_archive_item_meta', array( $template_class, 'before_archive_item_meta' ), 10 );

//add_action( 'learnpress_package/archive_item_meta', array( $template_class, 'package_item_price' ), 20 );

//add_action( 'learnpress_package/archive_item_meta', array( $template_class, 'package_item_count_courses' ), 30 );

//add_action( 'learnpress_package/archive_item_meta', array( $template_class, 'package_item_title' ), 40 );

//add_action( 'learnpress_package/archive_item_meta', array( $template_class, 'package_item_content' ), 50 );

//add_action( 'learnpress_package/archive_item_meta', array( $template_class, 'package_item_view_detail' ), 60 );

//add_action( 'learnpress_package/after_archive_item_meta', array( $template_class, 'after_archive_item_meta' ), 70 );
// End item meta.

// Start single package.
//add_action( 'learnpress_package/single_package', array( $template_class, 'single_package_header_start' ), 10 );

//add_action( 'learnpress_package/single_package', array( $template_class, 'single_package_title' ), 20 );

//add_action( 'learnpress_package/single_package', array( $template_class, 'single_package_price' ), 30 );

//add_action( 'learnpress_package/single_package', array( $template_class, 'single_package_add_to_cart' ), 40 );

//add_action( 'learnpress_package/single_package', array( $template_class, 'single_package_certificate_btn' ), 50 );

//add_action( 'learnpress_package/single_package', array( $template_class, 'single_package_header_end' ), 60 );

//add_action( 'learnpress_package/single_package', array( $template_class, 'single_package_image' ), 70 );

//add_action( 'learnpress_package/single_package', array( $template_class, 'single_package_content' ), 80 );

//add_action( 'learnpress_package/single_package', array( $template_class, 'single_package_list_courses' ), 90 );

//add_action( 'learnpress_package/single_package', array( $template_class, 'related_package' ), 100 );
// End single package.

// Single course tab.
/*add_filter(
	'learn-press/course-tabs',
	function( $tabs ) use ( $template_class ) {
		$enable = \LP_Settings::instance()->get( 'package.is_course_tab', 'yes' );

		if ( $enable !== 'yes' ) {
			return $tabs;
		}

		$count_packages = Core_Functions::instance()->count_packages_by_course_id( get_the_ID() );

		if ( $count_packages <= 0 ) {
			return $tabs;
		}

		$tabs['package'] = array(
			'title'    => __( 'Package', 'learnpress-upsell' ),
			'priority' => 60,
			'callback' => array( $template_class, 'add_course_tab_package_callback' ),
		);

		return $tabs;
	},
	4
);*/
