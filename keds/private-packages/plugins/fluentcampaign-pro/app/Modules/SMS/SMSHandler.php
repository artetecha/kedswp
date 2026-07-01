<?php

namespace FluentCampaign\App\Modules\SMS;

use FluentCampaign\App\Modules\SMS\Models\SMSCampaign;
use FluentCampaign\App\Modules\SMS\Models\SMSMessage;
use FluentCrm\Framework\Support\Arr;


class SMSHandler
{
    public function register()
    {
        add_action('fluentcrm_webhook_to_sms_webhook', [$this, 'handleSMSWebhook']);
    }

    /**
     * Handle SMS provider webhooks dispatched by FluentCRM's public ExternalPages router.
     *
     * Core routes ?fluentcrm=1&route=webhook&handler=sms_webhook requests to the
     * fluentcrm_webhook_to_sms_webhook action. This keeps the pro SMS webhook on the
     * same public handler path as other FluentCRM external webhooks.
     *
     * @param array $requestData Query/form data passed by ExternalPages.
     */
    public function handleSMSWebhook($requestData = [])
    {
        $provider = isset($_GET['provider']) ? $this->sanitizeRequestValue($_GET['provider']) : '';
        $hash = isset($_GET['hash']) ? $this->sanitizeRequestValue($_GET['hash']) : '';

        if (!$provider) {
            $provider = $this->sanitizeRequestValue(Arr::get($requestData, 'provider', ''));
        }

        if (!$hash) {
            $hash = $this->sanitizeRequestValue(Arr::get($requestData, 'hash', ''));
        }

        if (!$provider) {
            status_header(400);
            echo 'Missing provider';
            exit;
        }

        $allowed_providers = SMSHelper::getAllowedProviders();

        if (!in_array($provider, $allowed_providers, true)) {
            status_header(400);
            echo 'Invalid provider';
            exit;
        }

        if (!$hash) {
            status_header(400);
            echo 'Missing hash';
            exit;
        }

        if (!SMSHelper::verifyWebhookHash($hash, $provider, '')) {
            status_header(403);
            echo 'Invalid hash';
            exit;
        }

        $bodyData = $this->getWebhookPayload($provider);

        switch ($provider) {
            case 'twilio':
                SMSReceiver::handleTwilioWebhook($bodyData);
                break;

            default:
                SMSReceiver::handleCustomProviderWebhook($provider, $bodyData);
                break;
        }

        echo 'OK';
        exit;
    }

    private function sanitizeRequestValue($value)
    {
        if (is_array($value) || is_object($value)) {
            return '';
        }

        return sanitize_text_field(wp_unslash((string) $value));
    }

    private function getWebhookPayload($provider)
    {
        $requestMethod = $_SERVER['REQUEST_METHOD'] ?? '';
        $contentType   = $_SERVER['CONTENT_TYPE'] ?? '';
        $bodyData      = [];

        if ($requestMethod !== 'POST') {
            return [];
        }

        if (!empty($_POST)) {
            return $this->sanitizePayload(wp_unslash($_POST), $provider);
        }

        $rawInput = file_get_contents('php://input');

        if (!$rawInput) {
            return [];
        }

        if (stripos($contentType, 'application/json') !== false) {
            $decoded = json_decode($rawInput, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $bodyData = $decoded;
            }
        }

        if (empty($bodyData)) {
            parse_str($rawInput, $bodyData);
            $bodyData = wp_unslash($bodyData);
        }

        return $this->sanitizePayload($bodyData, $provider);
    }

    private function sanitizePayload($payload, $provider = '')
    {
        if (!is_array($payload)) {
            return [];
        }

        $payload = map_deep($payload, 'sanitize_textarea_field');

        if ($provider !== 'twilio') {
            return $payload;
        }

        foreach (['From', 'To', 'MessageSid', 'SmsSid', 'SmsMessageSid'] as $key) {
            if (!empty($payload[$key]) && !is_array($payload[$key])) {
                $payload[$key] = sanitize_text_field($payload[$key]);
            }
        }

        if (isset($payload['Body']) && !is_array($payload['Body'])) {
            $payload['Body'] = sanitize_textarea_field($payload['Body']);
        }

        return $payload;
    }

    /**
     * Action Scheduler handler for generating SMSMessage records for a campaign.
     * Processes subscribers in batches and chains to the next batch until all are done.
     *
     * Scheduled via: as_schedule_single_action(time(), 'fluentcrm_generate_sms_batch', [$campaignId], 'fluent-crm-sms')
     *
     * @param int $campaignId
     */
    public function handleGenerateBatch($campaignId)
    {
        if (!SMSHelper::isActive()) {
            return;
        }

        $smsCampaign = SMSCampaign::find($campaignId);

        if (!$smsCampaign) {
            return;
        }

        if (!in_array($smsCampaign->status, ['pending-scheduled', 'processing'])) {
            return;
        }

        if ($smsCampaign->status == 'pending-scheduled') {
            $smsCampaign->status = 'processing';
            $smsCampaign->save();
        }

        if (fluentCrmIsMemoryExceeded()) {
            // Reschedule for later if memory is tight
            as_schedule_single_action(
                time() + 10,
                'fluentcrm_generate_sms_batch',
                [$campaignId],
                'fluent-crm-sms'
            );
            return;
        }

        $perChunk = (int) apply_filters('fluent_crm/sms_process_subscribers_per_request', 30);
        if ($perChunk < 1) {
            $perChunk = 30;
        }

        $subscribersModel = $smsCampaign->getSubscribersModel($smsCampaign->settings);

        if (!$subscribersModel) {
            // No valid subscriber selection, mark campaign as failed
            $smsCampaign->status = 'archived';
            $smsCampaign->save();
            return;
        }

        $subscribersModel = $subscribersModel->limit($perChunk)->offset($smsCampaign->recipients_count);

        $result = $this->subscribe($smsCampaign, $subscribersModel);

        if (!empty($result)) {
            // More subscribers to process — schedule next generation batch
            as_schedule_single_action(
                time(),
                'fluentcrm_generate_sms_batch',
                [$campaignId],
                'fluent-crm-sms'
            );
        } else {
            // All subscribers processed — finalize generation
            $smsCampaign = SMSCampaign::find($smsCampaign->id);

            if (!$smsCampaign) {
                return; // Campaign was deleted mid-generation
            }

            if ($smsCampaign->status == 'processing') {
                $smsCampaign->status = 'scheduled';
                $smsCampaign->save();
            }

            SMSMessage::where('campaign_id', $smsCampaign->id)
                ->where('status', 'scheduling')
                ->update([
                    'status' => 'scheduled'
                ]);

            $smsCampaign->maybeDeleteDuplicates();

            // Schedule the first sending batch (start from message ID 0 = beginning)
            as_schedule_single_action(
                time(),
                'fluentcrm_send_sms_batch',
                [$campaignId, 0],
                'fluent-crm-sms'
            );
        }
    }

    private function subscribe($smsCampaign, $subscribersModel)
    {
        $subscribers = $subscribersModel->get();

        if ($subscribers->isEmpty()) {
            return [];
        }

        return $smsCampaign->subscribe($subscribers, [
            'status'       => 'scheduling',
            'scheduled_at' => $smsCampaign->getSMSScheduleAt(),
            'sms_type'     => 'campaign',
        ], true);
    }
}
