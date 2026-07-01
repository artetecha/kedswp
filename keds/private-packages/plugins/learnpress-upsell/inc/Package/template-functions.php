<?php
namespace LearnPress\Upsell\Package;

class Template_Functions {

	protected static $instance = null;

	public function __construct() {
		//add_action( 'the_post', array( $this, 'setup_global_data' ) );
	}

	/*public function setup_global_data( $post ) {
		unset( $GLOBALS['learnpress_package'] );

		if ( is_int( $post ) ) {
			$post = get_post( $post );
		}

		if ( empty( $post->post_type ) || $post->post_type !== LP_PACKAGE_CPT ) {
			return;
		}

		$GLOBALS['learnpress_package'] = new Package( $post );

		return $GLOBALS['learnpress_package'];
	}*/

	public function page_title( $echo = true ) {
		if ( is_search() ) {
			$page_title = sprintf( __( 'Search results: &ldquo;%s&rdquo;', 'learnpress-upsell' ), get_search_query() );

			if ( get_query_var( 'paged' ) ) {
				$page_title .= sprintf( __( '&nbsp;&ndash; Page %s', 'learnpress-upsell' ), get_query_var( 'paged' ) );
			}
		} elseif ( is_tax() ) {
			$page_title = single_term_title( '', false );
		} else {
			$archive_page_id = Core_Functions::instance()->get_page_id( 'archive' );
			$page_title      = get_the_title( $archive_page_id );
		}

		$page_title = apply_filters( 'learnpress_package/page_title', $page_title );

		if ( $echo ) {
			echo $page_title;
		} else {
			return $page_title;
		}
	}

	public function query_package_tab_in_course( $course_id, $page = 1, $limit = 4 ) {
		$setting = \LP_Settings::instance()->get( 'package.course_tab_limit', 4 );

		if ( ! empty( $setting ) ) {
			$limit = absint( $setting );
		}

		$package_ids = Core_Functions::instance()->get_packages_by_course_id( $course_id, $limit, $page );

		if ( empty( $package_ids ) ) {
			return false;
		}

		$count_packages = Core_Functions::instance()->count_packages_by_course_id( $course_id );

		$total_page = 0;

		if ( $count_packages > $limit ) {
			$total_page = ceil( $count_packages / $limit );
		}

		$packages = array();

		foreach ( $package_ids as $package_id ) {
			$packages[] = new Package( $package_id );
		}

		return array(
			'packages'   => $packages,
			'total_page' => $total_page,
		);
	}

	public static function instance() {
		if ( ! self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}
}

Template_Functions::instance();
