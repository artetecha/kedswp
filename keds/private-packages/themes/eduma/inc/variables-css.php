<?php

namespace Eduma;

class Variables_Options {

	public function __construct() {
		add_filter( 'thim_get_var_css_customizer', array( $this, 'get_options' ), 10 );
		add_filter( 'thim_get_var_css_customizer', array( $this, 'thim_custom_color_header_single_page' ), 20 );
		add_action( 'clean_term_cache', array( $this, 'clear_cache_taxonomy' ), 10, 2 );
	}

	/**
	 * Sanitize CSS color/value to prevent CSS injection.
	 * Allows: hex colors, rgb/rgba/hsl, named colors, numbers, units.
	 *
	 * @param string $value
	 * @return string
	 */
	public function thim_sanitize_css_color( $value ) {
		return preg_replace( '/[^a-zA-Z0-9#,.()\s%]/', '', trim( (string) $value ) );
	}

	/**
	 * Get a theme option value, falling back to the default.
	 *
	 * @param string $name
	 * @param mixed  $value_default
	 * @return mixed
	 */
	public function thim_get_theme_option( $name = '', $value_default = '' ) {
		$data = get_theme_mods();
		return isset( $data[ $name ] ) ? $data[ $name ] : $value_default;
	}

	public function get_options() {
		$css        = '';
		$tablet_css = '';
		$mobile_css = '';

		$theme_options = array(
			// Header
			'thim_body_primary_color'                => '#ffb606',
			'thim_body_secondary_color'              => '#4caf50',
			'thim_button_text_color'                 => '#333',
			'thim_button_hover_color'                => '#e6a303',
			'thim_border_color'                      => '#eee',
			'thim_placeholder_color'                 => '#999',
			'top_info_course'                        => array(
				'background_color' => '#273044',
				'text_color'       => '#fff',
			),
			'thim_footer_font_title'                 => array(
				'variant'        => get_theme_mod( 'thim_footer_font_title_font_weight', 700 ),
				'font-size'      => '14px',
				'line-height'    => '40px',
				'text-transform' => 'uppercase',
			),
			'thim_top_heading_title_align'           => 'left',
			'thim_top_heading_title_font'            => array(
				'size-desktop'   => '48px',
				'size-mobile'    => '35px',
				'text-transform' => 'uppercase',
				'weight'         => 'bold',
			),
			'thim_top_heading_padding'               => array(
				'top'    => array(
					'desktop' => '90px',
					'tablet'  => '',
					'mobile'  => '50px',
				),
				'bottom' => array(
					'desktop' => '90px',
					'tablet'  => '',
					'mobile'  => '50px',
				),
			),
			'thim_breacrumb_font_size'               => '1em',
			'thim_breacrumb_color'                   => '#666',
			'thim_breacrumb_bg_color'                => '',
			'thim_breacrumb_border_color'            => '',
			'thim_course_price_color'                => '#f24c0a',
			// Logo
			'thim_width_logo'                        => '155px',
			// Toolbar
			'thim_bg_color_toolbar'                  => '#111',
			'thim_text_color_toolbar'                => '#ababab',
			'thim_link_color_toolbar'                => '#fff',
			'thim_link_hover_color_toolbar'          => '#fff',
			'thim_toolbar'                           => array(
				'variant'        => get_theme_mod( 'thim_toolbar_font_weight', 600 ),
				'font-size'      => '12px',
				'line-height'    => '30px',
				'text-transform' => 'none',
			),
			'thim_toolbar_border_type'               => 'dashed',
			'thim_toolbar_border_size'               => '1px',
			'thim_link_color_toolbar_border_button'  => '#ddd',
			// Main Menu
			'thim_bg_main_menu_color'                => 'rgba(255,255,255,0)',
			'thim_main_menu'                         => array(
				'variant'        => get_theme_mod( 'thim_main_menu_font_weight', 600 ),
				'font-size'      => '14px',
				'line-height'    => '1.3em',
				'text-transform' => 'uppercase',
			),
			'thim_main_menu_font_weight'             => '600',
			'thim_main_menu_text_color'              => '#fff',
			'thim_main_menu_text_hover_color'        => '#fff',
			// Sticky Menu
			'thim_sticky_bg_main_menu_color'         => '#fff',
			'thim_sticky_main_menu_text_color'       => '#333',
			'thim_sticky_main_menu_text_hover_color' => '#333',
			// Sub Menu
			'thim_sub_menu_bg_color'                 => '#fff',
			'thim_sub_menu_border_color'             => 'rgba(43,43,43,0)',
			'thim_sub_menu_text_color'               => '#999',
			'thim_sub_menu_text_color_hover'         => '#333',
			// Mobile Menu
			'thim_bg_mobile_menu_color'              => '#232323',
			'thim_mobile_menu_text_color'            => '#777',
			'thim_mobile_menu_text_hover_color'      => '#fff',
			// Footer
			'thim_footer_font_size'                  => '14px',

			'thim_bg_switch_layout_style'            => '#f5f5f5',
			'thim_padding_switch_layout_style'       => '10px',

			'thim_font_body'                         => array(
				'font-family' => 'Roboto',
				'variant'     => '400',
				'font-size'   => '15px',
				'line-height' => '1.7em',
				'color'       => '#666666',
			),
			'thim_font_title'                        => array(
				'font-family' => 'Roboto Slab',
				'color'       => '#333333',
				'variant'     => '700',
			),
			'thim_font_h1'                           => array(
				'font-size'      => '36px',
				'line-height'    => '1.6em',
				'text-transform' => 'none',
			),
			'thim_font_h2'                           => array(
				'font-size'      => '28px',
				'line-height'    => '1.6em',
				'text-transform' => 'none',
			),
			'thim_font_h3'                           => array(
				'font-size'      => '24px',
				'line-height'    => '1.6em',
				'text-transform' => 'none',
			),
			'thim_font_h4'                           => array(
				'font-size'      => '18px',
				'line-height'    => '1.6em',
				'text-transform' => 'none',
				'variant'        => get_theme_mod( 'thim_font_title_variant', 600 ),
			),
			'thim_font_h5'                           => array(
				'font-size'      => '16px',
				'line-height'    => '1.6em',
				'text-transform' => 'none',
				'variant'        => get_theme_mod( 'thim_font_title_variant', 600 ),
			),
			'thim_font_h6'                           => array(
				'font-size'      => '16px',
				'line-height'    => '1.4em',
				'text-transform' => 'none',
				'variant'        => get_theme_mod( 'thim_font_title_variant', 600 ),
			),
			'thim_font_title_sidebar'                => array(
				'font-size'      => '18px',
				'line-height'    => '1.4em',
				'text-transform' => 'uppercase',
			),
			'thim_font_button'                       => array(
				'variant'        => 'regular',
				'font-size'      => '13px',
				'line-height'    => '1.6em',
				'text-transform' => 'uppercase',
			),
			'thim_preload_style'                     => array(
				'background' => '#fff',
				'color'      => '#333333',
			),
			'thim_footer_bg_color'                   => '#111',
			'thim_footer_color'                      => array(
				'title' => '#ffffff',
				'text'  => '#ffffff',
				'link'  => '#ffffff',
				'hover' => '#ffb606',
			),
			'thim_padding_content'                   => array(
				'pdtop'    => array(
					'desktop' => '60px',
					'tablet'  => '',
					'mobile'  => '40px',
				),
				'pdbottom' => array(
					'desktop' => '60px',
					'tablet'  => '',
					'mobile'  => '40px',
				),
			),
			'thim_border_radius'                     => array(
				'item'     => '4px',
				'item-big' => '10px',
				'button'   => '4px',
			),
			'thim_copyright_bg_color'                => '#111',
			'thim_copyright_text_color'              => '#999',
			'thim_copyright_border_color'            => '#222',

			'thim_bg_pattern'                        => THIM_URI . 'images/patterns/pattern1.png',
			'thim_bg_upload'                         => '',
			'thim_bg_repeat'                         => 'no-repeat',
			'thim_bg_position'                       => 'center',
			'thim_bg_attachment'                     => 'inherit',
			'thim_bg_size'                           => 'inherit',
			'thim_footer_background_img'             => '',
			'thim_footer_bg_repeat'                  => 'no-repeat',
			'thim_footer_bg_position'                => 'center',
			'thim_footer_bg_size'                    => 'inherit',
			'thim_footer_bg_attachment'              => 'inherit',
			'thim_body_bg_color'                     => '#fff',
			'nav_mobile_color'                       => array(
				'background' => '#ffffff',
				'text'       => '#333',
				'hover'      => '#ffb606',
			),
		);

		if ( get_theme_mod( 'thim_content_course_border', false ) === false ) {
			unset( $theme_options['thim_border_radius'] );
		}

		$image_keys = array( 'thim_bg_pattern', 'thim_footer_background_img', 'thim_bg_upload' );
		$rgb_keys   = array( 'thim_main_menu_text_color', 'thim_sticky_main_menu_text_color', 'thim_mobile_menu_text_color' );

		foreach ( $theme_options as $key => $val ) {
			$val_opt  = $this->thim_get_theme_option( $key, $val );
			$var_name = '--' . str_replace( '_', '-', $key );

			if ( is_array( $val_opt ) && isset( $val_opt['desktop'] ) ) {
				// Responsive single value: ['desktop' => '...', 'tablet' => '...', 'mobile' => '...']
				$desktop = isset( $val_opt['desktop'] ) ? $val_opt['desktop'] : ( ! is_array( $val ) ? $val : '' );
				$tablet  = isset( $val_opt['tablet'] ) ? $val_opt['tablet'] : '';
				$mobile  = isset( $val_opt['mobile'] ) ? $val_opt['mobile'] : '';

				if ( isset( $desktop ) && $desktop !== '' ) {
					$css .= $var_name . ':' . $desktop . ';';
				}
				if ( isset( $tablet ) && $tablet !== '' ) {
					$tablet_css .= $var_name . ':' . $tablet . ';';
				}
				if ( isset( $mobile ) && $mobile !== '' ) {
					$mobile_css .= $var_name . ':' . $mobile . ';';
				}
			} elseif ( is_array( $val_opt ) ) {
				foreach ( $val as $attr => $value ) {
					$val_ar   = isset( $val_opt[ $attr ] ) ? $val_opt[ $attr ] : $value;
					$var_attr = $var_name . '-' . $attr . ':';

					if ( is_array( $val_ar ) ) {

						$desktop = isset( $val_ar['desktop'] ) ? $val_ar['desktop'] : ( ! is_array( $value ) ? $value : '' );
						$tablet  = isset( $val_ar['tablet'] ) ? $val_ar['tablet'] : '';
						$mobile  = isset( $val_ar['mobile'] ) ? $val_ar['mobile'] : ''; 
						if ( isset( $desktop ) && $desktop !== '' ) {
							$css .= $var_attr . $desktop . ';';
						}
						if ( isset( $tablet ) && $tablet !== '' ) {
							$tablet_css .= $var_attr . $tablet . ';';
						}
						if ( isset( $mobile ) && $mobile !== '' ) {
							$mobile_css .= $var_attr . $mobile . ';';
						}
					} else {
						$css .= $var_attr . $val_ar . ';';
					}
				}
			} elseif ( $val_opt !== '' && $val_opt !== false ) {
				if ( in_array( $key, $image_keys, true ) ) {
					$val_opt = 'url("' . $val_opt . '")';
				}

				$css .= $var_name . ':' . $val_opt . ';';

				// Also output an RGB variant for color keys used in rgba() contexts
				if ( in_array( $key, $rgb_keys, true ) ) {
					if ( str_starts_with( $val_opt, '#' ) ) {
						list( $r, $g, $b ) = sscanf( $val_opt, '#%02x%02x%02x' );
					} else {
						$rgba              = explode( ',', $val_opt );
						$rgba_rr           = explode( '(', $rgba[0] );
						list( $r, $g, $b ) = array( $rgba_rr[1], $rgba[1], $rgba[2] );
					}
					$css .= $var_name . '-rgb:' . $r . ',' . $g . ',' . $b . ';';
				}
			}
		}

		$terms = get_transient( 'thim_eduma_course_tags_css' );
		if ( false === $terms ) {
			$terms = get_terms(
				'course_tag',
				array(
					'pad_counts'         => 1,
					'show_counts'        => 1,
					'hierarchical'       => 1,
					'hide_empty'         => 1,
					'show_uncategorized' => 1,
					'orderby'            => 'name',
					'menu_order'         => false,
				)
			);
			set_transient( 'thim_eduma_course_tags_css', $terms, DAY_IN_SECONDS );
		}

		$term_background = array();
		if ( ! is_wp_error( $terms ) && ! empty( $terms ) ) {
			foreach ( $terms as $term ) {
				$term_bg_color = get_term_meta( $term->term_id, 'learnpress_tag_bg_color', true );
				$term_color    = get_term_meta( $term->term_id, 'learnpress_tag_text_color', true );

				if ( ! empty( $term_bg_color ) ) {
					$term_background[] = '.term-' . esc_html( $term->slug ) . '{background:' . $this->thim_sanitize_css_color( $term_bg_color ) . '!important;}';
				}
				if ( ! empty( $term_color ) ) {
					$term_background[] = '.term-' . esc_html( $term->slug ) . '{color:' . $this->thim_sanitize_css_color( $term_color ) . '!important;}';
				}
			}
		}

		$css .= implode( ' ', $term_background );

		$patterns     = array( '/\s*(\w)\s*{\s*/', '/\s*(\S*:)(\s*)([^;]*)(\s|\n)*;(\n|\s)*/', '/\n/', '/\s*}\s*/' );
		$replacements = array( '$1{ ', '$1$3;', '', '} ' );

		$output = ':root{' . preg_replace( $patterns, $replacements, $css ) . '}';

		if ( $tablet_css ) {
			$output .= '@media (max-width:1024px){:root{' . preg_replace( $patterns, $replacements, $tablet_css ) . '}}';
		}

		if ( $mobile_css ) {
			$output .= '@media (max-width:768px){:root{' . preg_replace( $patterns, $replacements, $mobile_css ) . '}}';
		}

		return $output;
	}

	/**
	 * Clear the course tag CSS transient when term cache is cleaned.
	 *
	 * @param int[]  $ids      Term IDs.
	 * @param string $taxonomy Taxonomy slug.
	 */
	public function clear_cache_taxonomy( $ids, $taxonomy ) {
		if ( 'course_tag' === $taxonomy ) {
			delete_transient( 'thim_eduma_course_tags_css' );
		}
	}

	public function thim_custom_color_header_single_page( $css ) {
		$var_css = '';

		if ( is_page() || is_single() ) {
			$bg_main_menu_color         = $this->thim_sanitize_css_color( get_post_meta( get_the_ID(), 'thim_mtb_bg_main_menu_color', true ) );
			$main_menu_text_color       = $this->thim_sanitize_css_color( get_post_meta( get_the_ID(), 'thim_mtb_main_menu_text_color', true ) );
			$main_menu_text_hover_color = $this->thim_sanitize_css_color( get_post_meta( get_the_ID(), 'thim_mtb_main_menu_text_hover_color', true ) );

			if ( $bg_main_menu_color ) {
				$var_css .= '--thim-bg-main-menu-color:' . $bg_main_menu_color . ';';
				$var_css .= '--thim-main-menu-text-color:' . $main_menu_text_color . ';';
				$var_css .= '--thim-main-menu-text-hover-color:' . $main_menu_text_hover_color . ';';
				$css     .= '#masthead{' . $var_css . '}';
			}
		}

		if ( thim_lp_style_single_course() === 'layout_style_3' ) {
			$top_info_color    = $this->thim_sanitize_css_color( get_post_meta( get_the_ID(), 'thim_mtb_text_top_info_course', true ) );
			$bg_top_info_color = $this->thim_sanitize_css_color( get_post_meta( get_the_ID(), 'thim_mtb_bg_top_info_course', true ) );

			$var_css_course  = $bg_top_info_color ? '--top-info-course-background_color:' . $bg_top_info_color . ';' : '';
			$var_css_course .= $top_info_color ? '--top-info-course-text_color:' . $top_info_color . ';' : '';

			if ( $var_css_course ) {
				$css .= '.postid-' . get_the_ID() . '.single-lp_course .course-info-top{' . $var_css_course . '}';
			}
		}

		if ( get_post_type() === 'lp_collection' && is_single() && thim_eduma_header_position() === 'header_overlay' && get_theme_mod( 'thim_config_att_sticky', 'sticky_same' ) === 'sticky_custom' ) {
			$var_css_sticky  = '--thim-bg-main-menu-color:var(--thim-sticky-bg-main-menu-color);';
			$var_css_sticky .= '--thim-main-menu-text-color:var(--thim-sticky-main-menu-text-color);';
			$var_css_sticky .= '--thim-main-menu-text-hover-color:var(--thim-sticky-main-menu-text-hover-color);';
			$css            .= '#masthead{' . $var_css_sticky . '}';
		}

		return $css;
	}
}

new Variables_Options();
