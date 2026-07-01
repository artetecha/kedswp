<?php

namespace FluentCampaign\App\Modules\SMS\Http\Controllers;

use FluentCampaign\App\Http\Controllers\Controller;
use FluentCampaign\App\Modules\SMS\SMSHelper;
use FluentCampaign\App\Modules\SMS\SMSMigrator;
use FluentCrm\App\Services\Helper;
use FluentCrm\Framework\Http\Request\Request;
use FluentCrm\Framework\Support\Arr;

class SettingsController extends Controller
{
    public function getSettings()
    {
        $settings = SMSHelper::getSettings();
        $options = SMSHelper::getApiOptions();
        $smsWebhook = null;

        if (!empty($settings['sms_provider']) && Arr::get($settings, 'enabled') === 'yes') {
            $smsWebhook = $this->getSMSWebhook($settings['sms_provider']);
        }

        return [
            'settings'    => $settings,
            'options'     => $options,
            'sms_webhook' => $smsWebhook,
        ];
    }

    public function saveSettings(Request $request)
    {
        $prevSettings = SMSHelper::getSettings();

        $settings = $request->get('settings', []);
        $smsProvider = trim((string) Arr::get($settings, 'sms_provider', ''));
        $isEnabled = Arr::get($settings, 'enabled') === 'yes';

        $providerApiOptions = SMSHelper::getApiOptions();
        $providerDefinitions = Arr::get($providerApiOptions, 'providers', []);

        if ($isEnabled) {
            if (!$smsProvider || !isset($providerDefinitions[$smsProvider])) {
                return $this->sendError([
                    'message' => __('Please select an SMS provider before enabling the SMS module', 'fluentcampaign-pro')
                ]);
            }

            $providerFields = Arr::get($providerDefinitions, $smsProvider . '.fields', []);
            $providerSettings = Arr::get($settings, $smsProvider, []);
            if (!is_array($providerSettings)) {
                $providerSettings = [];
            }

            $missingRequiredFields = [];
            foreach ($providerFields as $fieldKey => $field) {
                if (empty($field['required'])) {
                    continue;
                }

                $value = Arr::get($providerSettings, $fieldKey);
                $isMissing = $value === null || $value === '';

                if (is_string($value)) {
                    $isMissing = trim($value) === '';
                } elseif (is_array($value)) {
                    $filledValues = array_filter($value, function ($item) {
                        if (is_string($item)) {
                            return trim($item) !== '';
                        }
                        return $item !== null && $item !== '';
                    });
                    $isMissing = empty($filledValues);
                }

                if ($isMissing) {
                    $missingRequiredFields[] = Arr::get($field, 'label', $fieldKey);
                }
            }

            if ($missingRequiredFields) {
                return $this->sendError([
                    'message' => __('Please provide all required SMS provider credentials before enabling the SMS module', 'fluentcampaign-pro'),
                    'required_fields' => $missingRequiredFields
                ]);
            }

            SMSMigrator::migrate();
        }

        /*
         * Adding this to experimental settings so we don't have to do extra query
        */
        $experiments = Helper::getExperimentalSettings();
        $experiments['sms_module'] = $isEnabled ? 'yes' : 'no';
        update_option('_fluentcrm_experimental_settings', $experiments, 'yes');

        $enabledValue = $isEnabled ? 'yes' : 'no';
        fluentcrm_update_option('_fc_sms_enable', $enabledValue);
        fluentcrm_update_option('_fc_sms_provider', $smsProvider);

        foreach ($providerDefinitions as $providerName => $options) {
            $providerSettings = Arr::get($settings, $providerName, []);
            if (!is_array($providerSettings)) {
                $providerSettings = [];
            }

            $toSave = [];
            $fields = Arr::get($options, 'fields', []);
            foreach ($fields as $fieldKey => $field) {
                $defaultValue = Arr::get($field, 'default', '');
                $value = Arr::get($providerSettings, $fieldKey, $defaultValue);

                if (is_string($value)) {
                    $value = trim($value);
                } elseif (is_scalar($value)) {
                    $value = (string) $value;
                }

                $toSave[$fieldKey] = $value;
            }

            fluentcrm_update_option('_fc_sms_' . $providerName . '_settings', $toSave);
        }

        if ((string) fluentcrm_get_option('_fc_sms_enable', 'no') !== $enabledValue) {
            return $this->sendError([
                'message' => __('SMS settings could not be saved completely. Please try again.', 'fluentcampaign-pro')
            ], 500);
        }

        $smsWebhook = null;
        if ($isEnabled && $smsProvider) {
            $smsWebhook = $this->getSMSWebhook($smsProvider, true);
        }

        return [
            'message'     => __('Settings has been saved successfully', 'fluentcampaign-pro'),
            'reload'      => Arr::get($prevSettings, 'enabled') !== $enabledValue,
            'sms_webhook' => $smsWebhook,
        ];
    }

    private function getSMSWebhook($provider, $createKey = false)
    {
        $webhookUrl = SMSHelper::getSMSWebhookUrl($provider, $createKey);
        if (!$webhookUrl) {
            return null;
        }

        $providerLabel = ucwords(str_replace('_', ' ', $provider));
        $providerOptions = Arr::get(SMSHelper::getApiOptions(), 'providers.' . $provider, []);

        return [
            'object_type' => 'webhook_' . $provider,
            'value'       => [
                'name'                => sprintf(__('%s SMS Webhook', 'fluentcampaign-pro'), Arr::get($providerOptions, 'label', $providerLabel)),
                'sms_provider'        => $provider,
                'sms_webhook_enabled' => true,
                'type'                => 'sms_webhook',
                'url'                 => $webhookUrl,
            ],
        ];
    }

    public function disable()
    {
        $prevSettings = SMSHelper::getSettings();

        fluentcrm_update_option('_fc_sms_enable', 'no');

        $experiments = Helper::getExperimentalSettings();
        $experiments['sms_module'] = 'no';
        update_option('_fluentcrm_experimental_settings', $experiments, 'yes');

        return [
            'message' => __('SMS has been disabled successfully', 'fluentcampaign-pro'),
            'reload' => $prevSettings['enabled'] !== 'no'
        ];
    }
}
