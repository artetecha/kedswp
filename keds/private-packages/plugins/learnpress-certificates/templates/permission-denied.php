<?php

defined( 'ABSPATH' ) || exit;

$title = __( 'You do not have permission to preview this certificate.', 'learnpress-certificates' );
$desc  = __( 'This certificate is not shared publicly. Only the owner, the course instructor, and site administrators can view it.', 'learnpress-certificates' );
?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" <?php language_attributes(); ?>>
<head>
	<meta name="viewport" content="width=device-width"/>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
	<meta name="robots" content="noindex,nofollow"/>
	<title><?php echo esc_html( $title ); ?></title>
	<?php do_action( 'wp_enqueue_scripts' ); ?>
	<?php wp_print_styles( 'certificates-css' ); ?>
</head>
<body>
<div class="lp-cert-permission-denied">
	<div class="lp-cert-permission-denied__inner">
		<h1 class="lp-cert-permission-denied__title"><?php echo esc_html( $title ); ?></h1>
		<p class="lp-cert-permission-denied__desc"><?php echo esc_html( $desc ); ?></p>
	</div>
</div>
</body>
</html>
