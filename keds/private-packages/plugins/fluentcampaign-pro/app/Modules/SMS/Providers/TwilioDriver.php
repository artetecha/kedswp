<?php

namespace FluentCampaign\App\Modules\SMS\Providers;

class TwilioDriver extends AbstractSMSDriver
{
    public function getSlug(): string
    {
        return 'twilio';
    }

    public function getLabel(): string
    {
        return __('Twilio', 'fluentcampaign-pro');
    }

    public function hasWebhook(): bool
    {
        return true;
    }

    public function getFields(): array
    {
        return [
            'account_sid' => [
                'type'      => 'password',
                'label'     => __('Twilio Account SID', 'fluentcampaign-pro'),
                'sub_label' => __('Please provide Twilio Account SID', 'fluentcampaign-pro'),
                'required'  => true,
                'default'   => '',
            ],
            'auth_token'  => [
                'type'      => 'password',
                'label'     => __('Twilio Auth Token', 'fluentcampaign-pro'),
                'sub_label' => __('Please provide Twilio Auth Token', 'fluentcampaign-pro'),
                'required'  => true,
                'default'   => '',
            ],
            'from_number' => [
                'type'      => 'text',
                'label'     => __('Twilio From Number', 'fluentcampaign-pro'),
                'sub_label' => __('Please provide Twilio From Number', 'fluentcampaign-pro'),
                'required'  => true,
                'default'   => '',
            ],
        ];
    }

    public function send(string $to, string $message, array $settings): array
    {
        $accountSid = $settings['account_sid'] ?? '';
        $authToken  = $settings['auth_token'] ?? '';
        $fromNumber = $settings['from_number'] ?? '';

        if (empty($accountSid) || empty($authToken) || empty($fromNumber)) {
            return [
                'status'      => 'error',
                'status_code' => 0,
                'message'     => 'Missing Twilio credentials',
                'response'    => null,
            ];
        }

        $url     = "https://api.twilio.com/2010-04-01/Accounts/{$accountSid}/Messages.json";
        $options = [
            CURLOPT_URL            => $url,
            CURLOPT_POST           => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_USERPWD        => "{$accountSid}:{$authToken}",
            CURLOPT_POSTFIELDS     => http_build_query(['To' => $to, 'From' => $fromNumber, 'Body' => $message]),
            CURLOPT_HTTPHEADER     => ['Content-Type: application/x-www-form-urlencoded'],
        ];

        $result = $this->executeCurl($options, 201);

        if (($result['status'] ?? '') !== 'success') {
            return $result;
        }

        // Twilio returns the outbound message SID in the JSON response body.
        // Pass it back using the shared provider_message_id key so the scheduler
        // can persist it on fc_sms_messages.provider_message_id.
        $decodedResponse = json_decode($result['response'], true);

        if (json_last_error() === JSON_ERROR_NONE && is_array($decodedResponse)) {
            $result['response'] = $decodedResponse;

            if (!empty($decodedResponse['sid'])) {
                $result['provider_message_id'] = $decodedResponse['sid'];
            }
        }

        return $result;
    }
}
