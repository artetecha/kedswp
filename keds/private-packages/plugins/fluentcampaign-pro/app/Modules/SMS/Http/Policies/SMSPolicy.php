<?php

namespace FluentCampaign\App\Modules\SMS\Http\Policies;

use FluentCampaign\App\Modules\SMS\SMSHelper;
use FluentCrm\App\Http\Policies\BasePolicy;
use FluentCrm\Framework\Http\Request\Request;

class SMSPolicy extends BasePolicy
{
    /**
     * Check user permission for any method
     * @param  \FluentCrm\Framework\Http\Request\Request $request
     * @return bool
     */
    public function verifyRequest(Request $request)
    {
        if (!SMSHelper::isActive()) {
            return false;
        }

        if ($request->method() == 'GET') {
            return $this->currentUserCan('fcrm_read_emails');
        }

        return $this->currentUserCan('fcrm_read_emails');
    }

    public function delete(Request $request)
    {
        return $this->currentUserCan('fcrm_manage_email_delete');
    }

}
