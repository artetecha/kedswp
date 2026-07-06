<?php
/**
 * List menus for certificate builder.
 *
 * @version 4.2.0
 */

$menus = [
	'elements'    => [
		'icon'   => 'lp-cert-icon-element',
		'label'  => esc_html__( 'Elements', 'learnpress-certificates' ),
		'active' => true,
	],
	'uploads'     => [
		'icon'  => 'lp-cert-icon-uploads',
		'label' => esc_html__( 'Uploads', 'learnpress-certificates' ),
	],
	'library'     => [
		'icon'  => 'lp-cert-icon-library',
		'label' => esc_html__( 'Library', 'learnpress-certificates' ),
	],
	'backgrounds' => [
		'icon'  => 'lp-cert-icon-background',
		'label' => esc_html__( 'Background', 'learnpress-certificates' ),
	],
	'templates'   => [
		'icon'  => 'lp-cert-icon-templates',
		'label' => esc_html__( 'Templates', 'learnpress-certificates' ),
	],
	'layers'      => [
		'icon'  => 'lp-cert-icon-layer',
		'label' => esc_html__( 'Layers', 'learnpress-certificates' ),
	],
];

return apply_filters(
	'learn-press/certificate/builder/menus',
	$menus
);
