<?php
/**
 * The template for display the content of single course
 *
 * @author  ThimPress
 * @package LearnPress/Templates
 * @version 3.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

$course = learn_press_get_course();
if ( ! $course ) {
	return;
}

//$is_required = $course->is_required_enroll();
//$user        = LearnPress::instance()->user;
//$is_enrolled = $user->has( 'enrolled-course', $course->id );

do_action( 'learn_press_before_single_course' );
?>

<article id="post-<?php the_ID(); ?>" <?php post_class( 'learn-press' ); ?> itemscope
		itemtype="https://schema.org/CreativeWork">

	<?php do_action( 'learn_press_before_single_course_summary' ); ?>

	<?php
	the_title( '<h1 class="entry-title" itemprop="name">', '</h1>' );
	?>

	<div class="course-meta course-meta-single">
		<?php learn_press_course_instructor(); ?>
		<?php //learn_press_course_categories(); ?>
	</div>

	<div class="course-payment thim-enroll-kid-art">
		<div class="course-price" itemprop="offers" itemscope itemtype="https://schema.org/Offer">
			<?php echo wp_kses_post( $course->get_course_price_html() ); ?>
		</div>
		<a class="thim-enroll-course-button" href="#"><?php esc_html_e( 'Register', 'eduma-child-kid-art' ); ?></a>
	</div>

	<?php learn_press_get_template( 'single-course/thumbnail.php' ); ?>

	<div class="course-summary">
		<?php learn_press_get_template( 'single-course/content-landing.php' ); ?>
	</div>

	<?php do_action( 'learn_press_after_single_course_summary' ); ?>

	<?php do_action( 'learn_press_after_single_course_summary' ); ?>

</article><!-- #post-## -->

<?php thim_related_courses(); ?>
<?php do_action( 'learn_press_after_single_course' ); ?>
