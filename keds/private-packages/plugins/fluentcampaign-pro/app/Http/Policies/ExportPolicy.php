<?php

namespace FluentCampaign\App\Http\Policies;

use FluentCrm\App\Http\Policies\BasePolicy;
use FluentCrm\Framework\Http\Request\Request;

class ExportPolicy extends BasePolicy
{
    /**
     * Check user permission for any method
     * @param Request $request
     * @return Boolean
     */
    public function verifyRequest(Request $request)
    {
        return $this->currentUserCan('fcrm_manage_contacts_export');
    }
}
