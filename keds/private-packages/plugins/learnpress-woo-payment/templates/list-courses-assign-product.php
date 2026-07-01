<?php
/**
 * Template list courses assign to product
 *
 * @package LearnPress/Templates
 * @version 1.0.0
 */

use LearnPress\Helpers\Template;
use LearnPress\Models\CourseModel;

defined( 'ABSPATH' ) || exit();

if ( ! isset( $courses_assign_product ) || ! isset( $all_courses_out_of_stock ) ) {
	return;
}

if ( $all_courses_out_of_stock ) {
	printf(
		'<span style="font-style: italic; color: darkred">%s</span>',
		__( 'Courses are full students, so this product is out of stock.', 'learnpress-woo-payment' )
	);
}
?>
<ul class="list-courses-assign-product">
	<?php
	/**
	 * @var CourseModel[] $courses_assign_product
	 */
	foreach ( $courses_assign_product as $course ) {
		$message = '';
		if ( isset( $course->meta_data->is_out_stock ) ) {
			$message = sprintf(
				'<span style="font-style: italic; color: darkred">(%s)</span>',
				$course->meta_data->is_out_stock );
		}
		?>
        <li>
            <a href="<?php echo esc_url_raw( $course->get_permalink() ); ?>">
				<?php echo wp_kses_post( $course->post_title . ' ' . $message ) ?>
            </a>
        </li>
		<?php
	}
	?>
</ul>




