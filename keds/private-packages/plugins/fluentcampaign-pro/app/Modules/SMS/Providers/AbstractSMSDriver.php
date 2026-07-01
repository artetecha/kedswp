<?php

namespace FluentCampaign\App\Modules\SMS\Providers;

abstract class AbstractSMSDriver
{
    /**
     * Instantiating a driver automatically registers it with the manager.
     * Third-party usage: new MyCustomSMSDriver();
     */
    public function __construct()
    {
        SMSDriverManager::register($this);
    }

    /**
     * Unique slug used as the option key prefix and provider identifier.
     * e.g. 'twilio', 'aws_end_user_message', 'my_custom_sms'
     */
    abstract public function getSlug(): string;

    /**
     * Human-readable label shown in the Settings UI provider dropdown.
     */
    abstract public function getLabel(): string;

    /**
     * Settings fields for this provider.
     *
     * Return an associative array of field definitions, keyed by field slug.
     * Each definition supports: type (text|password|select|checkbox), label,
     * sub_label, required (bool), default, options (for select).
     *
     * Example:
     * [
     *   'api_key' => ['type' => 'password', 'label' => 'API Key', 'required' => true, 'default' => ''],
     * ]
     */
    abstract public function getFields(): array;

    /**
     * Send an SMS message.
     *
     * @param string $to       E.164 formatted phone number (e.g. +12125551234)
     * @param string $message  SMS body text
     * @param array  $settings Saved provider settings for this provider (from DB)
     *
     * @return array {
     *   status: 'success'|'error',
     *   status_code: int,
     *   message: string,
     *   response: mixed,
     *   provider_message_id?: string
     * }
     */
    abstract public function send(string $to, string $message, array $settings): array;

    /**
     * Whether this provider can receive incoming SMS via webhook.
     * When true, a webhook URL is auto-created when settings are saved.
     */
    public function hasWebhook(): bool
    {
        return false;
    }

    /**
     * Execute a cURL request and return a normalised response array.
     * Available to all providers as a convenience helper.
     *
     * @param array $options    cURL option constants → values
     * @param int   $successCode Expected HTTP success code
     * @return array
     */
    protected function executeCurl(array $options, int $successCode = 200): array
    {
        $ch = curl_init();
        curl_setopt_array($ch, $options);
        $response = curl_exec($ch);

        if (curl_errno($ch)) {
            $error = curl_error($ch);
            curl_close($ch);
            return [
                'status'      => 'error',
                'status_code' => 0,
                'message'     => 'CURL error: ' . $error,
                'response'    => null,
            ];
        }

        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== $successCode) {
            return [
                'status'      => 'error',
                'status_code' => $httpCode,
                'message'     => 'Unexpected HTTP response',
                'response'    => $response,
            ];
        }

        return [
            'status'      => 'success',
            'status_code' => $httpCode,
            'message'     => 'SMS sent successfully',
            'response'    => $response,
        ];
    }
}
