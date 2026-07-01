<?php

namespace FluentCampaign\App\Modules\SMS\Examples;

use FluentCampaign\App\Modules\SMS\Providers\AbstractSMSDriver;

/**
 * Example / Demo Custom SMS Driver
 *
 * This file shows how a third-party plugin can add its own SMS driver to FluentCRM.
 *
 * USAGE — in your plugin's bootstrap (after 'plugins_loaded'):
 *
 *   add_action('fluent_crm/register_sms_providers', function () {
 *       new \YourPlugin\YourSMSDriver();
 *   });
 *
 * That's it. The constructor handles registration automatically.
 *
 * -----------------------------------------------------------------------
 * This demo sends via a fictional HTTP API so you can verify the full flow:
 *   settings page shows the driver → saves credentials → sends a real cURL
 * -----------------------------------------------------------------------
 */
class CustomSMSDriverExample extends AbstractSMSDriver
{
    public function getSlug(): string
    {
        return 'custom_demo';
    }

    public function getLabel(): string
    {
        return __('Custom Demo Provider', 'fluentcampaign-pro');
    }

    public function hasWebhook(): bool
    {
        return false; // set true if your provider pushes delivery/reply webhooks
    }

    public function getFields(): array
    {
        return [
            'api_key'      => [
                'type'      => 'password',
                'label'     => __('API Key', 'fluentcampaign-pro'),
                'sub_label' => __('Your provider API key', 'fluentcampaign-pro'),
                'required'  => true,
                'default'   => '',
            ],
            'sender_id'    => [
                'type'      => 'text',
                'label'     => __('Sender ID', 'fluentcampaign-pro'),
                'sub_label' => __('The number or name shown as sender', 'fluentcampaign-pro'),
                'required'  => true,
                'default'   => '',
            ],
            'gateway_url'  => [
                'type'      => 'text',
                'label'     => __('Gateway URL', 'fluentcampaign-pro'),
                'sub_label' => __('Your provider\'s SMS send endpoint', 'fluentcampaign-pro'),
                'required'  => true,
                'default'   => 'https://api.example.com/sms/send',
            ],
        ];
    }

    public function send(string $to, string $message, array $settings): array
    {
        $apiKey     = $settings['api_key'] ?? '';
        $senderId   = $settings['sender_id'] ?? '';
        $gatewayUrl = $settings['gateway_url'] ?? '';

        if (empty($apiKey) || empty($senderId) || empty($gatewayUrl)) {
            return [
                'status'      => 'error',
                'status_code' => 0,
                'message'     => 'Missing Custom Demo Provider credentials',
                'response'    => null,
            ];
        }

        $options = [
            CURLOPT_URL            => $gatewayUrl,
            CURLOPT_POST           => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => [
                'Authorization: Bearer ' . $apiKey,
                'Content-Type: application/json',
            ],
            CURLOPT_POSTFIELDS     => json_encode([
                'to'        => $to,
                'from'      => $senderId,
                'message'   => $message,
            ]),
        ];

        return $this->executeCurl($options, 200);
    }
}
