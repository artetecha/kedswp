<?php
require_once THIM_DIR . 'inc/widgets/form-login-register.php';

if ( ! function_exists( 'thim_form_search_popup' ) ) {
	function thim_form_search_popup( $placeholder = null, $heading = false ) {
		?>
		<div class="courses-searching layout-overlay">
			<div class="search-popup-bg"></div>
			<?php if ( $heading ) : ?>
				<div class="search-popup-heading">
					<h2 class="search-popup-title"><?php echo esc_html__( 'Search', 'eduma' ); ?></h2>
					<i class="<?php echo eduma_font_icon( 'close' ); ?>"></i>
				</div>
			<?php endif; ?>
			<form method="get" action="<?php echo esc_url( get_post_type_archive_link( 'lp_course' ) ); ?>">
				<input type="text" value="" name="c_search" placeholder="<?php echo esc_attr( $placeholder ); ?>"
						class="thim-s form-control courses-search-input" autocomplete="off"
						aria-label="<?php echo esc_attr( $placeholder ); ?>"/>
				<input type="hidden" value="course" name="ref"/>
				<button type="submit" aria-label="<?php echo esc_html__( 'Search', 'eduma' ); ?>"><i
						class="edu-search"></i></button>
				<span class="widget-search-close"></span>
			</form>
			<ul class="courses-list-search list-unstyled"></ul>
		</div>
		<?php
	}
}

add_filter( 'thim_register_shortcode', 'thim_register_elements' );

if ( ! function_exists( 'thim_register_elements' ) ) {
	/**
	 * @param $elements
	 *
	 * @return mixed
	 */
	function thim_register_elements() {

		// elements want to add
		$elements = array(
			'general'                      => array(
				'button',
				'accordion',
				'carousel-categories',
				'carousel-post',
				'countdown-box',
				'counters-box',
				'empty-space',
				'gallery-images',
				'gallery-posts',
				'google-map',
				'heading',
				'icon-box',
				'image-box',
				'landing-image',
				'link',
				'list-post',
				'login-form',
				'multiple-images',
				'single-images',
				'social',
				'tab',
				'testimonials',
				'timetable',
				'twitter',
				'video',
				'navigation-menu',
			),
			'LearnPress'                   => array(
				'course-categories',
				'courses',
				'courses-searching',
				'list-instructors',
				//              'one-course-instructors',
			),
			'LP_Co_Instructor_Preload'     => array(
				'one-course-instructors',
			),
			'LP_Addon_Collections_Preload' => array(
				'courses-collection',
			),
			'THIM_Our_Team'                => array(
				'our-team',
			),
			'Thim_Portfolio'               => array(
				'portfolio',
			),
			'WPEMS'                        => array(
				'tab-event',
				'list-event',
			),
		);

		if ( class_exists( 'Thim_EL_Kit' ) ) {
			$elements['general'][] = 'tabs-post';
		}

		return $elements;
	}
}

add_filter( 'thim_shortcode_group_name', 'thim_shortcode_group_name' );

if ( ! function_exists( 'thim_shortcode_group_name' ) ) {
	function thim_shortcode_group_name() {
		return esc_html__( 'Thim Shortcodes', 'eduma' );
	}
}

// change folder shortcodes to widgets
if ( ! function_exists( 'thim_custom_folder_shortcodes' ) ) {
	function thim_custom_folder_shortcodes() {
		return 'widgets';
	}
}
add_filter( 'thim_custom_folder_shortcodes', 'thim_custom_folder_shortcodes' );

// don't support folder groups
add_filter( 'thim_support_folder_groups', '__return_false' );

if ( ! function_exists( 'thim_ekit_get_widget_template' ) ) {
	function thim_ekit_get_widget_template( $widget_base, $args = array(), $template_name = 'base' ) {
		if ( is_array( $args ) && isset( $args ) ) {
			extract( $args );
		}

		if ( false === strpos( $template_name, '.php' ) ) {
			$template_name .= '.php';
		}

		$parent_path = get_template_directory() . '/inc/widgets/' . $widget_base . '/tpl/' . $template_name;
		$child_path  = get_stylesheet_directory() . '/inc/widgets/' . $widget_base . '/' . $template_name;

		if ( file_exists( $child_path ) ) {
			$child_theme_dir = realpath( get_stylesheet_directory() );
			$child_path      = realpath( $child_path );
			if ( strpos( $child_path, $child_theme_dir ) === 0 ) {
				$template_path = $child_path;
			} else {
				_doing_it_wrong( __FUNCTION__, sprintf( '<code>%s</code> does not exist.', $template_name ), '1.0.0' );

				return;
			}
		} elseif ( file_exists( $parent_path ) ) {
			$parent_path = realpath( $parent_path );
			$theme_dir   = realpath( get_template_directory() );
			if ( strpos( $parent_path, $theme_dir ) === 0 ) {
				$template_path = $parent_path;
			} else {
				_doing_it_wrong( __FUNCTION__, sprintf( '<code>%s</code> does not exist.', $template_name ), '1.0.0' );

				return;
			}
		} else {
			_doing_it_wrong( __FUNCTION__, sprintf( '<code>%s</code> does not exist.', $template_name ), '1.0.0' );

			return;
		}

		require $template_path;
	}
}

// disable mega menu in thim-core
if ( class_exists( 'Thim_EL_Kit' ) ) {
	$ekits_module_settings = get_option( 'thim_ekits_module_settings' );
	if ( ( ! $ekits_module_settings || ! empty( $ekits_module_settings['megamenu'] ) ) ) {
		add_filter( 'thim-support-mega-menu', '__return_false' );
	}
}
