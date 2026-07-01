<?php
/**
 * Template list course checkout
 *
 * @author  VuxMinhThanh
 * @package LearnPress/Templates
 * @version 1.0.0
 */

use LearnPress\Models\CourseModel;

defined( 'ABSPATH' ) || exit();

if ( ! isset( $courses_assign_product ) || ! isset( $all_courses_out_of_stock ) || ! isset( $product ) ) {
	return;
}

/**
 * @var CourseModel[] $courses_assign_product
 */
?>
<div class="lp-course-sold-out">

	<p class="lp-course-sold-out__title">
		<?php
		printf( '%s - %s', esc_html( $product->get_name() ), __( 'list courses', 'learnpress-woo-payment' ) );
		?>
	</p>
	<ul class="lp-course-sold-out__list">
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
			<li class="lp-course-sold-out__item">
				<a href="<?php echo esc_url_raw( $course->get_permalink() ) ?>">
					<?php echo wp_kses_post( $course->post_title . ' ' . $message ) ?>
				</a>
			</li>
			<?php
		}
		?>
	</ul>
</div>


