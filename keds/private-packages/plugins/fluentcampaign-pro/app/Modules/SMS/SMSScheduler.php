<?php

namespace FluentCampaign\App\Modules\SMS;

use FluentCampaign\App\Modules\SMS\Models\SMSMessage;
use FluentCampaign\App\Modules\SMS\Models\SMSCampaign;
use FluentCrm\App\Services\Helper;

class SMSScheduler
{
    protected $sendingPerChunk = 30;
    protected $maximumProcessingTime = 30;
    protected $sentCount = 0;
    protected $startingTimeStamp;

    public function __construct()
    {
        $sendingPerChunk = (int)apply_filters('fluent_crm/sms_scheduler_chunk_size', $this->sendingPerChunk, $this);
        if ($sendingPerChunk > 0) {
            $this->sendingPerChunk = $sendingPerChunk;
        }

        $maximumProcessingTime = (int)apply_filters('fluent_crm/sms_scheduler_max_processing_seconds', $this->maximumProcessingTime, $this);
        if ($maximumProcessingTime > 0) {
            $this->maximumProcessingTime = $maximumProcessingTime;
        }
    }

    /**
     * Register Action Scheduler hooks for SMS processing.
     *
     * Three clean hooks replace all previous cron/AJAX/fallback triggers:
     * - fluentcrm_send_single_sms: Send one SMS (direct, custom, automation)
     * - fluentcrm_send_sms_batch: Send a batch of campaign SMS, chains to next batch
     * - fluentcrm_generate_sms_batch: Generate SMSMessage records from campaign subscribers
     */
    public function register()
    {
        // Flow 1 & 3: Single SMS send (direct, custom, automation)
        add_action('fluentcrm_send_single_sms', [$this, 'handleSendSingleSMS']);

        // Flow 2: Campaign batch sending
        add_action('fluentcrm_send_sms_batch', [$this, 'handleSendBatch'], 10, 2);

        // Flow 2: Campaign message generation (delegates to SMSHandler — must be instance, not static)
        add_action('fluentcrm_generate_sms_batch', [(new SMSHandler()), 'handleGenerateBatch']);

        // Campaign processing trigger from free plugin (when email handler is idle)
        add_action('fluentcrm_scheduled_maybe_regular_tasks', [$this, 'maybeTriggerCampaignProcessing']);

        // Health check piggybacks on the email plugin's existing 5-minute scheduler —
        // no separate AS action needed, rate-limited internally to once per 5 minutes
        add_action('fluentcrm_scheduled_five_minute_tasks', [$this, 'handleHealthCheck']);
        add_action('fluentcrm_scheduled_hourly_tasks', [$this, 'handleHealthCheck']);
    }

    /**
     * Handle sending a single SMS message by ID.
     * Scheduled via: as_schedule_single_action(time(), 'fluentcrm_send_single_sms', [$messageId], 'fluent-crm-sms')
     *
     * @param int $messageId
     */
    public function handleSendSingleSMS($messageId)
    {
        if (!$this->isSystemOk()) {
            return;
        }

        $smsMessage = SMSMessage::with('campaign', 'subscriber')->find($messageId);

        if (!$smsMessage) {
            return;
        }

        if (!in_array($smsMessage->status, ['pending', 'scheduled'])) {
            return;
        }

        // Atomic status transition to prevent duplicate sends from concurrent runners
        $updated = fluentCrmDb()->table('fc_sms_messages')
            ->where('id', $smsMessage->id)
            ->whereIn('status', ['pending', 'scheduled'])
            ->update([
                'status'     => 'processing',
                'updated_at' => current_time('mysql')
            ]);

        if (!$updated) {
            return; // Another runner already claimed this message
        }

        $this->sendSingleSMS($smsMessage);
        $this->logSentCount();
    }

    /**
     * Handle sending a batch of SMS messages for a campaign.
     * Uses last message ID as cursor for reliable pagination.
     * Scheduled via: as_schedule_single_action(time(), 'fluentcrm_send_sms_batch', [$campaignId, $lastMessageId], 'fluent-crm-sms')
     *
     * @param int $campaignId
     * @param int $lastMessageId Last processed message ID (0 = start from beginning)
     */
    public function handleSendBatch($campaignId, $lastMessageId = 0)
    {
        if (!$this->isSystemOk()) {
            return;
        }

        $campaign = SMSCampaign::find($campaignId);

        if (!$campaign) {
            return;
        }

        // Abort if campaign is no longer in a sendable state
        if (in_array($campaign->status, ['paused', 'cancelled', 'draft', 'archived'])) {
            return;
        }

        // Transition from scheduled to working when it's time
        if ($campaign->status == 'scheduled' && strtotime($campaign->scheduled_at) <= current_time('timestamp')) {
            $campaign->status = 'working';
            $campaign->save();
        }

        if (!in_array($campaign->status, ['working', 'scheduled'])) {
            return;
        }

        // Reset stuck processing messages before fetching next batch (use consistent timezone)
        $stuckThreshold = date('Y-m-d H:i:s', current_time('timestamp') - $this->maximumProcessingTime - 30);
        SMSMessage::where('campaign_id', $campaignId)
            ->where('status', 'processing')
            ->where('updated_at', '<', $stuckThreshold)
            ->update(['status' => 'pending']);

        $currentTime = current_time('mysql');

        // Fetch next batch using last message ID as cursor (ordered by id ASC)
        $smsMessages = SMSMessage::where('campaign_id', $campaignId)
            ->where('id', '>', $lastMessageId)
            ->whereIn('status', ['pending', 'scheduled'])
            ->where('scheduled_at', '<=', $currentTime)
            ->with('campaign', 'subscriber')
            ->orderBy('id', 'ASC')
            ->limit($this->sendingPerChunk)
            ->get();

        if ($smsMessages->isEmpty()) {
            // No ready messages — but campaign may have future-scheduled messages
            $this->maybeFinalizeCampaignOrReschedule($campaignId);
            $this->logSentCount();
            return;
        }

        // Atomic mark as processing — only transition rows still in pending/scheduled
        $ids = $smsMessages->pluck('id')->toArray();
        fluentCrmDb()->table('fc_sms_messages')
            ->whereIn('id', $ids)
            ->whereIn('status', ['pending', 'scheduled'])
            ->update([
                'status'     => 'processing',
                'updated_at' => $currentTime
            ]);

        // Send each message, tracking the last processed ID
        $lastProcessedId = $lastMessageId;
        $brokeEarly = false;
        foreach ($smsMessages as $smsMessage) {
            $this->sendSingleSMS($smsMessage);
            $lastProcessedId = $smsMessage->id;

            if ($this->isTimeUp() || $this->memoryExceeded()) {
                $brokeEarly = true;
                break;
            }
        }

        // Revert unsent messages from this batch back to pending
        if ($brokeEarly) {
            $unsentIds = [];
            foreach ($smsMessages as $msg) {
                if ($msg->id > $lastProcessedId) {
                    $unsentIds[] = $msg->id;
                }
            }
            if ($unsentIds) {
                fluentCrmDb()->table('fc_sms_messages')
                    ->whereIn('id', $unsentIds)
                    ->where('status', 'processing')
                    ->update(['status' => 'pending']);
            }
        }

        $this->logSentCount();

        // Check if there are more messages to send (include processing for stuck recovery)
        $remainingCount = SMSMessage::where('campaign_id', $campaignId)
            ->whereIn('status', ['pending', 'scheduled', 'processing'])
            ->count();

        if ($remainingCount > 0) {
            // Schedule next batch with cursor at last processed ID
            as_schedule_single_action(
                time(),
                'fluentcrm_send_sms_batch',
                [$campaignId, $lastProcessedId],
                'fluent-crm-sms'
            );
        } else {
            $this->maybeFinalizeCampaign($campaignId);
        }
    }

    /**
     * Health check hooked to the email plugin's existing recurring schedulers
     * (fluentcrm_scheduled_five_minute_tasks / fluentcrm_scheduled_hourly_tasks).
     * Rescues stuck campaigns without creating any new background jobs.
     *
     * Covers two failure modes:
     * 1. Generation stuck: campaign in processing/pending-scheduled but AS action crashed
     * 2. Sending stuck: campaign in working/scheduled but send batch action disappeared
     */
    public function handleHealthCheck()
    {
        if (!SMSHelper::isActive()) {
            return;
        }

        // Rate-limit: at most once every 5 minutes regardless of how often the hook fires
        $lastRun = fluentCrmGetOptionCache('_fcrm_sms_health_check_last', 360);
        if ($lastRun && (time() - $lastRun) < 300) {
            return;
        }
        fluentCrmSetOptionCache('_fcrm_sms_health_check_last', time(), 360);

        $now = current_time('timestamp');

        // --- Recovery 1: stuck generation ---
        // Campaigns still in pending-scheduled/processing whose scheduled_at is >5 min in the past
        // and haven't been updated recently (not actively being processed right now)
        $genStuckThreshold  = date('Y-m-d H:i:s', $now - 600);  // not updated for 10 min
        $pastScheduleTime   = date('Y-m-d H:i:s', $now - 300);  // scheduled_at >5 min ago

        $stuckGenCampaigns = SMSCampaign::whereIn('status', ['processing', 'pending-scheduled'])
            ->whereIn('type', ['campaign', 'automation'])
            ->where('scheduled_at', '<', $pastScheduleTime)
            ->where('updated_at', '<', $genStuckThreshold)
            ->get(['id', 'status']);

        foreach ($stuckGenCampaigns as $campaign) {
            if (!as_next_scheduled_action('fluentcrm_generate_sms_batch', [$campaign->id], 'fluent-crm-sms')) {
                Helper::debugLog('SMS Health Check', 'Recovering stuck generation for campaign #' . $campaign->id . ' (status: ' . $campaign->status . ')');
                as_schedule_single_action(time(), 'fluentcrm_generate_sms_batch', [$campaign->id], 'fluent-crm-sms');
            }
        }

        // --- Recovery 2: stuck sending ---
        // Campaigns in working/scheduled whose send time has passed but still have unsent messages
        // and no send batch action is queued for them
        $stuckSendCampaigns = SMSCampaign::whereIn('status', ['working', 'scheduled'])
            ->whereIn('type', ['campaign', 'automation'])
            ->where('scheduled_at', '<', $pastScheduleTime)
            ->whereHas('messages', function ($q) {
                $q->whereIn('status', ['pending', 'scheduled']);
            })
            ->get(['id']);

        foreach ($stuckSendCampaigns as $campaign) {
            // Check per-campaign — another campaign's batch action doesn't cover this one
            $existingSend = as_next_scheduled_action('fluentcrm_send_sms_batch', [$campaign->id, 0], 'fluent-crm-sms');
            if (!$existingSend) {
                Helper::debugLog('SMS Health Check', 'Recovering stuck send for campaign #' . $campaign->id);
                as_schedule_single_action(time(), 'fluentcrm_send_sms_batch', [$campaign->id, 0], 'fluent-crm-sms');
            }
        }

        // Clean up any fully-sent campaigns still showing active statuses
        self::markSMSCampaignsAsArchived();
    }

    /**
     * Entry point from free plugin's Handler.php when no emails are being sent.
     * Checks for pending SMS campaigns and triggers message generation.
     */
    public function maybeTriggerCampaignProcessing()
    {
        Helper::debugLog('SMS Scheduler Triggered', 'Checking for pending SMS campaigns to process');
        if (!SMSHelper::isActive()) {
            return;
        }

        // Rate-limit: once per 60 seconds (TTL must be >= check interval)
        $lastRun = fluentCrmGetOptionCache('_fcrm_last_sms_campaign_trigger', 120);
        if ($lastRun && (time() - $lastRun) < 60) {
            return;
        }

        fluentCrmSetOptionCache('_fcrm_last_sms_campaign_trigger', time(), 120);

        $cutOutTime = date('Y-m-d H:i:s', current_time('timestamp') + 360); // within 6 minutes

        // Include 'scheduled' status to recover future-scheduled campaigns that had
        // their first send batch find zero ready messages
        $smsCampaign = SMSCampaign::whereIn('status', ['pending-scheduled', 'processing', 'scheduled'])
            ->whereIn('type', ['campaign', 'automation'])
            ->orderBy('scheduled_at', 'ASC')
            ->where('scheduled_at', '<=', $cutOutTime)
            ->first();

        if (!$smsCampaign) {
            self::markSMSCampaignsAsArchived();
            return;
        }

        if ($smsCampaign->status == 'scheduled') {
            // Campaign already has messages generated — schedule a send batch if none pending
            $existingSend = as_next_scheduled_action('fluentcrm_send_sms_batch', null, 'fluent-crm-sms');
            if (!$existingSend) {
                as_schedule_single_action(
                    time(),
                    'fluentcrm_send_sms_batch',
                    [$smsCampaign->id, 0],
                    'fluent-crm-sms'
                );
            }
        } else {
            // Campaign needs message generation
            $existingGen = as_next_scheduled_action('fluentcrm_generate_sms_batch', [$smsCampaign->id], 'fluent-crm-sms');
            if (!$existingGen) {
                as_schedule_single_action(
                    time(),
                    'fluentcrm_generate_sms_batch',
                    [$smsCampaign->id],
                    'fluent-crm-sms'
                );
            }
        }
    }

    /**
     * Check if a campaign is complete and mark as archived.
     *
     * @param int $campaignId
     */
    private function maybeFinalizeCampaign($campaignId)
    {
        $pendingCount = SMSMessage::where('campaign_id', $campaignId)
            ->whereIn('status', ['pending', 'scheduled', 'scheduling', 'processing'])
            ->count();

        if ($pendingCount === 0) {
            $campaign = SMSCampaign::find($campaignId);
            if ($campaign && in_array($campaign->status, ['working', 'scheduled'])) {
                SMSCampaign::where('id', $campaignId)->update([
                    'status'     => 'archived',
                    'updated_at' => current_time('mysql')
                ]);

                $campaign = SMSCampaign::find($campaignId);
                do_action('fluent_crm/sms_campaign_archived', $campaign);
            }
        }
    }

    /**
     * If there are no ready messages now but future-scheduled ones exist,
     * schedule a retry precisely at the next message's scheduled_at time.
     * This avoids the wasteful every-60-seconds polling loop for far-future campaigns.
     *
     * @param int $campaignId
     */
    private function maybeFinalizeCampaignOrReschedule($campaignId)
    {
        $nextMessage = SMSMessage::where('campaign_id', $campaignId)
            ->whereIn('status', ['pending', 'scheduled'])
            ->where('scheduled_at', '>', current_time('mysql'))
            ->orderBy('scheduled_at', 'ASC')
            ->first(['id', 'scheduled_at']);

        if ($nextMessage) {
            // Schedule retry exactly when the next message becomes ready (min 30s from now)
            $nextSendAt = max(time() + 30, strtotime($nextMessage->scheduled_at));
            as_schedule_single_action(
                $nextSendAt,
                'fluentcrm_send_sms_batch',
                [$campaignId, 0],
                'fluent-crm-sms'
            );
        } else {
            $this->maybeFinalizeCampaign($campaignId);
        }
    }

    /**
     * Check if system is ready to process SMS.
     * Simplified: no option-based concurrency locks, Action Scheduler handles that.
     */
    private function isSystemOk()
    {
        if (!SMSHelper::isActive()) {
            return false;
        }

        if (apply_filters('fluent_crm/disable_sms_processing', false)) {
            return false;
        }

        $this->startingTimeStamp = time();

        if ($this->memoryExceeded()) {
            Helper::debugLog('SMS Scheduler Memory Exceeded', 'Memory Limit: ' . fluentCrmGetMemoryLimit() . ' | Current Usage: ' . memory_get_usage(true));
            return false;
        }

        return true;
    }

    /**
     * Send SMS messages from a collection.
     */
    protected function sendSMSMessages($smsMessages)
    {
        foreach ($smsMessages as $smsMessage) {
            $this->sendSingleSMS($smsMessage);

            if ($this->isTimeUp()) {
                break;
            }
        }
    }

    /**
     * Send a single SMS message.
     */
    protected function sendSingleSMS($smsMessage)
    {
        try {
            $subscriber = $smsMessage->subscriber;

            // Use mobile_number from message as primary (supports custom/automation numbers),
            // fall back to subscriber phone
            $phoneNumber = $smsMessage->mobile_number ?: ($subscriber ? $subscriber->phone : null);

            if (!$phoneNumber) {
                $this->markSMSAsFailed($smsMessage, 'No valid phone number');
                return;
            }

            if (!$subscriber) {
                $this->markSMSAsFailed($smsMessage, 'Subscriber not found');
                return;
            }

            // Prevent sending to the contact's own number when they are not SMS subscribed.
            // We only enforce this for the subscriber's saved phone number so custom target
            // numbers used in automation/manual flows can still be sent intentionally.
            if (
                $subscriber->phone &&
                $phoneNumber === $subscriber->phone &&
                $subscriber->sms_status !== 'sms_subscribed'
            ) {
                $this->markSMSAsFailed($smsMessage, 'Contact is not subscribed for SMS');
                return;
            }

            // Parse message content with subscriber data
            $messageContent = SMSHelper::parseMessageContent($smsMessage->message_content, $subscriber);

            // Send SMS
            $result = SMSSender::send($phoneNumber, $messageContent);

            if ($result && isset($result['status']) && $result['status'] === 'success') {
                $this->markSMSAsSent($smsMessage, $result);
                Helper::debugLog('SMS Sent', 'Phone: ' . $phoneNumber . ' | Message: ' . $messageContent . ' | Result: ' . json_encode($result));
                $this->sentCount++;
            } else {
                Helper::debugLog('SMS Send Failed', 'Phone: ' . $phoneNumber . ' | Message: ' . $messageContent . ' | Result: ' . json_encode($result));
                $errorMessage = isset($result['message']) ? $result['message'] : 'Unknown error';
                $this->markSMSAsFailed($smsMessage, $errorMessage);
            }

        } catch (\Exception $e) {
            $this->markSMSAsFailed($smsMessage, $e->getMessage());
            Helper::debugLog('SMS Send Exception', $e->getMessage(), 'error');
        }
    }

    /**
     * Mark SMS as sent.
     */
    protected function markSMSAsSent($smsMessage, $result)
    {
        $updateData = [
            'status'     => SMSMessage::STATUS_SENT,
            'sent_at'    => current_time('mysql'),
            'updated_at' => current_time('mysql')
        ];

        if (isset($result['provider_message_id'])) {
            $updateData['provider_message_id'] = $result['provider_message_id'];
        }

        SMSMessage::where('id', $smsMessage->id)->update($updateData);

        if ($smsMessage->campaign_id) {
            SMSCampaign::where('id', $smsMessage->campaign_id)
                ->increment('sent_count');
        }

        do_action('fluent_crm/sms_sent', $smsMessage, $result);
    }

    /**
     * Mark SMS as failed.
     */
    protected function markSMSAsFailed($smsMessage, $errorMessage)
    {
        SMSMessage::where('id', $smsMessage->id)->update([
            'status'     => SMSMessage::STATUS_FAILED,
            'notes'      => $errorMessage,
            'updated_at' => current_time('mysql')
        ]);

        if ($smsMessage->campaign_id) {
            SMSCampaign::where('id', $smsMessage->campaign_id)
                ->increment('failed_count');
        }

        do_action('fluent_crm/sms_failed', $smsMessage, $errorMessage);
    }

    /**
     * Parse message content with subscriber data.
     *
     * @deprecated Use SMSHelper::parseMessageContent() directly.
     */
    public function parseMessageContent($content, $subscriber)
    {
        return SMSHelper::parseMessageContent($content, $subscriber);
    }

    /**
     * Check if processing time limit is reached.
     */
    protected function isTimeUp()
    {
        return (time() - $this->startingTimeStamp) >= $this->maximumProcessingTime;
    }

    /**
     * Check if memory limit is exceeded.
     */
    protected function memoryExceeded()
    {
        $memoryLimit = fluentCrmGetMemoryLimit();
        $currentUsage = memory_get_usage(true);
        return $currentUsage >= ($memoryLimit * 0.9);
    }

    /**
     * Log sent count.
     */
    protected function logSentCount()
    {
        if ($this->sentCount > 0) {
            Helper::debugLog('SMS Sent Count', $this->sentCount . ' SMS messages sent');
        }
    }

    /**
     * Mark completed SMS campaigns as archived.
     */
    public static function markSMSCampaignsAsArchived()
    {
        $smsCampaigns = SMSCampaign::whereIn('status', ['working', 'scheduled'])
            ->whereDoesntHave('messages', function ($query) {
                $query->whereIn('status', ['scheduling', 'pending', 'scheduled', 'processing', 'draft']);
                return $query;
            })
            ->withoutGlobalScope('type')
            ->whereIn('type', ['campaign', 'automation'])
            ->where('scheduled_at', '<', date('Y-m-d H:i:s', current_time('timestamp') - 300))
            ->get();

        if (!$smsCampaigns->isEmpty()) {
            SMSCampaign::whereIn('id', array_unique($smsCampaigns->pluck('id')->toArray()))
                ->withoutGlobalScope('type')
                ->update([
                    'status' => 'archived'
                ]);

            foreach ($smsCampaigns as $smsCampaign) {
                do_action('fluent_crm/sms_campaign_archived', $smsCampaign);
            }

            return true;
        }

        return false;
    }
}
