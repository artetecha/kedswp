<?php
/**
 * Template for displaying assignments tab in user profile page.
 *
 * This template can be overridden by copying it to yourtheme/learnpress/addons/assignments/profile/tabs/assignments.php.
 *
 * @author   ThimPress
 * @package  Learnpress/Assignments/Templates
 * @version  4.0.2
 */

use LearnPress\Helpers\Template;
use LearnPress\Models\CourseModel;
use LearnPressAssignment\Models\AssignmentPostModel;
use LearnPressAssignment\Models\UserAssignmentModel;

defined( 'ABSPATH' ) || exit();

$profile = LP_Profile::instance();
$user    = $profile->get_user();

$filter_status = LP_Request::get_param( 'filter-status', 'all' );
$curd          = new LP_Assignment_CURD();
$query         = $curd->query_profile_assignments( $profile->get_user_data( 'id' ), $filter_status );
$filters       = $curd->get_assignments_filters( $profile );
$items         = $query->get_items();
$tab_active    = 'all';
if ( $filter_status ) {
	$tab_active = $filter_status;
}
?>

<div class="learn-press-subtab-content">
	<?php if ( $filters ) : ?>
		<div class="learn-press-tabs">
			<ul class="lp-sub-menu learn-press-filters">
				<?php
				foreach ( $filters as $class => $link ) {
					$class_active = '';
					if ( $class === $tab_active ) {
						$class_active = 'active';
					}
					echo sprintf( '<li class="%1$s %2$s">%3$s</li>', esc_attr( $class ), $class_active, $link );
				}
				?>
			</ul>
		</div>
	<?php endif; ?>

	<?php if ( $items ) { ?>
		<table class="lp-list-table profile-list-assignments profile-list-table">
			<thead>
			<tr>
				<th class="column-assignment"><?php esc_html_e( 'Assignment', 'learnpress-assignments' ); ?></th>
				<th class="column-course"><?php esc_html_e( 'Course', 'learnpress-assignments' ); ?></th>
				<th class="column-padding-grade"><?php esc_html_e( 'Result', 'learnpress-assignments' ); ?></th>
				<th class="column-status"><?php esc_html_e( 'Status', 'learnpress-assignments' ); ?></th>
				<th class="column-mark"><?php esc_html_e( 'Mark', 'learnpress-assignments' ); ?></th>
				<th class="column-time-interval"><?php esc_html_e( 'Interval', 'learnpress-assignments' ); ?></th>
			</tr>
			</thead>

			<tbody>
			<?php
			foreach ( $items as $user_assignment ) :
				$userAssigmentModel = new UserAssignmentModel( $user_assignment );
				$assignment         = AssignmentPostModel::find( $user_assignment->item_id, true );
				if ( ! $assignment ) {
					continue;
				}

				$courseModel = CourseModel::find( $user_assignment->ref_id, true );
				if ( ! $courseModel ) {
					continue;
				}

				$graduation = $user_assignment->graduation;
				$mark       = learn_press_get_user_item_meta( $userAssigmentModel->get_user_item_id(), '_lp_assignment_mark', true );
				$status     = $user_assignment->status;
				?>

				<tr>
					<td class="column-assignment">
						<a href="<?php echo esc_url( $courseModel->get_item_link( $assignment->get_id() ) ); ?>">
							<?php echo $assignment->get_the_title(); ?>
						</a>
					</td>

					<td class="column-course">
						<a href="<?php echo esc_url( $courseModel->get_permalink() ); ?>">
							<?php echo $courseModel->get_title(); ?>
						</a>
					</td>

					<td class="column-passing-grade">
						<?php echo empty( $graduation ) ? __( '--', 'learnpress-assignments' ) : learn_press_get_graduation_text( $graduation ); ?>
					</td>

					<td class="column-status">
						<?php echo empty( $status ) ? __( '--', 'learnpress-assignments' ) : LP_Addon_Assignment::get_i18n_value( $status ); ?>
					</td>

					<td class="column-mark">
						<?php
						if ( $status === LP_ASSIGNMENT_STATUS_EVALUATED ) {
							echo sprintf( '%d/%d', $mark, $assignment->get_max_mark() );
						} else {
							_e( '--', 'learnpress-assignments' );
						}
						?>
					</td>
					<td class="column-time-interval">
						<?php
						$end_date = new LP_Datetime( $userAssigmentModel->get_end_time() );
						printf(
							'%s %s',
							sprintf( '<div>%s: %s</div>', __( 'Submitted on', 'learnpress-assignments' ), $end_date->format( LP_Datetime::I18N_FORMAT_HAS_TIME ) ),
							sprintf( '<div>%s: %s</div>', __( 'Time spent', 'learnpress-assignments' ), $userAssigmentModel->get_time_interval() )
						);
						?>
					</td>
				</tr>
				<tr>
					<td colspan="4"></td>
				</tr>
			<?php endforeach; ?>
			</tbody>

			<tfoot>
			<tr class="list-table-nav">
				<td colspan="2" class="nav-text">
					<?php echo $query->get_offset_text(); ?>
				</td>
				<td colspan="4" class="nav-pages">
					<?php $query->get_nav_numbers(); ?>
				</td>
			</tr>
			</tfoot>
		</table>

		<?php
	} else {
		Template::print_message( __( 'No assignments!', 'learnpress-assignments' ), 'info' );
	}
	?>
</div>
