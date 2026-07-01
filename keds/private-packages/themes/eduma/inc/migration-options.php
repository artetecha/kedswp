<?php
/**
 * Migrate blog meta tags: individual switch fields → unified sortable field.
 * Migrate page title padding: flat 4-key array → 2-key responsive array.
 * Each runs once, marks completion in wp_options, then removes old keys from theme_mods.
 */
if ( ! function_exists( 'thim_migrate_settings' ) ) {
	function thim_migrate_settings() {
		// --- Blog meta tags ---
		if ( ! get_option( 'thim_migrated_options' ) ) {
			$old_meta_keys = array( 'thim_show_author', 'thim_show_date', 'thim_show_category', 'thim_show_comment', 'thim_show_tag' );
			$theme_mods    = get_theme_mods();

			$has_old = false;
			foreach ( $old_meta_keys as $key ) {
				if ( isset( $theme_mods[ $key ] ) ) {
					$has_old = true;
					break;
				}
			}

			if ( $has_old ) {
				$defaults = array(
					'thim_show_author'   => true,
					'thim_show_date'     => true,
					'thim_show_category' => false,
					'thim_show_comment'  => true,
					'thim_show_tag'      => false,
				);
				$key_map  = array(
					'author'   => 'thim_show_author',
					'date'     => 'thim_show_date',
					'category' => 'thim_show_category',
					'comment'  => 'thim_show_comment',
					'tag'      => 'thim_show_tag',
				);

				$meta_tags = array();
				foreach ( $key_map as $new_key => $old_key ) {
					if ( get_theme_mod( $old_key, $defaults[ $old_key ] ) ) {
						$meta_tags[] = $new_key;
					}
				}

				set_theme_mod( 'thim_blog_meta_tags', $meta_tags );

				foreach ( $old_meta_keys as $old_key ) {
					remove_theme_mod( $old_key );
				}
			}

			$old_padding = get_theme_mod( 'thim_padding_content' );

			if ( is_array( $old_padding ) && isset( $old_padding['pdtop-desktop'] ) ) {
				$new_padding = array(
					'pdtop'    => array(
						'desktop' => isset( $old_padding['pdtop-desktop'] ) ? $old_padding['pdtop-desktop'] : '60px',
						'tablet'  => '',
						'mobile'  => isset( $old_padding['pdtop-mobile'] ) ? $old_padding['pdtop-mobile'] : '40px',
					),
					'pdbottom' => array(
						'desktop' => isset( $old_padding['pdbottom-desktop'] ) ? $old_padding['pdbottom-desktop'] : '60px',
						'tablet'  => '',
						'mobile'  => isset( $old_padding['pdbottom-mobile'] ) ? $old_padding['pdbottom-mobile'] : '40px',
					),
				);

				set_theme_mod( 'thim_padding_content', $new_padding );
			}

			$old_padding = get_theme_mod( 'thim_top_heading_padding' );

			if ( is_array( $old_padding ) && isset( $old_padding['top'] ) && ! is_array( $old_padding['top'] ) ) {
				$new_padding = array(
					'top'    => array(
						'desktop' => isset( $old_padding['top'] ) ? $old_padding['top'] : '90px',
						'tablet'  => '',
						'mobile'  => isset( $old_padding['top-mobile'] ) ? $old_padding['top-mobile'] : '50px',
					),
					'bottom' => array(
						'desktop' => isset( $old_padding['bottom'] ) ? $old_padding['bottom'] : '90px',
						'tablet'  => '',
						'mobile'  => isset( $old_padding['bottom-mobile'] ) ? $old_padding['bottom-mobile'] : '50px',
					),
				);

				set_theme_mod( 'thim_top_heading_padding', $new_padding );
			}
			$heading_style = get_theme_mod( 'thim_top_heading' );

			if ( $heading_style == 'style_3' ) {
				$body_class  = get_theme_mod( 'thim_body_custom_class', '' );
				$pdtop_value = ( false !== strpos( $body_class, 'demo-preschool' ) ) ? '30px' : '0px';
				$fix_padding = array(
					'pdtop' => array(
						'desktop' => $pdtop_value,
					),
				);

				set_theme_mod( 'thim_padding_content', $fix_padding );
			}

			// Migrate heading typography: tablet = desktop of next heading level (h1→h2, h2→h3, ...)
			$heading_defaults = array(
				'thim_font_h1' => array(
					'font-size'   => '36px',
					'line-height' => '1.6em',
				),
				'thim_font_h2' => array(
					'font-size'   => '28px',
					'line-height' => '1.6em',
				),
				'thim_font_h3' => array(
					'font-size'   => '24px',
					'line-height' => '1.6em',
				),
				'thim_font_h4' => array(
					'font-size'   => '18px',
					'line-height' => '1.6em',
				),
				'thim_font_h5' => array(
					'font-size'   => '16px',
					'line-height' => '1.6em',
				),
				'thim_font_h6' => array(
					'font-size'   => '16px',
					'line-height' => '1.4em',
				),
			);
			$heading_chain    = array(
				'thim_font_h1' => 'thim_font_h2',
				'thim_font_h2' => 'thim_font_h3',
				'thim_font_h3' => 'thim_font_h4',
				'thim_font_h4' => 'thim_font_h5',
				'thim_font_h5' => 'thim_font_h6',
			);

			foreach ( $heading_chain as $target_key => $source_key ) {
				$target_mod = get_theme_mod( $target_key );
				$source_mod = get_theme_mod( $source_key );

				if ( ! is_array( $target_mod ) ) {
					$target_mod = $heading_defaults[ $target_key ];
				}
				if ( ! is_array( $source_mod ) ) {
					$source_mod = $heading_defaults[ $source_key ];
				}

				foreach ( array( 'font-size', 'line-height' ) as $prop ) {
					$source_val     = isset( $source_mod[ $prop ] ) ? $source_mod[ $prop ] : '';
					$source_desktop = is_array( $source_val )
						? ( isset( $source_val['desktop'] ) ? $source_val['desktop'] : '' )
						: $source_val;

					if ( empty( $source_desktop ) ) {
						continue;
					}

					if ( isset( $target_mod[ $prop ] ) && is_array( $target_mod[ $prop ] ) ) {
						$target_mod[ $prop ]['tablet'] = $source_desktop;
					} else {
						$current_desktop     = isset( $target_mod[ $prop ] ) ? $target_mod[ $prop ] : '';
						$target_mod[ $prop ] = array(
							'desktop' => $current_desktop,
							'tablet'  => $source_desktop,
							'mobile'  => '',
						);
					}
				}

				set_theme_mod( $target_key, $target_mod );
			}

			if ( 'default' === get_theme_mod( 'thim_archive_cate_template' ) || empty( $theme_mods['thim_archive_cate_template'] ) ) {
				set_theme_mod( 'thim_archive_cate_template', 'list' );
				set_theme_mod( 'thim_feature_image_pos', 'above' );
			}  

			update_option( 'thim_migrated_options', true );
		}
	}
}
add_action( 'after_setup_theme', 'thim_migrate_settings' );
