<?php

namespace FluentCampaign\App\Modules\SMS;

use FluentCampaign\App\Modules\SMS\Http\Controllers\SettingsController;
use FluentCampaign\App\Modules\SMS\Http\Policies\SMSPolicy;
use FluentCampaign\App\Modules\SMS\Providers\AWSDriver;
use FluentCampaign\App\Modules\SMS\Providers\TwilioDriver;
use FluentCrm\App\Services\PermissionManager;

class SMSModule
{

    public function init($app)
    {
        add_filter('init', function () use ($app) {
            $this->register($app);
        });
    }

    public function register($app)
    {
        // Register built-in drivers — constructor auto-registers via SMSDriverManager.
        // Runs before isActive() so the settings page always has driver options.
        new TwilioDriver();
        new AWSDriver();
        do_action('fluent_crm/register_sms_providers');

        if(!SMSHelper::isActive()) {
            return false;
        }

        /*
         * register the routes
         */
        $app->router->group(function ($router) {
            require_once __DIR__ . '/Http/sms_api.php';
        });

        add_filter('fluent_crm/admin_vars', function ($adminVars) {
            // $adminVars['available_sms_statuses'] = fluentcrm_subscriber_sms_statuses(true);
            $adminVars['sms_enabled'] = 'yes';
            return $adminVars;
        });


        // sidebar menu item
        add_action('fluent_crm/after_core_menu_items', function ($permissions, $isAdmin) {
            
            if (!in_array('fcrm_read_emails', $permissions)){
                return;
            }

            add_submenu_page(
                'fluentcrm-admin',
                __('SMS', 'fluentcampaign-pro'),
                __('SMS', 'fluentcampaign-pro'),
                ($isAdmin) ? 'manage_options' : 'fcrm_read_emails',
                'fluentcrm-admin#/sms/campaigns',
                '__return_null'
            );
        }, 9, 2);

        // top menu item
        add_filter('fluent_crm/core_menu_items', function ($items, $permissions, $urlBase = null) {
            
            if (!in_array('fcrm_read_emails', $permissions)){
                return $items;
            }

            if (!$urlBase) {
                $urlBase = fluentcrm_menu_url_base();
            }

            $smsCampaignMenu = [
                'key'       => 'sms',
                'label'     => __('SMS', 'fluentcampaign-pro'),
                'permalink' => $urlBase . 'sms/campaigns',
                'layout_class' => 'sms_menu',
            ];

            $smsCampaignMenu['sub_items'] = [
                [
                    'key'       => 'all_sms_campaigns',
                    'label'     => __('SMS Campaigns', 'fluentcampaign-pro'),
                    'permalink' => $urlBase . 'sms/campaigns',
                    'description' => __('Deliver SMS campaigns to specific subscribers based on tags, lists, or segments', 'fluentcampaign-pro'),
                    'icon'       => '<svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M8.125 13.125C8.125 13.125 12.5 13.75 14.375 15.625H15C15.3452 15.625 15.625 15.3452 15.625 15V11.2106C16.1641 11.0719 16.5625 10.5824 16.5625 10C16.5625 9.41756 16.1641 8.92812 15.625 8.78937V5C15.625 4.65482 15.3452 4.375 15 4.375H14.375C12.5 6.25 8.125 6.875 8.125 6.875H5.625C4.93464 6.875 4.375 7.43464 4.375 8.125V11.875C4.375 12.5654 4.93464 13.125 5.625 13.125H6.25L6.875 16.25H8.125V13.125ZM9.375 7.91325C9.80206 7.82162 10.3297 7.69496 10.8996 7.52733C11.9484 7.21884 13.2812 6.73289 14.375 5.98411V14.0159C13.2812 13.2671 11.9484 12.7812 10.8996 12.4727C10.3297 12.3051 9.80206 12.1784 9.375 12.0868V7.91325ZM5.625 8.125H8.125V11.875H5.625V8.125Z" fill="#525866"/>
                            </svg>'
                ],
                [
                    'key'       => 'all_sms',
                    'label'     => __('SMS Activities', 'fluentcampaign-pro'),
                    'permalink' => $urlBase . 'sms/all-sms',
                    'description' => __('View all SMS messages that have been sent or are scheduled to be sent by FluentCRM', 'fluentcampaign-pro'),
                    'icon'        => '<svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M5.84125 15.25L2.5 17.875V4C2.5 3.80109 2.57902 3.61032 2.71967 3.46967C2.86032 3.32902 3.05109 3.25 3.25 3.25H16.75C16.9489 3.25 17.1397 3.32902 17.2803 3.46967C17.421 3.61032 17.5 3.80109 17.5 4V14.5C17.5 14.6989 17.421 14.8897 17.2803 15.0303C17.1397 15.171 16.9489 15.25 16.75 15.25H5.84125ZM5.32225 13.75H16V4.75H4V14.7887L5.32225 13.75ZM9.25 8.5H10.75V10H9.25V8.5ZM6.25 8.5H7.75V10H6.25V8.5ZM12.25 8.5H13.75V10H12.25V8.5Z" fill="#525866"/>
                            </svg>'
                ],
            ];

            $items[] = $smsCampaignMenu;

            return $items;

        }, 10, 3);

        (new \FluentCampaign\App\Modules\SMS\SMSScheduler())->register();
        (new \FluentCampaign\App\Modules\SMS\SMSHandler())->register();

    }

}
