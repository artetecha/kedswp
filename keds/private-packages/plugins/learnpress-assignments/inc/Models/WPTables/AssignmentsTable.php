<?php

namespace LearnPressAssignment\Models\WPTables;

use LP_Abstract_Post_Type;
use LP_Assignment_CURD;
use WP_Posts_List_Table;

/**
 * LearnPress Assignments Table class.
 */
class AssignmentsTable extends WP_Posts_List_Table {
	/**
	 * Get the table columns.
	 *
	 * @return array
	 */
	public function get_columns() {
		$columns = parent::get_columns();

		$pos = array_search( 'title', array_keys( $columns ) );
		if ( false !== $pos ) {
			$insert  = array(
				'author'        => esc_html__( 'Author', 'learnpress-assignments' ),
				'lp_course'     => esc_html__( 'Course', 'learnpress-assignments' ),
				'students'      => esc_html__( 'Students', 'learnpress-assignments' ),
				'mark'          => esc_html__( 'Max Mark', 'learnpress-assignments' ),
				'passing_grade' => esc_html__( 'Passing Grade', 'learnpress-assignments' ),
				'duration'      => esc_html__( 'Duration', 'learnpress-assignments' ),
				'actions'       => esc_html__( 'Actions', 'learnpress-assignments' ),
			);
			$columns = array_merge(
				array_slice( $columns, 0, $pos + 1 ),
				$insert,
				array_slice( $columns, $pos + 1 )
			);
		}

		unset( $columns['comments'] );
		unset( $columns['taxonomy-lesson-tag'] );

		$user = wp_get_current_user();
		if ( in_array( 'lp_teacher', $user->roles ) ) {
			unset( $columns['author'] );
		}

		return $columns;
	}

	/**
	 * Set columns can be sortable.
	 *
	 * @return array
	 */
	public function get_sortable_columns() {
		$sortable_columns              = parent::get_sortable_columns();
		$sortable_columns['author']    = array( 'author', 'asc' );
		$sortable_columns['lp_course'] = array( 'course-name', 'asc' );

		return $sortable_columns;
	}

	/**
	 * Column author.
	 *
	 * @param $post
	 *
	 * @return void
	 */
	public function column_author( $post ) {
		LP_Abstract_Post_Type::column_author( $post );
	}

	/**
	 * Column course.
	 *
	 * @param $post
	 *
	 * @return void
	 */
	public function column_lp_course( $post ) {
		$courses = learn_press_get_item_courses( $post->ID );
		if ( $courses ) {
			foreach ( $courses as $course ) {
				echo '<div><a href="' . esc_url( add_query_arg( array( 'filter_course' => $course->ID ) ) ) . '">' . get_the_title( $course->ID ) . '</a>';
				echo '<div class="row-actions">';
				printf( '<a href="%s">%s</a>', admin_url( sprintf( 'post.php?post=%d&action=edit', $course->ID ) ), esc_html__( 'Edit', 'learnpress-assignments' ) );
				echo '&nbsp;|&nbsp;';
				printf( '<a href="%s">%s</a>', get_the_permalink( $course->ID ), esc_html__( 'View', 'learnpress-assignments' ) );
				echo '</div></div>';
			}
		} else {
			esc_html_e( 'Not assigned yet', 'learnpress-assignments' );
		}
	}

	/**
	 * Column students count.
	 *
	 * @param $post
	 *
	 * @return void
	 */
	public function column_students( $post ) {
		$curd  = new LP_Assignment_CURD();
		$count = count( $curd->get_students( $post->ID ) );

		echo '<span class="lp-label-counter' . ( ! $count ? ' disabled' : '' ) . '">' . $count . '</span>';
	}

	/**
	 * Column max mark.
	 *
	 * @param $post
	 *
	 * @return void
	 */
	public function column_mark( $post ) {
		$maximum_mark = get_post_meta( $post->ID, '_lp_mark', true ) ?: 10;
		echo esc_html( $maximum_mark );
	}

	/**
	 * Column passing grade.
	 *
	 * @param $post
	 *
	 * @return void
	 */
	public function column_passing_grade( $post ) {
		$passing_grade = get_post_meta( $post->ID, '_lp_passing_grade', true ) ?: 7;
		echo esc_html( $passing_grade );
	}

	/**
	 * Column duration.
	 *
	 * @param $post
	 *
	 * @return void
	 */
	public function column_duration( $post ) {
		echo esc_html( learn_press_get_post_translated_duration( $post->ID, false ) );
	}

	/**
	 * Column actions.
	 *
	 * @param $post
	 *
	 * @return void
	 */
	public function column_actions( $post ) {
		printf(
			'<a href="%s" target="">%s</a>',
			learn_press_assignment_students_url( array( 'assignment_id' => $post->ID ) ),
			esc_html__( 'View Submissions', 'learnpress-assignments' )
		);
	}
}
