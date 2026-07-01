<?php
/**
 * Template for displaying list reviews, rating tab of single course.
 *
 * @author  ThimPress
 * @package  Learnpress/Templates
 * @version  4.0.2
 */

_deprecated_file( __FILE__, '4.2.0' );
return;

defined( 'ABSPATH' ) || exit();

if ( empty( $data ) ) {
	return;
}
?>

<div class="lp-rating-reviews">
	<?php
	do_action( 'learn-press/course-review/list-rating-reviews', $data );
	?>
</div>
