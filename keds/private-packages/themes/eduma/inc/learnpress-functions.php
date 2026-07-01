<?php

use LearnPress\CourseReview\TemplateHooks\CourseRatingTemplate;

if ( ! function_exists( 'thim_learnpress_page_title' ) ) {
	function thim_learnpress_page_title( $echo = true ) {
	}
}

/**
 * Display co instructors
 *
 * @param $course_id
 */
if ( ! function_exists( 'thim_co_instructors' ) ) {
	function thim_co_instructors( $course_id, $author_id ) {
		if ( ! $course_id ) {
			return;
		}
		if ( class_exists( 'LP_Co_Instructor_Preload' ) && thim_is_version_addons_instructor( '3' ) ) {
			$instructors = get_post_meta( $course_id, '_lp_co_teacher' );
			//$instructors = array_diff( $instructors, array( $author_id ) );
			if ( ! empty( $instructors ) && is_array( $instructors ) ) {
				foreach ( $instructors as $instructor ) {
					//Check if instructor not exist
					$user = get_userdata( $instructor );
					if ( $user === false ) {
						break;
					}
					$lp_info = get_the_author_meta( 'lp_info', $instructor );

					$co_instructor = learn_press_get_user( $instructor );

					if ( $co_instructor && thim_is_new_learnpress( '4.2.3' ) ) {
						$link = $co_instructor->get_url_instructor();
					} else {
						$link = learn_press_user_profile_link( $instructor );
					}
					?>
					<div class="thim-about-author thim-co-instructor" itemprop="contributor" itemscope
						itemtype="https://schema.org/Person">
						<div class="author-wrapper lp-course-author">
							<div class="author-avatar">
								<a href="<?php echo esc_url( $link ); ?>"><?php echo get_avatar( $instructor, 110 ); ?></a>
							</div>
							<div class="author-bio">
								<div class="author-top">
									<a itemprop="url" class="name instructor-display-name"
										href="<?php echo esc_url( $link ); ?>">
										<span
											itemprop="name"><?php echo esc_html( get_the_author_meta( 'display_name', $instructor ) ); ?></span>
									</a>
									<?php if ( isset( $lp_info['major'] ) && $lp_info['major'] ) : ?>
										<p class="job"><?php echo esc_html( $lp_info['major'] ); ?></p>
									<?php endif; ?>
								</div>
								<?php
								if ( function_exists( 'thim_lp_social_user' ) ) {
									thim_lp_social_user( $instructor );
								}
								?>
								<div class="author-description" itemprop="description">
									<?php echo wp_kses_post( get_the_author_meta( 'description', $instructor ) ); ?>
								</div>
							</div>
						</div>
					</div>
					<?php
				}
			}
		}
	}
}

/**
 * Display ratings count
 */

if ( ! function_exists( 'thim_course_ratings_count' ) ) {
	function thim_course_ratings_count( $course_id = null ) {
		if ( ! class_exists( 'LP_Addon_Course_Review' ) ) {
			return;
		}
		if ( ! $course_id ) {
			$course_id = get_the_ID();
		}
		$ratings = learn_press_get_course_rate_total( $course_id ) ? learn_press_get_course_rate_total( $course_id ) : 0;
		echo '<div class="course-comments-count">';
		echo '<div class="value"><i class="edu-comment"></i>';
		echo esc_html( $ratings );
		echo '</div>';
		echo '</div>';
	}
}


/**
 * Display course ratings
 */
if ( ! function_exists( 'thim_course_ratings' ) ) {
	function thim_course_ratings() {
		if ( ! class_exists( 'LP_Addon_Course_Review' ) || ! function_exists( 'learn_press_course_meta_primary_review' ) ) {
			return;
		}
		learn_press_course_meta_primary_review();
	}
}

if ( ! function_exists( 'thim_print_rating' ) ) {
	function thim_print_rating( $rated ) {
		if ( version_compare( LP_ADDON_COURSE_REVIEW_VER, '4.1.6', '>=' ) ) {
			echo CourseRatingTemplate::instance()->html_rated_star( $rated );
		}
	}
}


if ( ! function_exists( 'thim_content_item_edit_link' ) ) {
	function thim_content_item_edit_link() {
		$course      = learn_press_get_course();
		$course_item = LP_Global::course_item();
		$user        = learn_press_get_current_user();
		if ( $user->can_edit_item( $course_item->get_id(), $course->get_id() ) ) :
			?>
			<p class="edit-course-item-link">
				<a href="<?php echo esc_url( get_edit_post_link( $course_item->get_id() ) ); ?>"><i
						class="edu-pencil-square-o"></i> <?php esc_html_e( 'Edit item', 'eduma' ); ?>
				</a>
			</p>
			<?php
		endif;
	}
}
add_action( 'learn-press/after-course-item-content', 'thim_content_item_edit_link', 3 );

if ( ! function_exists( 'thim_content_item_lesson_media' ) ) {
	function thim_content_item_lesson_media() {
		$item                  = LP_Global::course_item();
		$user                  = learn_press_get_current_user();
		$course_item           = LP_Global::course_item();
		$course                = learn_press_get_course();
		$is_no_required_enroll = $is_block = false;

		if ( thim_is_new_learnpress( '4.0.8' ) ) {
			$can_view_content_course = $user->can_view_content_course( $course->get_id() );
			$is_no_required_enroll   = $course->is_no_required_enroll();
			$can_view_item           = $user->can_view_item( $course_item->get_id(), $can_view_content_course );
			$is_block                = $can_view_item->flag;
		} else {
			$can_view_item = $user->can_view_item( $course_item->get_id(), $course->get_id() );
			if ( ! $course_item->is_blocked() && $can_view_item ) {
				$is_block = true;
			}
		}

		$media_intro = get_post_meta( $item->get_id(), '_lp_lesson_video_intro', true );

		if ( ! empty( $media_intro ) && $is_block || $is_no_required_enroll ) {
			?>
			<div class="learn-press-video-intro">
				<div class="video-content">
					<?php
					if ( wp_oembed_get( $media_intro ) ) {
						echo '<div class="responsive-iframe">' . wp_oembed_get( $media_intro ) . '</div>';
					} else {
						echo str_replace(
							array( '<iframe', '</iframe>' ),
							array(
								'<div class="responsive-iframe"><iframe',
								'</iframe></div>',
							),
							do_shortcode( $media_intro )
						);
					}
					?>
				</div>
			</div>
			<?php
		}
	}
}

//add_action( 'learn-press/before-course-item-content', 'thim_content_item_lesson_media', 5 );
add_action( 'learn-press/before-content-item-summary/lp_lesson', 'thim_content_item_lesson_media', 5 );

if ( ! function_exists( 'thim_couses_item_media' ) ) {
	function thim_couses_item_media( $thumbnail_html ) {
		$media_intro = get_post_meta( get_the_ID(), 'thim_course_media_intro', true );
		if ( ! empty( $media_intro ) ) {
			$thumbnail_html = '<div class="media-intro">';
			if ( wp_oembed_get( $media_intro ) ) {
				$thumbnail_html .= '<div class="responsive-iframe">' . wp_oembed_get( $media_intro ) . '</div>';
			} else {
				$thumbnail_html .= str_replace(
					array( '<iframe', '</iframe>' ),
					array(
						'<div class="responsive-iframe"><iframe',
						'</iframe></div>',
					),
					do_shortcode( $media_intro )
				);
			}
			$thumbnail_html .= '</div>';
		}

		return $thumbnail_html;
	}

	add_filter( 'thim-ekit/single-course/thumbnail', 'thim_couses_item_media' );
}

/*
 * Before Curiculumn on item page
 */
if ( ! function_exists( 'thim_before_curiculumn_item_func' ) ) {
	function thim_before_curiculumn_item_func() {
		$args = array();
		$args = wp_parse_args(
			$args,
			apply_filters(
				'learn_press_breadcrumb_defaults',
				array(
					'delimiter'   => '<i class="fa-angle-right fa"></i>',
					'wrap_before' => '<nav class="thim-font-heading learn-press-breadcrumb" ' . ( is_single() ? 'itemprop="breadcrumb"' : '' ) . '>',
					'wrap_after'  => '</nav>',
					'before'      => '',
					'after'       => '',
				)
			)
		);

		$breadcrumbs = new LP_Breadcrumb();

		$args['breadcrumb'] = $breadcrumbs->generate();

		learn_press_get_template( 'global/breadcrumb.php', $args );
	}
}
add_action( 'thim_before_curiculumn_item', 'thim_before_curiculumn_item_func' );

/**
 * @param LP_Quiz $item
 */
function thim_item_quiz_meta_questions( $item ) {
	$count = $item->count_questions();
	echo '<span class="meta count-questions">' . sprintf( $count ? _n( '%d question', '%d questions', $count, 'eduma' ) : __( '%d question', 'eduma' ), $count ) . '</span>';
}

//add_action( 'learn-press/course-section-item/before-lp_quiz-meta', 'thim_item_quiz_meta_questions', 5 );

/**
 * @param LP_Quiz|LP_Lesson $item
 */
if ( ! function_exists( 'thim_item_meta_duration' ) ) {
	function thim_item_meta_duration( $item ) {
		$duration = $item->get_duration();

		if ( is_a( $duration, 'LP_Duration' ) && $duration->get() ) {
			$format = array(
				'day'    => '%s ' . _x( 'day', 'duration', 'eduma' ),
				'hour'   => '%s ' . _x( 'hour', 'duration', 'eduma' ),
				'minute' => '%s ' . _x( 'min', 'duration', 'eduma' ),
				'second' => '%s ' . _x( 'sec', 'duration', 'eduma' ),
			);

			if ( 1 == absint( $duration->to_timer( $format, true ) ) ) {
				$format = array(
					'day'    => '%s ' . _x( 'day', 'duration', 'eduma' ),
					'hour'   => '%s ' . _x( 'hour', 'duration', 'eduma' ),
					'minute' => '%s ' . _x( 'min', 'duration', 'eduma' ),
					'second' => '%s ' . _x( 'sec', 'duration', 'eduma' ),
				);
			}

			echo '<span class="meta duration">' . $duration->to_timer( $format, true ) . '</span>';

		} elseif ( is_string( $duration ) && strlen( $duration ) ) {
			echo '<span class="meta duration">' . $duration . '</span>';
		}
	}
}
//add_action( 'learn-press/course-section-item/before-lp_lesson-meta', 'thim_item_meta_duration', 5 );

/**
 * Add format icon before curriculum items
 *
 * @param $lesson_or_quiz
 * @param $enrolled
 */
if ( ! function_exists( 'thim_add_format_icon' ) ) {
	function thim_add_format_icon( $item ) {
		$format = get_post_format( $item->get_id() );
		if ( get_post_type( $item->get_id() ) == 'lp_quiz' || get_post_type( $item->get_id() ) == 'lp_h5p' ) {
			echo '<span class="course-format-icon"><i class="' . eduma_font_icon( 'puzzle-piece' ) . '"></i></span>';
		} elseif ( get_post_type( $item->get_id() ) == 'lp_assignment' ) {
			echo '<span class="course-format-icon"><i class="' . eduma_font_icon( 'book' ) . '"></i></span>';
		} elseif ( $format == 'video' ) {
			echo '<span class="course-format-icon"><i class="' . eduma_font_icon( 'play-circle' ) . '"></i></span>';
		} elseif ( $format == 'image' ) {
			echo '<span class="course-format-icon"><i class="' . eduma_font_icon( 'images' ) . '"></i></span>';
		} elseif ( $format == 'link' ) {
			echo '<span class="course-format-icon"><i class="' . eduma_font_icon( 'link' ) . '"></i></span>';
		} elseif ( $format == 'gallery' ) {
			echo '<span class="course-format-icon"><i class="' . eduma_font_icon( 'images' ) . '"></i></span>';
		} elseif ( $format == 'audio' ) {
			echo '<span class="course-format-icon"><i class="' . eduma_font_icon( 'volume-up' ) . '"></i></span>';
		} else {
			echo '<span class="course-format-icon"><i class="' . eduma_font_icon( 'file' ) . '"></i></span>';
		}
	}
}

add_action( 'learn_press_before_section_item_title', 'thim_add_format_icon', 10, 1 );

if ( ! function_exists( 'thim_course_tabs_content' ) ) {
	function thim_course_tabs_content( $defaults ) {
		$group_tab = get_theme_mod(
			'group_tabs_course',
			array(
				'description',
				'curriculum',
				'instructor',
				'students-list',
				'faqs',
				'review',
				'materials',
			)
		);
		if ( empty( $group_tab ) ) {
			return;
		}
		$group_tab = str_replace( array( 'description', 'review' ), array( 'overview', 'reviews' ), $group_tab );

		foreach ( $defaults as $k => $v ) {
			switch ( $k ) {
				case 'overview':
					$v['icon'] = 'edu-bookmark';
					break;
				case 'reviews':
					$v['icon'] = 'edu-comments';
					break;
				case 'curriculum':
					$v['icon'] = 'edu-bars';
					break;
				case 'instructor':
					$v['icon'] = 'edu-user';
					break;
				case 'announcements':
					$v['icon'] = 'edu-envelope';
					break;
				case 'students-list':
					$v['icon'] = 'edu-user-friends';
					break;
				case 'faqs':
					$v['icon'] = 'edu-question-circle';
					break;
				case 'package':
					$v['icon'] = 'edu-cube';
					break;
				case 'materials':
					$v['icon'] = 'edu-dropbox';
					break;
			}
			$defaults[ $k ] = $v;
			$new_prioty     = array_keys( $group_tab, $k );

			if ( $new_prioty ) {
				$defaults[ $k ]['priority'] = $new_prioty[0];
			} else {
				unset( $defaults[ $k ] );
			}
		}

		$tab = get_theme_mod( 'default_tab_course', 'description' );
		$tab = str_replace( array( 'description', 'review' ), array( 'overview', 'reviews' ), $tab );
		if ( isset( $defaults[ $tab ] ) ) {
			$defaults[ $tab ]['active'] = true;
		}

		return $defaults;
	}
}

// check add_filter when have build as elementor kit
$overwritten_tab = true;
if ( class_exists( '\Thim_EL_Kit\Functions' ) ) {
	$build_el = \Thim_EL_Kit\Functions::instance()->get_conditions_by_type( 'single-course' );
	if ( ! empty( $build_el['all'] ) ) {
		$overwritten_tab = false;
	}
}
if ( $overwritten_tab ) {
	add_filter( 'learn-press/course-tabs', 'thim_course_tabs_content', 9999 );
}


/**
 * Display feature certificate
 *
 * @param $course_id
 */
if ( ! function_exists( 'thim_course_certificate' ) ) {
	function thim_course_certificate( $course_id ) {

		if ( class_exists( 'LP_Addon_Certificates_Preload' ) && thim_is_version_addons_certificates( '3' ) ) {
			?>
			<li class="cert-feature">
				<?php echo '<i class="' . eduma_font_icon( 'certificate' ) . '"></i>'; ?>
				<span class="label"><?php esc_html_e( 'Certificate', 'eduma' ); ?></span>
				<span
					class="value"><?php echo ( get_post_meta( $course_id, '_lp_cert', true ) ) ? esc_html__( 'Yes', 'eduma' ) : esc_html__( 'No', 'eduma' ); ?></span>
			</li>
			<?php
		}
	}
}

if ( ! function_exists( 'thim_get_post_translated_duration' ) ) {
	function thim_get_post_translated_duration( $course_duration, $default = '' ) {
		$duration_arr = isset( $course_duration ) ? explode( ' ', $course_duration ) : '';
		$duration_str = '';
		if ( count( $duration_arr ) > 1 ) {
			$duration_number = $duration_arr[0];
			$duration_text   = $duration_arr[1];
			switch ( strtolower( $duration_text ) ) {
				case 'minute':
					$duration_str = sprintf( _n( '%s minute', '%s minutes', $duration_number, 'learnpress' ), $duration_number );
					break;
				case 'hour':
					$duration_str = sprintf( _n( '%s hour', '%s hours', $duration_number, 'learnpress' ), $duration_number );
					break;
				case 'day':
					$duration_str = sprintf( _n( '%s day', '%s days', $duration_number, 'learnpress' ), $duration_number );
					break;
				case 'week':
					$duration_str = sprintf( _n( '%s week', '%s weeks', $duration_number, 'learnpress' ), $duration_number );
					break;
				default:
					$duration_str = $course_duration;
			}
		}

		return empty( absint( $course_duration ) ) ? $default : $duration_str;
	}
}
/**
 * Display course info
 */
if ( ! function_exists( 'thim_course_info' ) ) {
	function thim_course_info( $for_single = true ) {
		$course = learn_press_get_course();
		if ( ! $course ) {
			return;
		}
		$course_id = $course->get_id();

		if ( thim_is_new_learnpress( '4.0' ) ) {
			$course_skill_level = learn_press_get_post_level( $course_id );
			$course_duration    = get_post_meta( $course_id, 'thim_course_duration', true ) ? get_post_meta( $course_id, 'thim_course_duration', true ) : get_post_meta( $course_id, '_lp_duration', true );
		} else {
			$course_skill_level = get_post_meta( $course_id, 'thim_course_skill_level', true );
			$course_duration    = get_post_meta( $course_id, 'thim_course_duration', true );
		}
		$course_language = get_post_meta( $course_id, 'thim_course_language', true );

		?>
		<div class="thim-course-info">
			<?php
			if ( $for_single == '' ) {
				echo '<h3 class="title">' . esc_html__( 'Course Features', 'eduma' ) . '</h3>';
			}
			?>

			<ul>
				<?php do_action( 'thim_course_list_info_before' ); ?>

				<li class="lectures-feature">
					<?php echo '<i class="' . eduma_font_icon( 'file' ) . '"></i>'; ?>
					<span class="label">
					<?php
					$lecture_label = esc_html__( 'Lecture', 'eduma' );
					if ( $course->count_items( LP_LESSON_CPT ) > 1 ) {
						$lecture_label = esc_html__( 'Lectures', 'eduma' );
					}

					echo $lecture_label;
					?>
					</span>
					<span class="value">
						<?php
						echo $course->count_items( LP_LESSON_CPT );
						?>
					</span>
				</li>
				<li class="quizzes-feature">
					<?php echo '<i class="' . eduma_font_icon( 'puzzle-piece' ) . '"></i>'; ?>
					<span class="label">
					<?php
					$quiz_label = esc_html__( 'Quiz', 'eduma' );
					if ( $course->count_items( 'lp_quiz' ) > 1 ) {
						$quiz_label = esc_html__( 'Quizzes', 'eduma' );
					}
					echo $quiz_label;
					?>
					</span>
					<span class="value">
						<?php
						echo $course->count_items( 'lp_quiz' );
						?>
					</span>
				</li>

				<?php if ( ! empty( $course_duration ) ) : ?>
					<li class="duration-feature">
						<?php echo '<i class="' . eduma_font_icon( 'clock' ) . '"></i>'; ?>
						<span class="label"><?php esc_html_e( 'Duration', 'eduma' ); ?></span>
						<span
							class="value"><?php echo thim_get_post_translated_duration( $course_duration, esc_html__( 'Lifetime access', 'eduma' ) ); ?></span>
					</li>
				<?php endif; ?>

				<?php if ( ! empty( $course_skill_level ) ) : ?>
					<li class="skill-feature">
						<?php echo '<i class="' . eduma_font_icon( 'signal' ) . '"></i>'; ?>
						<span class="label"><?php esc_html_e( 'Skill level', 'eduma' ); ?></span>
						<span class="value"><?php echo esc_html( $course_skill_level ); ?></span>
					</li>
				<?php endif; ?>
				<?php if ( ! empty( $course_language ) ) : ?>
					<li class="language-feature">
						<?php echo '<i class="' . eduma_font_icon( 'language' ) . '"></i>'; ?>
						<span class="label"><?php esc_html_e( 'Language', 'eduma' ); ?></span>
						<span class="value"><?php echo esc_html( $course_language ); ?></span>
					</li>
				<?php endif; ?>
				<li class="students-feature">
					<?php echo '<i class="' . eduma_font_icon( 'students' ) . '"></i>'; ?>
					<span class="label"><?php esc_html_e( 'Students', 'eduma' ); ?></span>
					<?php $user_count = $course->get_users_enrolled() ? $course->get_users_enrolled() : 0; ?>
					<span class="value"><?php echo esc_html( $user_count ); ?></span>
				</li>
				<?php thim_course_certificate( $course_id ); ?>
				<li class="assessments-feature">
					<?php echo '<i class="' . eduma_font_icon( 'check-square-o' ) . '"></i>'; ?>
					<span class="label"><?php esc_html_e( 'Assessments', 'eduma' ); ?></span>
					<span
						class="value"><?php echo ( get_post_meta( $course_id, '_lp_course_result', true ) == 'evaluate_lesson' ) ? esc_html__( 'Yes', 'eduma' ) : esc_html__( 'Self', 'eduma' ); ?></span>
				</li>

				<?php do_action( 'thim_course_list_info_after' ); ?>
			</ul>
			<?php
			if ( $for_single == '' ) {
				do_action( 'thim_after_course_info' );
			}
			?>
		</div>
		<?php
	}
}

/**
 * Display the link to course forum
 */
if ( ! function_exists( 'thim_course_forum_link' ) ) {
	function thim_course_forum_link() {

		if ( ( thim_plugin_active( 'bbpress/bbpress.php' ) && thim_plugin_active( 'learnpress-bbpress/learnpress-bbpress.php' ) ) && thim_is_version_addons_bbpress( '3' ) ) {
			LP_Addon_bbPress::instance()->forum_link();
		}
	}
}

/*
 * Add real students enrolled meta for orderby attribute in the query
 * */
// add_action( 'init', 'thim_add_real_student_enrolled_meta' );

// if ( ! function_exists( 'thim_add_real_student_enrolled_meta' ) ) {
// 	function thim_add_real_student_enrolled_meta( $lp ) {
// 		if ( isset( $_POST['course_orderby'] ) && 'most-members' == $_POST['course_orderby'] ) {
// 			$arg = array(
// 				'post_type'           => 'lp_course',
// 				'post_status'         => 'publish',
// 				'posts_per_page'      => - 1,
// 				'ignore_sticky_posts' => true,
// 			);

// 			$course_query = new WP_Query( $arg );

// 			if ( $course_query->have_posts() ) {
// 				while ( $course_query->have_posts() ) {
// 					$course_query->the_post();

// 					$course = learn_press_get_course( get_the_ID() );

// 					update_post_meta( get_the_ID(), 'thim_real_student_enrolled', $course->get_users_enrolled() );
// 				}

// 				wp_reset_postdata();
// 			}
// 		}
// 	}
// }

/**
 * Add filter link login redirecti frontend editor
 */
add_filter( 'learn-press/frontend-editor/login-redirect', 'thim_custom_link_login_frontend_editor', 10, 1 );
if ( ! function_exists( 'thim_custom_link_login_frontend_editor' ) ) {
	function thim_custom_link_login_frontend_editor() {
		$url = add_query_arg( 'redirect_to', get_permalink( get_the_ID() ), thim_get_login_page_url() );

		return $url;
	}
}
/**
 * Display related courses
 */
if ( ! function_exists( 'thim_related_courses' ) ) {
	function thim_related_courses() {
		$related_courses = thim_lp_get_related_courses( 5 );
		if ( $related_courses->have_posts() ) {
			?>
			<div class="thim-related-course">
				<?php if ( get_theme_mod( 'thim_layout_content_page', 'normal' ) == 'new-1' ) { ?>
					<div class="sc_heading clone_title text-left">
						<h2 class="title"><?php esc_html_e( 'You May Like', 'eduma' ); ?></h2>
						<div class="clone"><?php esc_html_e( 'You May Like', 'eduma' ); ?></div>
					</div>
				<?php } else { ?>
					<h3 class="related-title">
						<?php esc_html_e( 'You May Like', 'eduma' ); ?>
					</h3>
				<?php } ?>

				<div class="thim-course-grid">
					<div class="thim-carousel-wrapper" data-visible="3" data-itemtablet="2" data-itemmobile="1"
						data-pagination="1">

						<?php
						while ( $related_courses->have_posts() ) {
							$related_courses->the_post();

							learn_press_get_template_part( 'content', 'course' );
						}
						?>

					</div>
				</div>
			</div>
			<?php
		}

		wp_reset_postdata();
	}
}

if ( ! function_exists( 'thim_lp_get_related_courses' ) ) {
	function thim_lp_get_related_courses( $limit ) {
		if ( ! $limit ) {
			$limit = 3;
		}
		$course_id = get_the_ID();

		$tag_ids = array();
		$tags    = get_the_terms( $course_id, 'course_tag' );

		if ( $tags ) {
			foreach ( $tags as $individual_tag ) {
				$tag_ids[] = $individual_tag->term_id;
			}
		}

		$args = array(
			'posts_per_page'      => $limit,
			'paged'               => 1,
			'ignore_sticky_posts' => 1,
			'post__not_in'        => array( $course_id ),
			'post_type'           => 'lp_course',
		);

		if ( $tag_ids ) {
			$args['tax_query'] = array(
				array(
					'taxonomy' => 'course_tag',
					'field'    => 'term_id',
					'terms'    => $tag_ids,
				),
			);
		}

		$query = new WP_Query( $args );

		return $query;
	}
}
// old for child theme
if ( ! function_exists( 'thim_get_related_courses' ) ) {
	function thim_get_related_courses( $limit ) {
		global $post;
		$related = array();

		$related_courses = thim_lp_get_related_courses( $limit );

		while ( $related_courses->have_posts() ) {
			$related_courses->the_post();
			$related[] = $post;
		}

		wp_reset_postdata();

		return $related;
	}
}

/*
 * Courses filter template
 */
if ( ! function_exists( 'thim_display_course_filter' ) ) {
	function thim_display_course_filter() {
		if ( get_theme_mod( 'thim_display_course_filter', false ) ) {
			$instance['title']                 = '';
			$instance['show_in_rest']          = 1;
			$instance['search_suggestion']     = get_theme_mod( 'filter_search_suggestion', true );
			$instance['number_level_category'] = get_theme_mod( 'number_level_category', 2 );

			// default field - Get old data of key thim_filter_by_cate, thim_filter_by_instructor, thim_filter_by_price
			$filter_course_field_default = array();
			if ( get_theme_mod( 'thim_filter_by_cate', false ) ) {
				$filter_course_field_default[] = 'category';
			}
			if ( get_theme_mod( 'thim_filter_by_instructor', false ) ) {
				$filter_course_field_default[] = 'author';
			}
			if ( get_theme_mod( 'thim_filter_by_price' ) && get_theme_mod( 'thim_single_course_offline', false ) == false ) {
				$filter_course_field_default[] = 'price';
			}
			$filter_course_field_default[] = 'btn_submit';
			$filter_course_field_default[] = 'btn_reset';

			$instance['fields'] = get_theme_mod( 'filter_course_field', $filter_course_field_default );

			the_widget(
				'LP_Widget_Course_Filter',
				$instance,
				array(
					'before_widget' => '<div class="widget filter-course %s">',
					'after_widget'  => '</div>',
				)
			);
		}
	}
}

add_action( 'thim_before_sidebar_course', 'thim_display_course_filter' );

/**
 * Create ajax handle for courses searching
 */
if ( ! function_exists( 'thim_courses_searching_callback' ) ) {
	function thim_courses_searching_callback() {
		check_ajax_referer( 'thim_courses_searching_nonce', 'nonce' );

		$keyword = sanitize_text_field( wp_unslash( $_REQUEST['keyword'] ?? '' ) );
		$page    = absint( $_REQUEST['page'] ?? 1 );

		$newdata = array();

		if ( $keyword ) {
			$keyword = strtoupper( $keyword );

			add_filter(
				'posts_search',
				function ( $search, $wp_query ) {
					global $wpdb;

					if ( ! empty( $wp_query->get( 'search_title_only' ) ) && ! empty( $wp_query->get( 's' ) ) ) {
						$keyword = $wp_query->get( 's' );
						$like    = '%' . $wpdb->esc_like( $keyword ) . '%';
						$search  = $wpdb->prepare( " AND {$wpdb->posts}.post_title LIKE %s ", $like );
					}

					return $search;
				},
				10,
				2
			);

			$arr_query = array(
				'post_type'           => apply_filters( 'thim/lp_courses/searching/post_type', 'lp_course' ),
				'post_status'         => 'publish',
				'ignore_sticky_posts' => true,
				's'                   => $keyword,
				'posts_per_page'      => 5,
				'paged'               => $page,
				'search_title_only'   => true,
			);

			$search = new WP_Query( $arr_query );

			remove_all_filters( 'posts_search' );

			foreach ( $search->posts as $post ) {
				$thumbnail   = get_the_post_thumbnail_url( $post->ID, 'thumbnail' );
				$author_name = get_the_author_meta( 'display_name', $post->post_author );

				$newdata[] = array(
					'id'        => $post->ID,
					'title'     => $post->post_title,
					'guid'      => get_permalink( $post->ID ),
					'thumbnail' => $thumbnail,
					'author'    => $author_name,
				);
			}

			wp_reset_postdata();

			if ( count( $search->posts ) ) {
				if ( $search->max_num_pages > $page ) {
					$newdata[] = array(
						'see_more' => esc_html__( 'See More', 'eduma' ),
					);
				}
			} else {
				$newdata[] = array(
					'id'    => '',
					'title' => esc_html__( 'No course found', 'eduma' ),
					'guid'  => '#',
				);

				$newdata = apply_filters( 'thim/lp/courses/searching/not-found-posts', $newdata, $keyword );
			}
		}

		echo json_encode( $newdata );
		die;
	}
}

add_action( 'wp_ajax_nopriv_courses_searching', 'thim_courses_searching_callback' );
add_action( 'wp_ajax_courses_searching', 'thim_courses_searching_callback' );
/**
 * Show thumbnail single course
 */
if ( ! function_exists( 'thim_course_thumbnail_item' ) ) {
	function thim_course_thumbnail_item() {
		learn_press_get_template( 'single-course/thumbnail.php' );
	}
}

if ( ! function_exists( 'thim_landing_tabs' ) ) {
	function thim_landing_tabs() {
		learn_press_get_template( 'single-course/tabs/tabs-landing.php' );
	}
}

if ( ! function_exists( 'show_pass_text' ) ) {
	function show_pass_text() {
		$user   = learn_press_get_current_user();
		$course = learn_press_get_course();

		if ( ! $course ) {
			return;
		}

		$grade = $user->get_course_grade( $course->get_id() );
		if ( $grade == 'passed' ) {
			echo '<div class="message message-success learn-press-success">' . ( __( 'You have finished this course.', 'eduma' ) ) . '</div>';
		}
	}
}
add_action( 'thim_begin_curriculum_button', 'show_pass_text', 5 );

/**
 * Show popular Courses
 */
if ( ! function_exists( 'thim_show_popular_courses' ) ) {
	function thim_show_popular_courses() {
		$show_popular = get_theme_mod( 'thim_learnpress_cate_show_popular' );
		if ( $show_popular && is_post_type_archive( 'lp_course' ) ) {
			$condition = array(
				'post_type'           => 'lp_course',
				'posts_per_page'      => 6,
				'ignore_sticky_posts' => true,
				'meta_query'          => array(
					array(
						'key'   => '_lp_featured',
						'value' => 'yes',
					),
				),
			);
			$the_query = new WP_Query( $condition );
			if ( $the_query->have_posts() ) :
				?>
				<div class="feature_box_before_archive">
					<div class="container">
						<div class="thim-widget-heading thim-widget-heading-base">
							<div class="sc_heading clone_title text-center">
								<h2 class="title"><?php esc_html_e( 'Popular Courses', 'eduma' ); ?></h2>
								<div class="clone"><?php esc_html_e( 'Popular Courses', 'eduma' ); ?></div>
							</div>
						</div>
						<div class="thim-carousel-wrapper thim-course-carousel thim-course-grid" data-visible="4"
							data-pagination="true" data-navigation="false" data-autoplay="false">
							<?php
							while ( $the_query->have_posts() ) :
								$the_query->the_post();
								learn_press_get_template_part( 'content', 'course' );
							endwhile;
							?>
						</div>
					</div>
				</div>
				<?php
			endif;
			wp_reset_postdata();
		}
	}
}
add_action( 'thim_before_site_content', 'thim_show_popular_courses' );

if ( ! function_exists( 'thim_course_wishlist_button' ) ) {
	function thim_course_wishlist_button( $course_id = null ) {
		if ( ! class_exists( 'LP_Addon_Wishlist' ) ) {
			return;
		}

		LP_Addon_Wishlist::instance()->wishlist_button( $course_id );
	}
}

/*
 * Hide ads Learnpress
 */
if ( get_theme_mod( 'thim_learnpress_hidden_ads', false ) ) {
	remove_action( 'admin_footer', 'learn_press_footer_advertisement', - 10 );
}

#
# Mark flag $_SESSION['eduma_do_redirect_to'] for redirect
#
add_action( 'miniorange_collect_attributes_for_authenticated_user', 'eduma_callback_action_miniorange_collect_attributes_for_authenticated_user', 10, 2 );
function eduma_callback_action_miniorange_collect_attributes_for_authenticated_user( $user, $mo_openid_redirect_url ) {
	$_SESSION['eduma_do_redirect_to'] = true;

	return $user;
}

#
# Do redirect if flag $_SESSION['eduma_do_redirect_to'] == true
#
add_filter( 'wp_redirect', 'eduma_redirect_after_mo_login', 10 );
function eduma_redirect_after_mo_login( $redirect_url ) {
	if ( isset( $_SESSION['mo_login'] ) && $_SESSION['mo_login'] == false
		&& isset( $_SESSION['eduma_do_redirect_to'] ) && $_SESSION['eduma_do_redirect_to'] == true
		&& isset( $_SESSION['eduma_redirect_to'] )
	) {
		$redirect_url = $_SESSION['eduma_redirect_to'];
		unset( $_SESSION['eduma_redirect_to'] );
		unset( $_SESSION['eduma_do_redirect_to'] );
	}
	if ( is_user_logged_in() ) {
		if ( ! session_id() ) {
			return $redirect_url;
		}
		if ( isset( $_SESSION['eduma_redirect_to'] ) ) {
			$redirect_url = $_SESSION['eduma_redirect_to'];
			unset( $_SESSION['eduma_redirect_to'] );
			if ( isset( $_SESSION['eduma_do_redirect_to'] ) ) {
				unset( $_SESSION['eduma_do_redirect_to'] );
			}
		}
	}

	return $redirect_url;
}


#
# Rewrite javascript function moOpenIdLogin to add redirect url after login
#
add_action( 'learn_press_after_single_course', 'thim_action_callback_learn_press_after_single_course', 100 ); // work for LearnPress 2
add_action( 'learn-press/after-single-course', 'thim_action_callback_learn_press_after_single_course', 100 ); // work for LearnPress 3
if ( ! function_exists( 'thim_action_callback_learn_press_after_single_course' ) ) {
	function thim_action_callback_learn_press_after_single_course() {
		if ( is_user_logged_in() || ! learn_press_is_course() ) {
			return;
		}
		$course = learn_press_get_course();
		if ( ! is_object( $course ) ) {
			return;
		}
		?>
		<script type="text/javascript">
			function moOpenIdLogin(app_name, is_custom_app) {
				<?php

				if ( isset( $_SERVER['HTTPS'] ) && ! empty( $_SERVER['HTTPS'] ) && $_SERVER['HTTPS'] != 'off' ) {
					$http = 'https://';
				} else {
					$http = 'http://';
				}
				?>
				var base_url = '<?php echo esc_url( home_url( '/' ) ); ?>';
				var request_uri = '<?php echo esc_js( wp_unslash( $_SERVER['REQUEST_URI'] ) ); ?>';
				var http = '<?php echo esc_js( $http ); ?>';
				var http_host = '<?php echo esc_js( wp_unslash( $_SERVER['HTTP_HOST'] ) ); ?>';
				if (is_custom_app == 'false') {
					if (request_uri.indexOf('wp-login.php') != -1) {
						var redirect_url = base_url + '/?option=getmosociallogin&app_name=';

					} else {
						var redirect_url = http + http_host + request_uri;
						if (redirect_url.indexOf('?') != -1) {
							redirect_url = redirect_url + '&option=getmosociallogin&app_name=';
						} else {
							redirect_url = redirect_url + '?option=getmosociallogin&app_name=';
						}
					}
				} else {
					var current_url = window.location.href;
					var cname = 'redirect_current_url';
					var d = new Date();
					d.setTime(d.getTime() + (2 * 24 * 60 * 60 * 1000));
					var expires = 'expires=' + d.toUTCString();
					document.cookie = cname + '=' + current_url + ';' + expires + ';path=/';   //path = root path(/)
					if (request_uri.indexOf('wp-login.php') != -1) {
						var redirect_url = base_url + '/?option=oauthredirect&app_name=';
					} else {
						var redirect_url = http + http_host + request_uri;
						if (redirect_url.indexOf('?') != -1)
							redirect_url = redirect_url + '&option=oauthredirect&app_name=';
						else
							redirect_url = redirect_url + '?option=oauthredirect&app_name=';
					}
				}
				var redirect_to = jQuery('#loginform input[name="redirect_to"]').val();
				redirect_url = redirect_url + app_name + '&redirect_to=' + encodeURIComponent(redirect_to);
				window.location.href = redirect_url;
			}
		</script>
		<?php
	}
}
if ( ! function_exists( 'thim_get_all_courses_instructors' ) ) {
	function thim_get_all_courses_instructors( $limits = 4 ) {
		$teacher       = array();
		$args_users    = array(
			'role'   => 'lp_teacher',
			'number' => $limits,
		);
		$my_user_query = new WP_User_Query( $args_users );
		$users_by_role = $my_user_query->get_results();

		if ( $users_by_role ) {
			foreach ( $users_by_role as $user ) {
				$teacher[] = array(
					'user_id' => $user->ID,
				);
			}
		}

		return $teacher;
	}
}

/*
 * add button read more action thim-lp-course-button-read-more
 * Fix for add-on LP Woo Payment has button add-to-cart
 */
if ( ! function_exists( 'thim_button_read_more_course' ) ) {
	function thim_button_read_more_course() {
		$course = learn_press_get_course();
		if ( $course ) {
			echo '<a class="course-readmore" href="' . esc_url( get_the_permalink( $course->get_id() ) ) . '" >' . esc_html__( 'Enroll Now', 'eduma' ) . '</a>';
		}
	}
}


/*
 * learnpress_loop_item_title
 */

add_action( 'learnpress_loop_item_title', 'thim_learnpress_loop_item_title', 10 );
add_action( 'learnpress_loop_item_title', 'learn_press_courses_loop_item_instructor', 5 );
if ( ! function_exists( 'thim_learnpress_loop_item_title' ) ) {
	function thim_learnpress_loop_item_title() {
		the_title( sprintf( '<h2 class="course-title"><a href="%s" rel="bookmark">', esc_url( get_permalink() ) ), '</a></h2>' );
	}
}
if ( thim_lp_style_content_course() == 'layout_style_2' ) {
	add_action( 'thim-lp-course-button-read-more', 'learn_press_courses_loop_item_price', 15 );
	add_action( 'learnpress_loop_item_title', 'list_item_course_cat', 5 );
	remove_action( 'learnpress_loop_item_title', 'learn_press_courses_loop_item_instructor', 5 );
	add_action( 'learnpress_loop_item_title', 'learn_press_courses_loop_item_instructor', 15 );
}

/*
 * learnpress description
 */
add_action( 'learnpress_loop_item_desc', 'thim_learnpress_loop_item_desc', 10 );

if ( ! function_exists( 'thim_learnpress_loop_item_desc' ) ) {
	function thim_learnpress_loop_item_desc() {
		if ( intval( get_theme_mod( 'thim_learnpress_excerpt_length', 25 ) ) ) {
			echo '<div class="course-description">';
			do_action( 'learn_press_before_course_content' );
			echo thim_excerpt( intval( get_theme_mod( 'thim_learnpress_excerpt_length', 25 ) ) );
			do_action( 'learn_press_after_course_content' );
			echo '</div>';
		}
	}
}

add_action( 'learnpress_loop_item_course_meta', 'thim_learnpress_loop_item_course_meta', 10 );
// add meta price and remove for layout list

if ( ! function_exists( 'thim_learnpress_loop_item_course_meta' ) ) {
	function thim_learnpress_loop_item_course_meta() {
		// show message comming soon
		if ( class_exists( 'LP_Addon_Coming_Soon_Courses' ) && learn_press_is_coming_soon( get_the_ID() ) ) :
			?>
			<div class="message message-warning learn-press-message coming-soon-message">
				<?php esc_html_e( 'Coming soon', 'eduma' ); ?>
			</div>
			<?php
		else :
			echo '<div class="course-meta">';

			learn_press_courses_loop_item_instructor();

			thim_learnpress_count_lesson();

			learn_press_get_template( 'loop/course/students.php' );

			if ( thim_lp_style_content_course() == 'layout_style_2' ) {
				thim_course_ratings();
			} else {
				learn_press_courses_loop_item_price();
			}

			echo '</div>';
		endif;
	}
}

add_action( 'learnpress_loop_item_price', 'learn_press_courses_loop_item_price', 5 );


if ( ! function_exists( 'thim_learnpress_count_lesson' ) ) {
	function thim_learnpress_count_lesson() {
		$course = learn_press_get_course();
		if ( $course ) {
			$total_lessons = $course->count_items( LP_LESSON_CPT );
			if ( version_compare( LEARNPRESS_VERSION, '4.2.7', '>=' ) ) {
				if ( $course->is_offline() ) {
					$total_lessons = get_post_meta( $course->get_id(), '_lp_offline_lesson_count', true );
				}
			}
			echo '<div class="course-lesson"><div class="value"><i aria-hidden="true" class="' . eduma_font_icon( 'file' ) . '"></i>' . $total_lessons . '</div></div>';
		}
	}
}

/**
 * Override LearnPress login URL to use the theme's login page
 * instead of the default WordPress login page.
 */
add_filter( 'learn-press/login-url', 'thim_override_learnpress_login_url' );

if ( ! function_exists( 'thim_override_learnpress_login_url' ) ) {
	function thim_override_learnpress_login_url( $url ) {
		return thim_get_login_page_url();
	}
}
