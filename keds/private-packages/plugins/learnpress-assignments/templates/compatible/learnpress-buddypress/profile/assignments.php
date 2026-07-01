<?php
/**
 * Template for displaying BuddyPress profile assignments page.
 *
 * @author   ThimPress
 * @package  LearnPress/Assignments/Templates
 * @version  3.0.1
 */

use LearnPress\Helpers\Template;
use LearnPress\Models\CourseModel;
use LearnPressAssignment\Models\AssignmentPostModel;
use LearnPressAssignment\Models\UserAssignmentModel;

defined( 'ABSPATH' ) || exit();

$profile = LP_Profile::instance();
$user    = $profile->get_user();
$curd    = new LP_Assignment_CURD();
$query   = $curd->query_profile_assignments( $profile->get_user_data( 'id' ) );
$items   = $query->get_items();
?>

<?php if ( $items ) { ?>
	<table class="lp-list-table profile-list-assignments profile-list-table">
		<thead>
		<tr>
			<th class="column-assignment"><?php _e( 'Assignment', 'learnpress-assignments' ); ?></th>
			<th class="column-mark"><?php _e( 'Mark', 'learnpress-assignments' ); ?></th>
			<th class="column-status"><?php _e( 'Status', 'learnpress-assignments' ); ?></th>
			<th class="column-time-interval"><?php _e( 'Interval', 'learnpress-assignments' ); ?></th>
		</tr>
		</thead>
		<tbody>
		<?php
		foreach ( $items as $user_assignment ) {
			$userAssigmentModel = new UserAssignmentModel( $user_assignment );
			$assignment         = AssignmentPostModel::find( $user_assignment->item_id, true );
			$course             = CourseModel::find( $user_assignment->ref_id, true );
			if ( ! $course ) {
				continue;
			}

			$mark   = learn_press_get_user_item_meta( $userAssigmentModel->get_user_item_id(), '_lp_assignment_mark', true );
			$status = $user_assignment->status;
			?>
			<tr>
				<td class="column-assignment">
					<a href="<?php echo $assignment->get_permalink(); ?>">
						<?php echo $assignment->get_the_title(); ?>
					</a>
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
				<td class="column-status">
					<?php echo empty( $status ) ? __( '--', 'learnpress-assignments' ) : LP_Addon_Assignment::get_i18n_value( $status ); ?>
				</td>
				<td class="column-time-interval">
					<?php echo esc_html( $userAssigmentModel->get_time_interval() ); ?>
				</td>
			</tr>
			<?php continue; ?>
			<tr>
				<td colspan="4"></td>
			</tr>
		<?php } ?>
		</tbody>
		<tfoot>
		<tr class="list-table-nav">
			<td colspan="2" class="nav-text"><?php echo $query->get_offset_text(); ?></td>
			<td colspan="2" class="nav-pages"><?php $query->get_nav_numbers( true ); ?></td>
		</tr>
		</tfoot>
	</table>

	<?php
} else {
	Template::print_message( __( 'No assignments!', 'learnpress-assignments' ), 'info' );
}

