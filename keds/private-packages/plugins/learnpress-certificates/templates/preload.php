<?php
defined( 'ABSPATH' ) || exit;

if ( ! isset( $certificate ) ) {
	return;
}

$hide_actions = true;
?>

<div class="certificate-preload" hidden aria-hidden="true">
	<?php LP_Addon_Certificates_Preload::$addon->get_template( 'details.php', compact( 'certificate', 'hide_actions' ) ); ?>
</div>
