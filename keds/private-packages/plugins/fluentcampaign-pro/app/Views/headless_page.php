<?php if (!defined('ABSPATH')) exit; // Exit if accessed directly ?>
<?php
/**
 * @var $load_wp boolean
 * @var $title string
 * @var $url string
 * @var $featured_image string
 * @var $scope string
 */
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <title><?php echo esc_attr($title); ?></title>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width,initial-scale=1,maximum-scale=1,user-scalable=0,viewport-fit=cover,interactive-widget=resizes-content"/>
    <?php do_action('fluent_crm/headless/head_early', $scope); ?>
    <?php
    if (!empty($load_wp)) :
        wp_head();
    else: ?>
        <meta name="mobile-web-app-capable" content="yes">
        <meta name="robots" content="noindex">
        <link rel="icon" type="image/x-icon" href="<?php echo esc_url(get_site_icon_url()); ?>"/>
        <meta property="og:type" content="website">
        <meta property="og:url" content="<?php echo esc_url($url); ?>">
        <meta property="og:site_name" content="<?php bloginfo('name'); ?>">
        <?php if ($featured_image): ?>
            <meta property="og:image" content="<?php echo esc_url($featured_image); ?>"/>
        <?php endif; ?>
    <?php endif; ?>

    <style>
        .fluent_headless_page {
            display: block;
            width: 100%;
        }
    </style>

    <?php do_action('fluent_crm/headless/head', $scope); ?>
</head>
<body class="fluent_headless_page fluentcrm-frontend-portal-page fcrm_frontend_portal_scope">

<div class="fluent_layout">
    <?php do_action('fluent_crm/headless/content', $scope); ?>
</div>

<?php if (!empty($load_wp)) { wp_footer(); } ?>

<?php do_action('fluent_crm/headless/footer', $scope); ?>
</body>
</html>
