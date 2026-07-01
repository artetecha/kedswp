<?php

namespace FluentCampaign\App\Modules\SMS;

use FluentCampaign\App\Modules\SMS\Providers\SMSDriverManager;

class SMSSender
{
    public static function send($to, $message, $provider = null)
    {
        if (!SMSHelper::isActive()) {
            return false;
        }

        if (!$provider) {
            $provider = SMSHelper::getCurrentProvider();
        }

        $settings         = SMSHelper::getProviderSettings($provider);
        $providerInstance = SMSDriverManager::getDriver($provider);

        if ($providerInstance) {
            return $providerInstance->send($to, $message, $settings);
        }

        return [
            'status'      => 'error',
            'status_code' => 0,
            'message'     => 'SMS provider not registered: ' . $provider,
            'response'    => null,
        ];
    }

}
