<?php
/**
 * Template for displaying detail item in profile page.
 *
 * This template can be overridden by copying it to yourtheme/learnpress/addons/certificates/profile/item-certificate.php.
 *
 * @author  ThimPress
 * @package LearnPress/Certificates
 * @version 4.0.2
 */

defined( 'ABSPATH' ) || exit;

if ( empty( $item ) || empty( $course ) || empty( $cert_id ) ) {
	return;
}
?>

<li class="certificate-item">
	<?php
	/*
	Legacy profile item flow:
	$user_certificate = new LP_User_Certificate( $item['user_id'], $item['course_id'], $cert_id );
	$template_id      = uniqid( $user_certificate->get_uni_id() );
	$link_cert        = $user_certificate->get_permalink();

	<div id="[template_id]" class="certificate-preview" data-key="[cert-key]">
		<div class="certificate-preview-inner">
			<canvas></canvas>
		</div>
		<input class="lp-data-config-cer" type="hidden" value="[certificate-json]">
	</div>
	*/
	$user_certificate = new LP_User_Certificate( $item['user_id'], $item['course_id'], $cert_id );
	$template_id      = uniqid( $user_certificate->get_uni_id() );
	$link_cert        = $user_certificate->get_permalink();
	$image_file       = $user_certificate->get_image_file();
	$cert_key         = LP_Certificate::get_cert_key( $item['user_id'], $item['course_id'], $cert_id, false );

	$share_enabled = lp_cert_share_link_enabled();
	$is_owner      = get_current_user_id() && (int) get_current_user_id() === (int) $item['user_id'];
	$show_share    = $share_enabled && $is_owner;
	$is_shared     = $user_certificate->is_shared();
	?>

	<a href="<?php echo esc_url( $link_cert ); ?>" class="course-permalink">
		<div class="certificate-thumbnail">
			<div id="<?php echo esc_attr( $template_id ); ?>" class="certificate-preview<?php echo $image_file ? ' has-cached-image' : ''; ?>"
				data-key ="<?php echo esc_attr( $cert_key ); ?>">
				<?php if ( $image_file ) : ?>
					<img class="certificate-result" src="<?php echo esc_url( $image_file['proxy_url'] ); ?>" alt="<?php echo esc_attr( $course->get_title() ); ?>" />
				<?php else : ?>
					<div class="certificate-preview-inner">
						<canvas></canvas>
					</div>
					<input type="hidden" name="need_upload_cert_img_to_server">
				<?php endif; ?>

				<input class="lp-data-config-cer" type="hidden" value="<?php echo htmlspecialchars( $user_certificate ); ?>">
			</div>
		</div>
	</a>

	<div class="lp-cert-title-row<?php echo $show_share ? ' has-share' : ''; ?>">
		<h4 class="course-title">
			<a href="<?php echo esc_url( $course->get_permalink() ); ?>"><?php echo esc_html( $course->get_title() ); ?></a>
		</h4>

		<?php if ( $show_share ) : ?>
			<div class="lp-cert-share" data-cert-key="<?php echo esc_attr( $cert_key ); ?>">
				<?php
				echo lp_cert_render_toggle( array(
					'id'               => 'lp-cert-share-' . $cert_key,
					'checked'          => $is_shared,
					'attrs'            => array( 'class' => 'lp-cert-share__toggle' ),
					'input_attrs'      => array( 'class' => 'lp-cert-share__input' ),
					'label_text'       => $is_shared ? esc_html__( 'Public', 'learnpress-certificates' ) : esc_html__( 'Private', 'learnpress-certificates' ),
					'label_text_class' => 'lp-cert-share__label',
				) );
				?>
			</div>
		<?php endif; ?>
	</div>
</li>
