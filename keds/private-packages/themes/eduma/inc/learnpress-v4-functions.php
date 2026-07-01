<?php
use LearnPress\Models\CourseModel;
use LearnPress\Wishlist\TemplateHooks\CourseWishlistTemplate;
add_filter( 'learn-press/override-templates', '__return_true' );

if ( thim_is_new_learnpress( '4.1.6' ) ) {
	add_filter( 'lp/template-course/course_curriculum/skeleton', '__return_true' );
}

// Remove action or filter in LearnPress
if ( ! function_exists( 'thim_remove_learnpress_hooks' ) ) {
	function thim_remove_learnpress_hooks() {
		// remove sidebar default of LearnPress
		remove_action( 'widgets_init', 'learn_press_register_sidebars' );

		LearnPress::instance()->template( 'course' )->remove( 'learn-press/single-button-toggle-sidebar', array( '<input type="checkbox" id="sidebar-toggle" />', 'single-button-toggle-sidebar' ), 5 );

		remove_action( 'learn-press/single-button-toggle-sidebar', 'single-button-toggle-sidebar', 5 );

		add_action( 'thim_single_course_payment', LearnPress::instance()->template( 'course' )->func( 'course_pricing' ), 5 );
		add_action( 'thim_single_course_payment', LearnPress::instance()->template( 'course' )->func( 'course_buttons' ), 15 );

		// Sidebar content
			/**
			 * @see LP_Template_Course::course_featured_review()
			 */
		add_action( 'learn-press/course-sidebar-after', LearnPress::instance()->template( 'course' )->func( 'course_featured_review' ), 10 );

		add_action( 'thim_single_course_meta', LearnPress::instance()->template( 'course' )->callback( 'single-course/instructor' ), 5 );
		add_action( 'thim_single_course_meta', LearnPress::instance()->template( 'course' )->callback( 'single-course/meta/category' ), 15 );

		add_action( 'thim_single_course_meta', LearnPress::instance()->template( 'course' )->func( 'user_progress' ), 30 );

		// add forum link layout default
		/**
		 * @see thim_course_forum_link();
		 */
		add_action( 'thim_single_course_featured_review', 'thim_course_forum_link', 5 );
		// add forum link layout 1, 2
		add_action( 'thim_sidebar_menu_info_course', 'thim_course_forum_link', 10 );

		add_action( 'learnpress/template/pages/profile/before-content', 'thim_wapper_page_title', 5 );
		add_action( 'learnpress/template/pages/profile/before-content', 'thim_wrapper_loop_start', 10 );
		add_action( 'learnpress/template/pages/profile/after-content', 'thim_wrapper_loop_end', 10 );

		add_action( 'learnpress/template/pages/checkout/before-content', 'thim_wapper_page_title', 5 );
		add_action( 'learnpress/template/pages/checkout/before-content', 'thim_wrapper_loop_start', 10 );
		add_action( 'learnpress/template/pages/checkout/after-content', 'thim_wrapper_loop_end', 10 );

		add_action( 'thim_single_course_before_meta', 'thim_course_thumbnail_item', 5 );

		add_action( 'theme_course_extra_boxes', LearnPress::instance()->template( 'course' )->func( 'course_extra_boxes' ), 5 );

		add_action(
			'init',
			function () {
				if ( class_exists( 'LP_Addon_Course_Review' ) ) {
					add_action( 'thim_single_course_meta', 'learn_press_course_meta_primary_review', 25 );
				}

				if ( class_exists( 'LP_Addon_Wishlist' ) && is_user_logged_in() && thim_is_version_addons_wishlist( '3' ) ) {
					$instance_addon = LP_Addon_Wishlist::instance();
					remove_action( 'learn-press/after-course-buttons', array( $instance_addon, 'wishlist_button' ), 100 );
					// Check wishlist new version 4.0.9
					if ( thim_is_version_addons_wishlist( '4.0.9' ) ) {
						include_once THIM_DIR . 'learnpress-v4/addons/wishlist/class-wishlist-hook.php';
						LP_Wishlist_Hook::instance();
					} else {
						add_action( 'thim_after_course_info', array( $instance_addon, 'wishlist_button' ), 10 );
						add_action( 'thim_inner_thumbnail_course', array( $instance_addon, 'wishlist_button' ), 10 );
					}
				}

				// remove html in begin loop and end loop
				if ( class_exists( 'LP_Addon_bbPress' ) ) {
					$instance_addon = LP_Addon_bbPress::instance();
					remove_action( 'learn-press/single-course-summary', array( $instance_addon, 'forum_link' ), 0 );
				}
				if ( class_exists( 'LP_Addon_Woo_Payment' ) ) {
					$instance_addon = LP_Addon_Woo_Payment::instance();
					remove_action(
						'learn-press/before-course-buttons',
						array(
							$instance_addon,
							'purchase_course_notice',
						)
					);
					remove_action( 'learn-press/after-course-buttons', array( $instance_addon, 'after_course_buttons' ) );

					/**
					 * @see LP_Woo_Assign_Course_To_Product::instance() notice_purchase_course_via_product();
					 */
					/*
					if ( class_exists( 'LP_Woo_Assign_Course_To_Product' ) ) {
						add_action( 'thim_single_course_featured_review', array(  LP_Woo_Assign_Course_To_Product::instance(), 'notice_purchase_course_via_product' ), 10 );
						add_action( 'thim_sidebar_menu_info_course', array(  LP_Woo_Assign_Course_To_Product::instance(), 'notice_purchase_course_via_product' ), 10 );
					}*/
				}

				if ( class_exists( 'LP_WC_Hooks' ) && thim_is_version_addons_woo_payment( '4.0.3' ) ) {
					$lp_woo_hoocks    = LP_WC_Hooks::instance();
					$buy_with_product = get_option( 'learn_press_woo-payment_buy_course_via_product' );
					if ( $buy_with_product == 'yes' ) {
						add_action( 'thim-lp-course-button-read-more', 'thim_button_read_more_course' );
					} else {
						add_action( 'thim-lp-course-button-read-more', array( $lp_woo_hoocks, 'btn_add_to_cart' ) );
						// add button remove for course free
						add_action( 'learnpress/woo-payment/course-free/btn_add_to_cart_before', 'thim_button_read_more_course' );
					}
				} else {
					add_action( 'thim-lp-course-button-read-more', 'thim_button_read_more_course' );
				}

				// Remove Results H5P
				if ( class_exists( 'LP_H5P_Template_Hook' ) ) {
					$instance_addon = LP_H5P_Template_Hook::instance();
					remove_action(
						'learn-press/user-item-progress',
						array(
							$instance_addon,
							'user_item_progress',
						),
						10,
						3
					);
				}

				if ( class_exists( 'LP_Addon_Coming_Soon_Courses' ) ) {
					$instance_addon = LP_Addon_Coming_Soon_Courses::instance();
					remove_action( 'learn-press/course-content-summary', array( $instance_addon, 'coming_soon_countdown' ), 10 );
					add_action( 'learn-press/single-course-summary', array( $instance_addon, 'coming_soon_countdown' ), 5 );
					add_action( 'thim_single_course_before_meta', array( $instance_addon, 'coming_soon_countdown' ), 5 );
					add_action( 'thim_lp_before_single_course_summary', array( $instance_addon, 'coming_soon_message' ), 15 );

				}
			},
			99
		);

		remove_action( 'learn-press/after-checkout-form', LearnPress::instance()->template( 'checkout' )->func( 'account_logged_in' ), 20 );
		remove_action( 'learn-press/after-checkout-form', LearnPress::instance()->template( 'checkout' )->func( 'order_comment' ), 60 );
		add_action( 'learn-press/before-checkout-form', LearnPress::instance()->template( 'checkout' )->func( 'account_logged_in' ), 9 );
		add_action( 'learn-press/before-checkout-form', LearnPress::instance()->template( 'checkout' )->func( 'order_comment' ), 11 );

		add_filter(
			'learn_press_course_loop_begin',
			function () {
				return '';
			}
		);
		add_filter(
			'learn_press_course_loop_end',
			function () {
				return '';
			}
		);

		remove_action( 'learn-press/profile/dashboard-summary', LearnPress::instance()->template( 'profile' )->func( 'dashboard_featured_courses' ), 20 );

		/**
		 * @see LP_Template_Course::popup_footer_nav()
		 */
		remove_action( 'learn-press/user-item-progress', 'lp_assignments_add_item_user_progress', 10, 3 );
	}
}

add_action( 'after_setup_theme', 'thim_remove_learnpress_hooks', 15 );

add_action(
	'template_redirect',
	function () {
		if ( class_exists( 'LP_Addon_Coming_Soon_Courses' ) ) {
			$instance_addon = LP_Addon_Coming_Soon_Courses::instance();
			if ( is_post_type_archive( 'lp_course' ) ) {
				remove_action( 'learn_press_course_price_html', array( $instance_addon, 'set_course_price_html_empty' ) );
			}
		}
	},
	100
);

// add div for thumb image when us coming soon
function thim_class_before_thumb_image() {
	$course = learn_press_get_course();
	if ( ! $course ) {
		echo '<div>';
	}
	$no_thumbnail = ' no-thumbnail';
	if ( has_post_thumbnail() ) {
		$no_thumbnail = '';
	}
	if ( class_exists( 'LP_Addon_Coming_Soon_Courses' ) ) {
		$instance_addon = LP_Addon_Coming_Soon_Courses::instance();
		if ( $instance_addon->is_coming_soon( $course->get_id() ) ) {
			echo '<div class="thim-top-course' . $no_thumbnail . '">';
		} else {
			echo '<div>';
		}
	} else {
		echo '<div>';
	}
}

function thim_class_after_thumb_image() {
	echo '</div>';
}

add_action( 'learn-press/single-course-summary', 'thim_class_before_thumb_image', 1 );
add_action( 'learn-press/single-course-summary', 'learn_press_course_thumbnail', 2 );
add_action( 'learn-press/single-course-summary', 'thim_class_after_thumb_image', 6 );
// comming soon for layout new 1
add_action( 'thim_single_course_before_meta', 'thim_class_before_thumb_image', 1 );
add_action( 'thim_single_course_before_meta', 'thim_class_after_thumb_image', 6 );

remove_all_actions( 'learn-press/course-content-summary', 10 );
remove_all_actions( 'learn-press/course-content-summary', 15 );
remove_all_actions( 'learn-press/course-content-summary', 85 );
remove_all_actions( 'learn-press/before-main-content' );

add_filter( 'lp_item_course_class', 'thim_item_course_class_custom' );
function thim_item_course_class_custom( $class ) {
	$class[] = 'thim-course-grid';

	return $class;
}

/**
 * @see LP_Template_Course::popup_header()
 * @see LP_Template_Course::popup_sidebar()
 * @see LP_Template_Course::popup_content()
 * @see LP_Template_Course::popup_footer()
 */


add_action( 'learn-press/before-main-content', 'lp_archive_courses_open', - 100 );
if ( ! function_exists( 'lp_archive_courses_open' ) ) {
	function lp_archive_courses_open() {
		$courses_page_id  = learn_press_get_page_id( 'courses' );
		$courses_page_url = $courses_page_id ? get_page_link( $courses_page_id ) : LP_Helper::getUrlCurrent();
		if ( thim_check_is_course_taxonomy() || thim_check_is_course() ) {
			?>
			<div id="lp-archive-courses" class="lp-archive-courses" data-all-courses-url="<?php echo esc_url( $courses_page_url ); ?>">
			<?php
		} elseif ( is_singular( LP_COURSE_CPT ) ) {
			?>
			<div id="lp-single-course" class="lp-single-course learn-press-4">
			<?php
		}
	}
}
// add lesson custom fields
function eduma_add_lesson_fields( $fields ) {
	$add_fields = array(
		'_lp_lesson_video_intro' => new LP_Meta_Box_Textarea_Field(
			esc_html__( 'Media', 'eduma' ),
			esc_html__( 'Add an embed link like video, PDF, slider...', 'eduma' ),
			''
		),
	);
	return array_merge( $fields, $add_fields );
}
add_filter( 'lp/metabox/lesson/lists', 'eduma_add_lesson_fields', 10, 1 );

// add cusom field for course
add_filter( 'lp/course/meta-box/fields/general', 'eduma_add_filter_custom_field_course', 10, 2 );
function eduma_add_filter_custom_field_course( $fields ) {
	$add_fields = array(
		'thim_course_duration'    => new LP_Meta_Box_Text_Field(
			esc_html__( 'Duration Info', 'eduma' ),
			esc_html__( 'Overwrite display Duration in singe course', 'eduma' ),
			''
		),
		'thim_course_language'    => new LP_Meta_Box_Text_Field(
			esc_html__( 'Languages', 'eduma' ),
			esc_html__( 'Language\'s used for studying', 'eduma' ),
			esc_html__( 'English', 'eduma' )
		),
		'thim_course_media_intro' => new LP_Meta_Box_Textarea_Field(
			esc_html__( 'Media Intro', 'eduma' ),
			esc_html__( 'Enter media intro', 'eduma' ),
			''
		),
	);
	return array_merge( $fields, $add_fields );
}

add_action(
	'learnpress_save_lp_course_metabox',
	function ( $post_id ) {
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}
		$video = ! empty( $_POST['thim_course_media_intro'] ) ? wp_kses_post( wp_unslash( $_POST['thim_course_media_intro'] ) ) : '';
		update_post_meta( $post_id, 'thim_course_media_intro', $video );
	}
);

add_action(
	'learnpress_save_lp_lesson_metabox',
	function ( $post_id ) {
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}
		$video = ! empty( $_POST['_lp_lesson_video_intro'] ) ? wp_kses_post( wp_unslash( $_POST['_lp_lesson_video_intro'] ) ) : '';

		update_post_meta( $post_id, '_lp_lesson_video_intro', $video );
	}
);
// custom add metabox video lesson by fe editor
function frontend_editor_add_video_lesson( $data = array() ) {

	$data['post_type_fields'][ LP_LESSON_CPT ][] =
		array(
			'id'   => '_lp_lesson_video_intro',
			'name' => esc_html__( 'Media', 'eduma' ),
			'type' => 'textarea',
			'std'  => '',
			'desc' => esc_html__( 'Add an embed link like video, PDF, slider...', 'eduma' ),
		);
	return $data;
}

add_filter( 'e-course-data-store', 'frontend_editor_add_video_lesson', 20, 1 );

function get_value_video_lesson_by_frontend_editor( $item_setting = array(), $item_type = '', $item_id = '' ) {

	$item_setting['_lp_lesson_video_intro'] = get_post_meta( $item_id, '_lp_lesson_video_intro', true );

	return $item_setting;
}
add_filter( 'frontend-editor/item-settings', 'get_value_video_lesson_by_frontend_editor', 20, 3 );

// end custom add metabox video lesson by fe editor
/**
 * @param Remaining time
 */
function thim_get_remaining_time() {
	$user   = learn_press_get_current_user();
	$course = learn_press_get_course();
	if ( ! $course ) {
		return false;
	}

	if ( ! $user ) {
		return false;
	}

	if ( ! $user->has_enrolled_course( $course->get_id() ) ) {
		return false;
	}

	if ( $user->has_finished_course( $course->get_id() ) ) {
		return false;
	}

	$remaining_time = thim_timestamp_remaining_duration( $course );

	if ( false === $remaining_time ) {
		return false;
	}

	$time  = '';
	$time .= '<div class="course-remaining-time message message-warning">';
	$time .= '<p>';
	$time .= sprintf( __( 'You have %s remaining for the course', 'eduma' ), $remaining_time );
	$time .= '</p>';
	$time .= '</div>';
	echo $time;
}

/**
 * custom remaning by UTC
 *
 * @param Remaining time
 */
function thim_timestamp_remaining_duration( LP_Course $course ) {

	$timestamp_remaining = - 1;
	$user                = learn_press_get_user( get_current_user_id() );

	if ( 0 === absint( $course->get_data( 'duration' ) ) ) {
		return $timestamp_remaining;
	}

	if ( $user instanceof LP_User_Guest ) {
		return $timestamp_remaining;
	}

	$course_item_data = $user->get_course_data( $course->get_id() );

	if ( ! $course_item_data ) {
		return $timestamp_remaining;
	}

	$course_start_time   = $course_item_data->get_start_time()->get_raw_date();
	$duration            = $course->get_data( 'duration' );
	$timestamp_expire    = strtotime( $course_start_time . ' +' . $duration );
	$timestamp_current   = strtotime( current_time( 'mysql' ) );
	$timestamp_remaining = $timestamp_expire - $timestamp_current;

	if ( $timestamp_remaining < 0 ) {
		$timestamp_remaining = 0;
	}

	$diff = learn_press_seconds_to_weeks( $timestamp_remaining );

	return $diff;
}

add_action( 'learn-press/before-single-course-curriculum', 'thim_get_remaining_time', 5 );

add_action( 'learn-press/course-content-summary', 'thim_landing_tabs', 22 );

// Before Curiculumn on item single course
add_action( 'learn-press/before-single-course-curriculum', 'thim_before_curiculumn_item_func', 6 );

// add class fix style use don't description in page profile
add_filter( 'learn-press/profile/class', 'thim_class_has_description_user' );
function thim_class_has_description_user( $classes ) {
	$profile = LP_Profile::instance();
	$user    = $profile->get_user();
	if ( ! isset( $user ) ) {
		return;
	}
	$bio = $user->get_description();
	if ( ! $bio ) {
		$classes[] = 'no-bio-user';
	}

	return $classes;
}


if ( ! function_exists( 'thim_courses_loop_item_thumbnail' ) ) {
	function thim_courses_loop_item_thumbnail( $course = null ) {
		$course                      = learn_press_get_course();
		$course_thumbnail_dimensions = learn_press_get_course_thumbnail_dimensions();
		$with_thumbnail              = $course_thumbnail_dimensions['width'];
		$height_thumbnail            = $course_thumbnail_dimensions['height'];

		if ( $course ) {
			echo '<div class="course-thumbnail">';
			echo '<a class="thumb" href="' . esc_url( get_the_permalink( $course->get_id() ) ) . '" >';
				echo thim_get_feature_image( get_post_thumbnail_id( $course->get_id() ), 'full', $with_thumbnail, $height_thumbnail, $course->get_title() );
			echo '</a>';

			do_action( 'thim_inner_thumbnail_course' );

			// only button read more
			do_action( 'thim-lp-course-button-read-more' );
			echo '</div>';
		}
	}
}
add_action( 'thim_courses_loop_item_thumb', 'thim_courses_loop_item_thumbnail' );

if ( ! function_exists( 'thim_lp_social_user' ) ) {
	function thim_lp_social_user( $user_id = '' ) {
		global $post;
		if ( ! $user_id ) {
			$user = learn_press_get_user( $post->post_author );
			if ( $user ) {
				$socials = $user->get_profile_social( $user->get_id() );
			}
		} else {
			$user_instructor = learn_press_get_user( $user_id );
			if ( $user_instructor ) {
				$socials = $user_instructor->get_profile_social( $user_id );
			}
		}
		if ( isset( $socials ) && $socials ) {
			?>
			<ul class="thim-author-social">
				<?php foreach ( $socials as $value ) : ?>
					<li><?php echo $value; ?></li>
				<?php endforeach; ?>
			</ul>
			<?php
		}
	}
}

add_action( 'thim_course_info_right', 'thim_course_info', 5 );

// add action related courses
add_action( 'thim_lp_after_single_course_summary', 'thim_related_courses' );

// change icon tab profile
/**
 * Change tabs profile
 */
if ( ! function_exists( 'thim_change_icon_tabs_course_profile' ) ) {
	function thim_change_icon_tabs_course_profile( $defaults ) {
		if ( isset( $defaults['courses'] ) ) {
			$defaults['courses']['icon'] = '<i class="edu-book-open"></i>';
		}
		if ( isset( $defaults['quizzes'] ) ) {
			$defaults['quizzes']['icon'] = '<i class="edu-question-circle"></i>';
		}
		if ( isset( $defaults['orders'] ) ) {
			$defaults['orders']['icon'] = '<i class="edu-shopping-bag"></i>';
		}
		if ( isset( $defaults['settings'] ) ) {
			$defaults['settings']['icon']                                  = '<i class="edu-cog"></i>';
			$defaults['settings']['sections']['avatar']['icon']            = '<i class="edu-user"></i>';
			$defaults['settings']['sections']['basic-information']['icon'] = '<i class="edu-home"></i>';
			$defaults['settings']['sections']['change-password']['icon']   = '<i class="edu-key"></i>';
		}
		if ( isset( $defaults['logout'] ) ) {
			$defaults['logout']['icon'] = '<i class="edu-sign-out"></i>';
		}
		if ( isset( $defaults['instructor'] ) ) {
			$defaults['instructor']['icon'] = '<i class="edu-author"></i>';
		}
		if ( isset( $defaults['wishlist'] ) ) {
			$defaults['wishlist']['icon'] = '<i class="edu-heart-o"></i>';
		}
		if ( isset( $defaults['certificates'] ) ) {
			$defaults['certificates']['icon'] = '<i class="edu-star"></i>';
		}
		if ( isset( $defaults['assignments'] ) ) {
			$defaults['assignments']['icon'] = '<i class="edu-assessments"></i>';
		}
		if ( isset( $defaults['withdrawals'] ) ) {
			$defaults['withdrawals']['icon'] = '<i class="edu-alternate-wavy-money-bill"></i>';
		}
		if ( isset( $defaults['gradebook'] ) ) {
			$defaults['gradebook']['icon'] = '<i class="edu-book"></i>';
		}
		// $defaults['settings']['icon'] = 14;

		return $defaults;
	}
}
// 26.09.2024 fix user icon default of learnpress
if ( ! thim_is_new_learnpress( '4.2.7.2' ) ) {
	add_filter( 'learn-press/profile-tabs', 'thim_change_icon_tabs_course_profile', 100 );
}
add_filter( 'template_include', 'thim_single_course_template_include', 99 );

function thim_single_course_template_include( $template ) {
	if ( thim_lp_style_single_course() == 'new-1' ) {
		add_action( 'thim_sidebar_menu_info_course', 'thim_menu_sidebar_course' );
		remove_action( 'learn-press/single-course-summary', 'learn_press_course_thumbnail', 2 );
	} elseif ( thim_lp_style_single_course() == 'layout_style_3' ) {
		remove_action( 'thim_course_info_right', 'thim_course_info', 5 );
		add_action( 'thim_sidebar_menu_info_course', 'thim_course_info', 5 );
		add_action( 'thim_wrapper_loop_start', 'thim_single_title_desc_layout_3', 6 );
	}

	return $template;
}

add_action( 'thim_sidebar_tab_course', 'thim_menu_sidebar_course', 5 );
if ( ! function_exists( 'thim_menu_sidebar_course' ) ) {
	function thim_menu_sidebar_course() {
		?>
		<div class="menu_course">
		<?php $tabs = learn_press_get_course_tabs(); ?>
			<ul>
				<?php foreach ( $tabs as $key => $tab ) { ?>
					<li role="presentation">
						<a href="#<?php echo esc_attr( $tab['id'] ); ?>" data-toggle="tab" class="<?php echo esc_attr( $key ); ?>">
						<?php
						if ( ! isset( $tab['custom'] ) ) {
							if ( $tab['icon'] ) {
								echo '<i class="edu ' . $tab['icon'] . '"></i>';
							}
							?>
								<span><?php echo $tab['title']; ?></span>
							<?php
						} else {
							do_action( 'learn-press/course-tab-nav', $tab, $key );
						}
						?>
						</a>
						</li>
					<?php } ?>
				</ul>
		</div>
		<?php
	}
}

if ( ! function_exists( 'thim_show_meta_course_coming_soon' ) ) {
	function thim_show_meta_course_coming_soon() {
		$thim_course_payment = true;
		if ( class_exists( 'LP_Addon_Coming_Soon_Courses' ) ) {
			$instance_addon = LP_Addon_Coming_Soon_Courses::instance();
			if ( $instance_addon->is_coming_soon( get_the_ID() ) && 'no' == get_post_meta( get_the_ID(), '_lp_coming_soon_metadata', true ) ) {
				$thim_course_payment = false;
			}
		}
		return $thim_course_payment;
	}
}

if ( ! function_exists( 'thim_single_title_desc_layout_3' ) ) {
	function thim_single_title_desc_layout_3() {
		if ( is_singular( 'lp_course' ) ) {
			?>
			<div class="course-info-top">
				<div class="container">
					<div class="row">
						<div class="course-info-left col-sm-8 learn-press">
							<?php the_title( '<h1 class="entry-title" itemprop="name">', '</h1>' ); ?>
							<?php
							the_excerpt();
							if ( thim_show_meta_course_coming_soon() ) {
								?>
								<div class="course-meta course-meta-single">
									<?php do_action( 'thim_single_course_meta' ); ?>
								</div>
								<?php
							}
							?>
						</div>
					</div>
				</div>
			</div>
			<?php
		}
	}
}

// add image size for elementor
$course_thumbnail_dimensions = learn_press_get_course_thumbnail_dimensions();

if ( $course_thumbnail_dimensions ) {
	$with_thumbnail   = $course_thumbnail_dimensions['width'];
	$height_thumbnail = $course_thumbnail_dimensions['height'];
	add_image_size( 'course_thumbnail', $with_thumbnail, $height_thumbnail, true );
}

// filter for package
add_filter( 'lp/upsell/archive-package/wrapper', 'lp_upsell_archive_package_wrapper_page_title' );
add_filter( 'lp/upsell/single-package/wrapper', 'lp_upsell_single_package_wrapper_page_title' );
function lp_upsell_archive_package_wrapper_page_title() {
	ob_start();
	thim_wapper_page_title();
	$page_title = ob_get_clean();

	return array(
		'<section class="content-area">' => '</section>',
		$page_title ? $page_title : '' => '',
		'<div class="learnpress-packages__wrapper site-content container">' => '</div>',
	);
}
function lp_upsell_single_package_wrapper_page_title() {
	ob_start();
	thim_wapper_page_title();
	$page_title = ob_get_clean();

	return array(
		'<section class="content-area">' => '</section>',
		$page_title ? $page_title : '' => '',
		'<div class="learnpress-packages__wrapper site-content container">' => '</div>',
		'<div class="single-package-wrapper">' => '</div>',
	);
}

add_filter(
	'lp/upsell/archive-package/sections',
	function ( $section ) {
		unset( $section['header'] );
		return $section;
	}
);
// add popup form register for package
add_filter(
	'learn-press/upsell/single-package/html-add-to-cart',
	function ( $section ) {
		$classes_purchase   = ( LearnPress::instance()->checkout()->is_enable_guest_checkout() ) ? ' guest_checkout' : '';
		$section['wrapper'] = '<form class="learnpress-single-package__add-cart' . $classes_purchase . '" method="post">';
		return $section;
	},
	10
);

// Support meta key language for thim-elementor-kit
add_filter(
	'thim-ekit-course-key-language',
	function () {
		return 'thim_course_language';
	}
);

remove_action( 'learn-press/user-profile-account', LearnPress::instance()->template( 'profile' )->func( 'socials' ), 10 );
add_filter(
	'learn-press/profile/header/sections',
	function () {
		return array(
			'profile/header/user-name.php',
			'profile/socials.php',
			'profile/header/user-bio.php',
		);
	}
);
// Custom layout course offline
add_action(
	'learn-press/single-course/offline/layout',
	function () {
		echo '<div class="content-area">';
	},
	1
);
add_action( 'learn-press/single-course/offline/layout', 'thim_wapper_page_title', 1 );
add_action(
	'learn-press/single-course/offline/layout',
	function () {
		echo '</div>';
	},
	99
);
add_filter( 'learn-press/single-course/offline/section-left', 'thim_course_offline_section_left', 10, 3 );

function thim_course_offline_section_left( $section, $course, $user ) {
	unset( $section['breadcrumb'] );
	return $section;
}
