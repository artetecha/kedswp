<?php defined('ABSPATH') or die;

/*
Plugin Name: FluentCRM Pro
Description: Pro Email Automation and Integration Addon for FluentCRM
Version: 3.1.0
Author: Fluent CRM
Author URI: https://fluentcrm.com
Plugin URI: https://fluentcrm.com
License: GPLv2 or later
Text Domain: fluentcampaign-pro
Domain Path: /language
*/

define('FLUENTCAMPAIGN_DIR_FILE', __FILE__);
define('FLUENTCAMPAIGN_PLUGIN_PATH', plugin_dir_path(__FILE__));

define('FLUENTCAMPAIGN', 'fluentcampaign');
define('FLUENTCAMPAIGN_PLUGIN_URL', plugin_dir_url(__FILE__));
define('FLUENTCAMPAIGN_PLUGIN_VERSION', '3.1.0');
define('FLUENTCAMPAIGN_CORE_MIN_VERSION', '3.1.0');
define('FLUENTCAMPAIGN_FRAMEWORK_VERSION', 3);

require __DIR__ . '/vendor/autoload.php';

call_user_func(function ($bootstrap) {
    $bootstrap(__FILE__);
}, require(__DIR__ . '/boot/app.php'));
