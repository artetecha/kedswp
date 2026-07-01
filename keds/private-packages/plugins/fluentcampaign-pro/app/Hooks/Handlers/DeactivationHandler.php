<?php

namespace FluentCampaign\App\Hooks\Handlers;

class DeactivationHandler
{
    public function handle()
    {
        wp_clear_scheduled_hook('fluentcrm_check_daily_birthday');
        wp_clear_scheduled_hook('fluentcrm_check_daily_birthday_once');
    }
}
