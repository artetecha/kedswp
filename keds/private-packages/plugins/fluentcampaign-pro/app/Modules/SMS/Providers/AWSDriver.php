<?php

namespace FluentCampaign\App\Modules\SMS\Providers;

class AWSDriver extends AbstractSMSDriver
{
    public function getSlug(): string
    {
        return 'aws_end_user_message';
    }

    public function getLabel(): string
    {
        return __('Amazon End User Messaging', 'fluentcampaign-pro');
    }

    public function hasWebhook(): bool
    {
        return true;
    }

    public function getFields(): array
    {
        return [
            'access_key_id'     => [
                'type'      => 'text',
                'label'     => __('AWS Access Key ID', 'fluentcampaign-pro'),
                'sub_label' => __('Please provide AWS Access Key ID', 'fluentcampaign-pro'),
                'required'  => true,
                'default'   => '',
            ],
            'secret_access_key' => [
                'type'      => 'password',
                'label'     => __('AWS Secret Access Key', 'fluentcampaign-pro'),
                'sub_label' => __('Please provide AWS Secret Access Key', 'fluentcampaign-pro'),
                'required'  => true,
                'default'   => '',
            ],
            'region'            => [
                'type'      => 'select',
                'label'     => __('AWS Region', 'fluentcampaign-pro'),
                'sub_label' => __('Please choose your AWS Region', 'fluentcampaign-pro'),
                'required'  => true,
                'default'   => 'us-east-1',
                'options'   => [
                    'us-east-1'      => __('US East (N. Virginia)', 'fluentcampaign-pro'),
                    'us-east-2'      => __('US East (Ohio)', 'fluentcampaign-pro'),
                    'us-west-1'      => __('US West (N. California)', 'fluentcampaign-pro'),
                    'us-west-2'      => __('US West (Oregon)', 'fluentcampaign-pro'),
                    'ca-central-1'   => __('Canada (Central)', 'fluentcampaign-pro'),
                    'eu-central-1'   => __('Europe (Frankfurt)', 'fluentcampaign-pro'),
                    'eu-west-1'      => __('Europe (Ireland)', 'fluentcampaign-pro'),
                    'eu-west-2'      => __('Europe (London)', 'fluentcampaign-pro'),
                    'eu-west-3'      => __('Europe (Paris)', 'fluentcampaign-pro'),
                    'eu-north-1'     => __('Europe (Stockholm)', 'fluentcampaign-pro'),
                    'eu-south-1'     => __('Europe (Milan)', 'fluentcampaign-pro'),
                    'ap-northeast-1' => __('Asia Pacific (Tokyo)', 'fluentcampaign-pro'),
                    'ap-northeast-2' => __('Asia Pacific (Seoul)', 'fluentcampaign-pro'),
                    'ap-northeast-3' => __('Asia Pacific (Osaka)', 'fluentcampaign-pro'),
                    'ap-southeast-1' => __('Asia Pacific (Singapore)', 'fluentcampaign-pro'),
                    'ap-southeast-2' => __('Asia Pacific (Sydney)', 'fluentcampaign-pro'),
                    'ap-south-1'     => __('Asia Pacific (Mumbai)', 'fluentcampaign-pro'),
                    'ap-east-1'      => __('Asia Pacific (Hong Kong)', 'fluentcampaign-pro'),
                    'sa-east-1'      => __('South America (São Paulo)', 'fluentcampaign-pro'),
                    'me-south-1'     => __('Middle East (Bahrain)', 'fluentcampaign-pro'),
                    'af-south-1'     => __('Africa (Cape Town)', 'fluentcampaign-pro'),
                ],
            ],
            'sender_id'         => [
                'type'     => 'text',
                'label'    => __('Sender ID (Optional)', 'fluentcampaign-pro'),
                'required' => false,
                'default'  => '',
            ],
        ];
    }

    public function send(string $to, string $message, array $settings): array
    {
        $accessKeyId     = trim((string) ($settings['access_key_id'] ?? ''));
        $secretAccessKey = trim((string) ($settings['secret_access_key'] ?? ''));

        if (empty($accessKeyId) || empty($secretAccessKey)) {
            return [
                'status'      => 'error',
                'status_code' => 0,
                'message'     => 'Missing AWS credentials',
                'response'    => null,
            ];
        }

        $res = AWS::sendSMS($to, $message, [
            'access_key_id'        => $accessKeyId,
            'secret_access_key'    => $secretAccessKey,
            'region'               => $settings['region'] ?? 'us-east-1',
            'origination_identity' => $settings['sender_id'] ?? '',
        ]);

        $statusCode        = (int) ($res['status'] ?? 0);
        $providerMessageId = $res['body']['MessageId'] ?? '';

        if (empty($res['error']) && $statusCode >= 200 && $statusCode < 300) {
            return [
                'status'              => 'success',
                'status_code'         => $statusCode,
                'message'             => 'SMS sent successfully',
                'response'            => $res['body'] ?? $res['raw'] ?? null,
                'provider_message_id' => $providerMessageId,
            ];
        }

        return [
            'status'      => 'error',
            'status_code' => $statusCode,
            'message'     => !empty($res['error']) ? $res['error'] : 'Failed to send SMS',
            'response'    => $res['body'] ?? $res['raw'] ?? null,
        ];
    }
}
