<?php
/**
 * Quiz question retake dropdown.
 *
 * @var array $template_args Template arguments.
 */

defined( 'ABSPATH' ) || exit;

$retake_count = absint( $template_args['retake_count'] ?? 0 );
$retakes      = $template_args['retakes'] ?? array();
?>
<?php if ( 0 === $retake_count ) : ?>
	<div class="number_retake retake_number">
		<span class="count_retake retake-count">(0)</span>
	</div>
<?php else : ?>
	<div class="lp-gradebook-dropdown number_retake">
		<button class="lp-gradebook-dropbtn" type="button">
			<span class="retake-count">(<?php echo esc_html( $retake_count ); ?>)</span>
			<?php esc_html_e( 'View', 'learnpress-gradebook' ); ?>
			<span class="dashicons dashicons-arrow-down"></span>
		</button>
		<div class="lp-gradebook-dropdown-content lp-gradebook_retake">
			<?php foreach ( $retakes as $index => $result ) : ?>
				<div class="lp-gradebook-retake-detail lp-gradebook_retake_detail">
					<span><?php echo esc_html( (int) $index + 1 ); ?></span>
					<span><?php echo esc_html( $result ); ?></span>
				</div>
			<?php endforeach; ?>
		</div>
	</div>
<?php endif; ?>
