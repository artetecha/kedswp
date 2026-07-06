<?php
/**
 * Template for displaying download button.
 *
 * This template can be overridden by copying it to yourtheme/learnpress/addons/certificates/buttons.php.
 *
 * @author  ThimPress
 * @package LearnPress/Templates/Certificates
 * @version 3.0.0
 */

defined( 'ABSPATH' ) || exit;

if ( ! isset( $certificate ) ) {
	return;
}
?>

<?php
/*
Legacy actions markup:
<ul class="certificate-actions">
	<li class="download" data-type-download="[download-type]">
		<a href="javascript:void(0)" class="social-download-svg" data-cert="[certificate-uni-id]">
			<img src="[download-icon]" alt="download-certificate">
		</a>
	</li>
</ul>
*/

$socials_before_download = array();
$socials_after_download  = array();

if ( isset( $socials ) && $socials ) {
	foreach ( $socials as $social ) {
		if ( false !== strpos( $social, 'social-share-link' ) ) {
			$socials_after_download[] = $social;
		} else {
			$socials_before_download[] = $social;
		}
	}
}
?>
<ul class="certificate-actions certificate-actions--modern">
	<?php
	/*
	if ( isset( $socials ) && $socials ) {
		foreach ( $socials as $social ) {
			?>
			<li class="share-social-cert">
				<?php echo $social; ?>
			</li>
			<?php
		}
	}
	*/
	if ( $socials_before_download ) {
		foreach ( $socials_before_download as $social ) {
			?>
			<li class="share-social-cert">
				<?php echo $social; ?>
			</li>
			<?php
		}
	}
	?>

	<li class="download" data-type-download="<?php echo get_option( 'learn_press_lp_cer_down_type', 'image' ); ?>">
		<a href="javascript:void(0)" class="social-download-svg certificate-action__button" data-cert="<?php echo $certificate->get_uni_id(); ?>" aria-label="<?php echo esc_attr__( 'Download', 'learnpress-certificates' ); ?>">
			<img src="<?php echo LP_Addon_Certificates_Preload::$addon->get_plugin_url( 'assets/images/download.svg' ); ?>"
			alt="download-certificate">
			<span class="certificate-action__label"><?php echo esc_html__( 'Download', 'learnpress-certificates' ); ?></span>
		</a>
	</li>

	<?php
	if ( $socials_after_download ) {
		foreach ( $socials_after_download as $social ) {
			?>
			<li class="share-social-cert">
				<?php echo $social; ?>
			</li>
			<?php
		}
	}
	?>
</ul>
