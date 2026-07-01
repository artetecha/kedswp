<?php
namespace LearnPress\Upsell\Package;

use LearnPress;
use LP_Addon_Upsell_Preload;

class Core_Functions {

	protected static $instance = null;

	public function is_package_taxonomy() {
		return is_tax( get_object_taxonomies( LP_PACKAGE_CPT ) );
	}

	public function get_page_id( $page ) {
		if ( $page === 'archive' ) {
			$page = \LP_Settings::instance()->get( 'package.archive' );
		}

		return ! empty( $page ) ? absint( $page ) : - 1;
	}

	public function template_path() {
		return apply_filters( 'learnpress/upsell/package/template-path', 'learnpress-upsell/' );
	}

	public function plugin_path() {
		return untrailingslashit( LP_ADDON_UPSELL_PACKAGE_PATH );
	}

	public function get_template_part( $slug, $name = '' ) {
		if ( $name ) {
			$template = locate_template(
				array(
					"{$slug}-{$name}.php",
					$this->template_path() . "{$slug}-{$name}.php",
				)
			);

			if ( ! $template ) {
				$fallback = $this->plugin_path() . "/templates/{$slug}-{$name}.php";
				$template = file_exists( $fallback ) ? $fallback : '';
			}
		}

		if ( ! $template ) {
			$template = locate_template(
				array(
					"{$slug}.php",
					$this->template_path() . "{$slug}.php",
				)
			);
		}

		$template = apply_filters( 'learnpress_package_get_template_part', $template, $slug, $name );

		if ( $template ) {
			load_template( $template, false );
		}
	}

	public function get_template( $template_name, $args = array(), $template_path = '', $default_path = '' ) {
		$template = $this->locate_template( $template_name, $template_path, $default_path );

		$filter_template = apply_filters( 'learnpress_package_get_template', $template, $template_name, $args, $template_path, $default_path );

		if ( $filter_template !== $template ) {
			$template = $filter_template;
		}

		$action_args = array(
			'template_name' => $template_name,
			'template_path' => $template_path,
			'located'       => $template,
			'args'          => $args,
		);

		if ( ! empty( $args ) && is_array( $args ) ) {
			extract( $args );
		}

		include $action_args['located'];
	}

	public function locate_template( $template_name, $template_path = '', $default_path = '' ) {
		if ( ! $template_path ) {
			$template_path = $this->template_path();
		}

		if ( ! $default_path ) {
			$default_path = $this->plugin_path() . '/templates/';
		}

		if ( empty( $template ) ) {
			$template = locate_template(
				array(
					trailingslashit( $template_path ) . $template_name,
					$template_name,
				)
			);
		}

		if ( ! $template ) {
			$template = $default_path . $template_name;
		}

		return apply_filters( 'learnpress_package_locate_template', $template, $template_name, $template_path );
	}

	public function placeholder_img( $size = 'learnpress_package_thumbnail', $attr = '' ) {
		$dimensions        = array(
			'width'  => 600,
			'height' => 600,
			'crop'   => 1,
		);
		$placeholder_image = get_option( 'learnpress_package_placeholder_image', 0 );

		$default_attr = array(
			'class' => 'learnpress_package_thumbnail-placeholder wp-post-image',
			'alt'   => esc_html__( 'Placeholder', 'learnpress-upsell' ),
		);

		$attr = wp_parse_args( $attr, $default_attr );

		if ( wp_attachment_is_image( $placeholder_image ) ) {
			$image_html = wp_get_attachment_image( $placeholder_image, $size, false, $attr );
		} else {
			$image     = LearnPress::instance()->image( 'no-image.png' );
			$hwstring  = image_hwstring( $dimensions['width'], $dimensions['height'] );
			$attribute = array();

			foreach ( $attr as $name => $value ) {
				$attribute[] = esc_attr( $name ) . '="' . esc_attr( $value ) . '"';
			}

			$image_html = '<img src="' . esc_url( $image ) . '" ' . $hwstring . implode( ' ', $attribute ) . '/>';
		}

		return apply_filters( 'learnpress_package_placeholder_img', $image_html, $size, $dimensions );
	}

	public function get_packages_by_course_id( $course_id, $limit = 10, $page = 1 ) {
		global $wpdb;

		// Get all post_id with meta_key = _lp_course_package and meta_value = $course_id
		$query = $wpdb->prepare( "SELECT post_id FROM {$wpdb->postmeta} JOIN {$wpdb->posts} ON {$wpdb->posts}.ID = {$wpdb->postmeta}.post_id WHERE meta_key = %s AND meta_value = %d AND {$wpdb->posts}.post_status = %s", '_lp_package_courses', $course_id, 'publish' );

		if ( ! empty( $limit ) ) {
			$offset = ( $page - 1 ) * $limit;
			$query .= $wpdb->prepare( ' LIMIT %d, %d', $offset, $limit );
		}

		$package_ids = $wpdb->get_col( $query );

		return $package_ids;
	}

	public function count_packages_by_course_id( $course_id ) {
		global $wpdb;

		// Get all post_id with meta_key = _lp_course_package and meta_value = $course_id
		$query = $wpdb->prepare( "SELECT COUNT(post_id) FROM {$wpdb->postmeta} JOIN {$wpdb->posts} ON {$wpdb->posts}.ID = {$wpdb->postmeta}.post_id WHERE meta_key = %s AND meta_value = %d AND {$wpdb->posts}.post_status = %s", '_lp_package_courses', $course_id, 'publish' );

		$count = $wpdb->get_var( $query );

		return $count;
	}

	public function check_all_courses_is_finished( $package_id ) {
		$user_id = get_current_user_id();
		if ( ! $user_id ) {
			return false;
		}

		$user       = learn_press_get_user( $user_id );
		$package    = new Package( $package_id );
		$course_ids = $package->get_course_list();
		foreach ( $course_ids as $course_id ) {
			$user_course = $user->get_course_data( $course_id );
			if ( ! $user_course->is_finished() || ! $user_course->is_passed() ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Nest elements by tags
	 *
	 * @param array $els [ 'html_tag_open' => 'html_tag_close' ]
	 * @param string $main_content
	 *
	 * @return string
	 */
	public function nest_elements( array $els = [], string $main_content = '' ): string {
		$html = '';
		foreach ( $els as $tag_open => $tag_close ) {
			$html .= $tag_open;
		}

		$html .= $main_content;

		foreach ( $els as $tag_close ) {
			$html .= $tag_close;
		}

		return $html;
	}

	/**
	 * Display sections
	 *
	 * @param array $sections ['name_section' => 'text html', 'link_template' => '']
	 *
	 * @return void
	 */
	public function print_sections( array $sections = [], array $args = [] ) {
		foreach ( $sections as $section ) {
			if ( ! is_array( $section ) ) {
				continue;
			}

			foreach ( $section as $type => $val ) {
				switch ( $type ) {
					case 'link_templates':
						LP_Addon_Upsell_Preload::$addon->get_template( $val, $args );
						break;
					default:
						if ( is_string( $val ) ) {
							//$allow_tag = wp_kses_allowed_html( 'post' );
							//echo wp_kses( $section, $allow_tag );
							echo $val;
						}
						break;
				}
			}
		}
	}

	// instance
	public static function instance() {
		if ( ! self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}
}
