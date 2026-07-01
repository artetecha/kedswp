<?php

namespace FluentCampaign\App\Hooks\Handlers;

use FluentCampaign\App\Migration\Migrate;

class ActivationHandler
{
    public function handle($network_wide = false)
    {
        Migrate::run($network_wide);
    }
}
