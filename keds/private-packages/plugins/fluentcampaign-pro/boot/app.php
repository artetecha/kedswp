<?php

use FluentCampaign\App\Core\Application;
use FluentCampaign\App\Hooks\Handlers\ActivationHandler;
use FluentCampaign\App\Hooks\Handlers\DeactivationHandler;

return function ($file) {

    register_activation_hook($file, function ($network_wide) {
        (new ActivationHandler)->handle($network_wide);
    });

    add_action('wp_insert_site', function ($new_site) {
        if (function_exists('is_plugin_active_for_network') && is_plugin_active_for_network('fluentcampaign-pro/fluentcampaign-pro.php')) {
            switch_to_blog($new_site->blog_id);
            (new ActivationHandler)->handle(false);
            restore_current_blog();
        }
    });

    register_deactivation_hook($file, function () {
        (new DeactivationHandler)->handle();
    });

    add_action('fluentcrm_loaded', function ($app) use ($file) {

        if (defined('FLUENTCRM_CORE_FRAMEWORK_VERSION') && FLUENTCRM_CORE_FRAMEWORK_VERSION == 3) {
            new Application($app, $file);
            (new \FluentCampaign\App\Modules\SMS\SMSModule())->init($app);

            $licenseManager = (new \FluentCampaign\App\Services\PluginManager\FluentLicensing())->register([
                'version'           => FLUENTCAMPAIGN_PLUGIN_VERSION, // Current version of your plugin
                'item_id'           => 7560867, // Product ID from FluentCart
                'settings_key'      => '__fluentcrm_campaign_license',
                'basename'          => 'fluentcampaign-pro/fluentcampaign-pro.php', // Plugin basename (e.g., 'your-plugin/your-plugin.php')
                'api_url'           => 'https://fluentapi.wpmanageninja.com/', // The API URL for license verification. Normally your store URL
                'store_url'         => 'https://wpmanageninja.com/', // Your store URL
                'purchase_url'      => 'https://fluentcrm.com/', // Purchase URL
                'activate_url'      => admin_url('admin.php?page=fluentcrm-admin#/settings/license_management'),
                'show_check_update' => true,
                'plugin_title'      => 'FluentCRM Pro',
            ]);

            $licenseMessage = $licenseManager->getLicenseMessages();

            if ($licenseMessage) {
                add_action('admin_notices', function () use ($licenseMessage) {
                    $class = 'notice fcrm_notice';
                    $message = $licenseMessage['message'];
                    printf('<div class="%1$s">%2$s</div>', esc_attr($class), wp_kses_post($message));
                });

                add_filter('fluent_crm/dashboard_notices', function ($notices) use ($licenseMessage) {
                    if (!empty($licenseMessage['message'])) {
                        $notices[] = '<div>' . $licenseMessage['message'] . '</div>';
                    }

                    return $notices;
                });
            }
        } else {
            // This is the old version. The user did not updated the core version to the latest
            // Please show a notice to the user to update the core version to the latest
            add_action('admin_notices', function () {
                echo '<div class="notice notice-error"><h3 style="margin: 10px 0 0;">FluentCRM Pro is not running</h3><p>';
                echo sprintf(
                    esc_html__('FluentCampaign Pro requires FluentCRM version %s or higher. Please update FluentCRM to the latest version to use FluentCRM Pro.', 'fluent-crm'),
                    FLUENTCRM_MIN_PRO_VERSION
                );
                echo '</p></div>';
            });
        }
    });
};
