<?php

namespace FluentCampaign\App\Modules\SMS\Http\Controllers;

use FluentCampaign\App\Http\Controllers\Controller;
use FluentCampaign\App\Modules\SMS\Models\SMSMessage;
use FluentCampaign\App\Modules\SMS\Models\SMSCampaign;
use FluentCampaign\App\Modules\SMS\SMSHelper;
use FluentCrm\App\Models\Subscriber;
use FluentCrm\App\Services\Helper;
use FluentCrm\Framework\Http\Request\Request;
use FluentCrm\Framework\Support\Arr;

class SMSController extends Controller
{
    public function campaigns(Request $request)
    {
        $search = sanitize_text_field($request->get('searchBy'));
        $status = $request->get('statuses');
        $order = $request->get('sort_type') ?: 'DESC';
        // fc_sms_campaigns columns. Required because the framework rewrite made
        // orderBy() throw on names that don't match ^[a-zA-Z0-9_\.]+$.
        $allowedOrderBy = [
            'id', 'parent_id', 'type', 'title', 'slug', 'status', 'message_content',
            'sender_number', 'recipients_count', 'sent_count', 'failed_count', 'delay',
            'scheduled_at', 'settings', 'created_by', 'created_at', 'updated_at',
        ];
        $orderBy = sanitize_key((string) ($request->get('sort_by') ?: 'id'));
        if (!in_array($orderBy, $allowedOrderBy, true)) {
            $orderBy = 'id';
        }
        $with = $request->get('with', []);
        $labels = $request->get('labels', []);

        $campaignQuery = SMSCampaign::when($status, function ($query) use ($status) {
                    return $query->whereIn('status', $status);
                })->where('type', 'campaign')
                  ->when($search, function ($query) use ($search) {
                      return $query->where('title', 'LIKE', "%$search%");
                  })
                  ->orderBy($orderBy, ($order == 'ASC') ? 'ASC' : 'DESC');

        if (!empty($labels)) {
            $campaignQuery->whereHas('labelsTerm', function ($query) use ($labels) {
                $query->whereIn('term_id', $labels);
            });
        }

        $campaigns = $campaignQuery->paginate();

        if (in_array('stats', $with)) {
            foreach ($campaigns as $campaign) {
//                $campaign->stats = $campaign->stats();
                $campaign->next_step = fluentcrm_get_sms_campaign_meta($campaign->id, '_next_config_step', true);
                $campaign->labels = $campaign->getFormattedLabels();
            }
        }

        return [
            'campaigns' => $campaigns
        ];
    }
    public function campaign(Request $request, $id)
    {
        $campaign = SMSCampaign::findOrFail($id);

        /**
         * Determine the sms campaign data in FluentCRM.
         *
         * This filter allows modification of the sms campaign data before it is used.
         *
         * @since 2.6.51
         *
         * @param array $campaign The sms campaign data.
         */

        $campaign->server_time = current_time('mysql');

        $campaign = apply_filters('fluent_crm/sms_campaign_data', $campaign);

        return $this->sendSuccess([
            'campaign' => $campaign
        ]);
    }
    public function create(Request $request)
    {
        $data = $this->validate($request->all(), [
            'title' => 'required',
            'message_content' => 'required'
        ]);

        $data['title'] = sanitize_text_field($data['title']);
        //Allow only links, no other HTML
        $data['message_content'] = wp_kses($data['message_content'], [
            'a' => [
                'href' => [],
                'title' => [],
                'target' => []
            ]
        ]);

        $data['type'] = 'campaign'; // campaign type campaign

        $campaign = SMSCampaign::create($data);

        do_action('fluent_crm/sms_campaign_created', $campaign);

        return $this->sendSuccess([
            'campaign' => $campaign,
            'message' => __('SMS Campaign created successfully', 'fluent-crm')
        ]);
    }

    public function update(Request $request, $id)
    {
        $data = $this->validate($request->all(), [
            "title" => "required",
            "message_content" => "required"
        ]);

        $data['title'] = sanitize_text_field($data['title']);
        //Allow only links, no other HTML
        $data['message_content'] = wp_kses($data['message_content'], [
            'a' => [
                'href' => [],
                'title' => [],
                'target' => []
            ]
        ]);

        $updateData = Arr::only($data, [
            'title',
            'message_content'
        ]);

        if (!empty($data['settings'])) {
            $updateData['settings'] = $data['settings'];
        }

        $campaign = SMSCampaign::findOrFail($id);

        $campaign->fill($updateData)->save();

        do_action('fluent_crm/sms_campaign_updated', $campaign);

        $nextStep = Arr::get($data, 'next_step');

        if ($nextStep) {
            fluentcrm_update_sms_campaign_meta($id, '_next_config_step', $nextStep);
        }

        return $this->sendSuccess([
            'campaign' => $campaign,
            'message' => __('SMS Campaign Updated successfully', 'fluentcampaign-pro')
        ]);
    }

    /**
     * Get SMS logs for a specific subscriber
     *
     * @param Request $request
     * @param int $subscriberId
     */
    public function getLogs(Request $request, $subscriberId)
    {
        // Validate subscriber exists
        $subscriber = Subscriber::find($subscriberId);
        if (!$subscriber) {
            return $this->sendError([
                'message' => __('Subscriber not found', 'fluentcampaign-pro'), 
                'code' => 404
            ]);
        }

        $perPage = $request->get('per_page', 20);
        $page = $request->get('page', 1);
        $filter = $request->get('filter', 'all');
        $order = $request->get('order', 'asc');

        // Query SMS messages from database
        $query = SMSMessage::with(['campaign', 'subscriber'])
            ->where('subscriber_id', $subscriberId);

        // Apply filter
        if ($filter !== 'all') {
            $query->where('sms_type', $filter);
        }

        // Order by created_at
        $query->orderBy('created_at', $order === 'desc' ? 'desc' : 'asc');

        // Get paginated results
        $logs = $query->paginate($perPage, ['*'], 'page', $page);

        // Format logs for frontend
        $formattedLogs = $logs->items();
        foreach ($formattedLogs as &$log) {
            $log['message'] = $log['message_content'];
            $log['from'] = $log['mobile_number'];
            $log['date'] = date('m/d/Y', strtotime($log['sent_at'] ?? $log['created_at']));
            $log['time'] = date('H:i', strtotime($log['sent_at'] ?? $log['created_at']));
            $log['type'] = $log['sms_type'];
        }

        return [
            'logs' => [
                'data' => $formattedLogs,
                'total' => $logs->total(),
                'per_page' => $logs->perPage(),
                'current_page' => $logs->currentPage(),
                'last_page' => $logs->lastPage()
            ]
        ];
    }

    /**
     * Send SMS to a subscriber
     *
     * @param Request $request
     * @param int $subscriberId
     */
    public function sendCustomSMS(Request $request, $contactId)
    {
        // Check if SMS module is enabled
        if (!SMSHelper::isActive()) {
            return $this->sendError([
                'message' => __('SMS module is not enabled', 'fluentcampaign-pro'),
                'code' => 400
            ]);
        }

        // Validate subscriber exists
        $subscriber = Subscriber::find($contactId);
        if (!$subscriber) {
            return $this->sendError([
                'message' => __('Subscriber not found', 'fluentcampaign-pro'),
                'code' => 404
            ]);
        }

        // Validate required fields
        $message = $request->get('message');
        if (!$message) {
            return $this->sendError([
                'message' => __('Message is required', 'fluentcampaign-pro'),
                'code' => 400
            ]);
        }

        // Get subscriber phone number
        $phoneNumber = $subscriber->phone;
        if (!$phoneNumber) {
            return $this->sendError([
                'message' => __('Subscriber does not have a phone number', 'fluentcampaign-pro'),
                'code' => 400
            ]);
        }

        if ($subscriber->sms_status !== 'sms_subscribed') {
            return $this->sendError([
                'message' => __('This contact is not subscribed for SMS.', 'fluentcampaign-pro'),
                'code' => 400
            ]);
        }

        // Validate message length (SMS limit is typically 160 characters for single SMS)
        if (strlen($message) > 1600) { // Allow up to 10 SMS segments
            return $this->sendError([
                'message' => __('Message is too long. Maximum 1600 characters allowed.', 'fluentcampaign-pro'),
                'code' => 400
            ]);
        }

        // Get additional parameters
        $fromNumber = $request->get('from_number');
        $type = 'custom_sms'; // campaign, automation, custom_sms - this is custom sms

        // create sms campaign and it will do the rest of the work

        try { 

            $smsCampaign = SMSCampaign::create([
                'title' => 'Custom SMS to ' . $subscriber->full_name,
                'subscriber_id' => $contactId,
                'mobile_number' => $phoneNumber,
                'message_content' => $message,
                'type' => $type,
                'status' => SMSMessage::STATUS_PENDING,
                'scheduled_at' => current_time('mysql'),
            ]);

            $smsCampaign->subscribe([$contactId], [
                'status'       => 'pending',
                'scheduled_at' => current_time('mysql'),
                'sms_type' => 'custom_sms'
            ]);

            // Schedule instant send via Action Scheduler
            $smsMessage = SMSMessage::where('campaign_id', $smsCampaign->id)->first();
            if (!$smsMessage) {
                return $this->sendError([
                    'message' => __('Failed to prepare SMS for sending', 'fluentcampaign-pro')
                ]);
            }

            as_schedule_single_action(time(), 'fluentcrm_send_single_sms', [$smsMessage->id], 'fluent-crm-sms');

            return $this->sendSuccess([
                'message' => __('Custom SMS will be sent shortly', 'fluentcampaign-pro')
            ]);
        } catch (\Exception $e) {
            return $this->sendError([
                'message' => __('Something went wrong while sending SMS', 'fluentcampaign-pro')
            ]);
        }

        

    }

    /**
     * Get SMS statistics for a subscriber
     *
     * @param Request $request
     * @param int $subscriberId
     */
    public function getStats(Request $request, $subscriberId)
    {
        // Validate subscriber exists
        $subscriber = Subscriber::find($subscriberId);
        if (!$subscriber) {
            return $this->sendError([
                'message' => __('Subscriber not found', 'fluentcampaign-pro'),
                'code' => 404
            ]);
        }

        // Get real stats from database
        $stats = SMSMessage::getStats($subscriberId);

        return [
            'stats' => [
                'total_messages' => $stats['total'],
                'campaign_messages' => $stats['by_type']['campaign'],
                'automation_messages' => $stats['by_type']['automation'],
                'custom_sms' => $stats['by_type']['custom_sms'],
                'sent_messages' => $stats['sent'],
                'delivered_messages' => $stats['delivered'],
                'failed_messages' => $stats['failed'],
                'pending_messages' => $stats['pending'],
                'last_sent' => SMSMessage::where('subscriber_id', $subscriberId)
                    ->whereNotNull('sent_at')
                    ->orderBy('sent_at', 'desc')
                    ->value('sent_at')
            ]
        ];
    }


    public function getContactEstimation(Request $request)
    {
        $start_time = microtime(true);

        $filterType = $request->get('sending_filter', 'list_tag');

        $subscribersSettings = [
            'sending_filter' => $filterType
        ];

        if ($filterType == 'list_tag') {
            $subscribersSettings['subscribers'] = $request->get('subscribers', []);
            $subscribersSettings['excludedSubscribers'] = $request->get('excludedSubscribers', []);
        } else if ($filterType == 'dynamic_segment') {
            $subscribersSettings['dynamic_segment'] = $request->get('dynamic_segment', []);
        } else if ($filterType == 'advanced_filters') {
            $subscribersSettings['advanced_filters'] = Helper::parseArrayOrJson($request->get('advanced_filters'));
        } else {
            return [
                'count' => 0
            ];
        }

        $count = (new SMSCampaign())->getSubscriberIdsCountBySegmentSettings($subscribersSettings);

        return [
            'count'          => $count,
            'execution_time' => microtime(true) - $start_time
        ];
    }

    public function getRecipientsCount(Request $request, $campaignId)
    {
        $campaign = SMSCampaign::withoutGlobalScope('type')->findOrFail($campaignId);
        $preProcessedStatuses = [
            'draft',
            'processing',
            'pending-scheduled'
        ];

        if (in_array($campaign->status, $preProcessedStatuses)) {
            $count = $campaign->getSubscribersModel()->count();
        } else {
            $count = $campaign->recipients_count;
        }

        return [
            'estimated_count' => $count
        ];
    }

    public function schedule(Request $request, $campaignId)
    {
        $scheduleAt = $request->get('scheduled_at');
        $smsCampaign = SMSCampaign::findOrFail($campaignId);

        if ($smsCampaign->status != 'draft') {
            return $this->sendError([
                'message' => __('Campaign status is not in draft status. Please reload the page', 'fluentcampaign-pro')
            ], 422);
        }

        do_action('fluent_crm/sms_campaign_status_active', $smsCampaign);


        if($scheduleAt) {
            $sendingType = $request->get('sending_type', 'schedule');

            /*
            * Range schedule is removed for now, we will add it in future release after doing more testing
            */
            // if ($sendingType == 'range_schedule') {
                // $isInvalid = true;
                // if (is_array($scheduleAt) && count($scheduleAt) == 2) {
                //     $scheduleStartAt = $this->normalizeScheduleDateTime($scheduleAt[0]);

                //     if ($scheduleStartAt && strtotime($scheduleStartAt) < current_time('timestamp')) {
                //         $scheduleStartAt = current_time('mysql');
                //     }

                //     $scheduleEndAt = $this->normalizeScheduleDateTime($scheduleAt[1]);

                //     if ($scheduleStartAt && $scheduleEndAt && strtotime($scheduleStartAt) < strtotime($scheduleEndAt)) {
                //         $isInvalid = false;
                //     }

                //     $scheduleAt = [$scheduleStartAt, $scheduleEndAt];
                // }

                // if ($isInvalid) {
                //     return $this->sendError([
                //         'message' => 'Invalid schedule date range'
                //     ], 422);
            //     }

            //     $settings = $smsCampaign->settings;
            //     $settings['sending_type'] = 'range_schedule';
            //     $settings['schedule_range'] = [strtotime($scheduleAt[0]), strtotime($scheduleAt[1])];

            //     $data = [
            //         'status'           => 'pending-scheduled',
            //         'updated_at'       => fluentCrmTimestamp(),
            //         'scheduled_at'     => $scheduleAt[0],
            //         'recipients_count' => 0,
            //         'settings'         => maybe_serialize($settings)
            //     ];

            // } 
            if ($sendingType == 'schedule') {
                $scheduleAt = $this->normalizeScheduleDateTime($scheduleAt);
                if (!$scheduleAt) {
                    return $this->sendError([
                        'message' => __('Invalid schedule date', 'fluentcampaign-pro')
                    ], 422);
                }

                $settings = $smsCampaign->settings;
                $settings['sending_type'] = 'schedule';

                $data = [
                    'status'           => 'pending-scheduled',
                    'updated_at'       => fluentCrmTimestamp(),
                    'scheduled_at'     => $scheduleAt,
                    'recipients_count' => 0,
                    'settings'         => maybe_serialize($settings)
                ];
            }

            $message = __('Your sms campaign has been scheduled', 'fluentcampaign-pro');
            SMSCampaign::where('id', $campaignId)->update($data);

            // Schedule generation 5 minutes before send time so messages are ready when it's time.
            // max(time(), ...) handles cases where the scheduled time is very soon or already past.
            $generateAt = max(time(), strtotime($scheduleAt) - 300);
            if (!as_next_scheduled_action('fluentcrm_generate_sms_batch', [$campaignId], 'fluent-crm-sms')) {
                as_schedule_single_action($generateAt, 'fluentcrm_generate_sms_batch', [$campaignId], 'fluent-crm-sms');
            }

        } else {

            $message = __('Campaign is starting now', 'fluentcampaign-pro');

            $settings = $smsCampaign->settings;
            $settings['sending_type'] = 'instant';

            $data = [
                'status'           => 'pending-scheduled',
                'updated_at'       => fluentCrmTimestamp(),
                'scheduled_at'     => date('Y-m-d H:i:s', current_time('timestamp')), // send immediately
                'recipients_count' => 0,
                'settings'         => maybe_serialize($settings)
            ];

            SMSCampaign::where('id', $campaignId)->update($data);
        }

        fluentcrm_update_sms_campaign_meta($smsCampaign->id, '_sms_campaign_sent_by', get_current_user_id());

        $smsCampaign = SMSCampaign::findOrFail($campaignId);

        if ($scheduleAt) {
            do_action('fluent_crm/sms_campaign_scheduled', $smsCampaign, $smsCampaign->scheduled_at);
        } else {
            // Trigger generation immediately — scheduled_at is now so messages are ready as soon as generated
            as_schedule_single_action(time(), 'fluentcrm_generate_sms_batch', [$campaignId], 'fluent-crm-sms');
        }

        return $this->sendSuccess([
            'campaign'          => $smsCampaign,
            'message'           => $message,
            'current_timestamp' => fluentCrmTimestamp()
        ]);
    }

    /**
     * Normalize mixed datetime inputs into mysql datetime string.
     *
     * Accepts unix timestamps, mysql datetime strings, and ISO-8601 strings.
     *
     * @param mixed $value
     * @return string|null
     */
    private function normalizeScheduleDateTime($value)
    {
        if (is_numeric($value)) {
            $timestamp = (int)$value;
            return $timestamp ? wp_date('Y-m-d H:i:s', $timestamp, wp_timezone()) : null;
        }

        if (!is_string($value)) {
            return null;
        }

        $value = sanitize_text_field($value);
        if (!$value) {
            return null;
        }

        if (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $value)) {
            return $value;
        }

        $timestamp = strtotime($value);
        if ($timestamp === false) {
            return null;
        }

        return wp_date('Y-m-d H:i:s', $timestamp, wp_timezone());
    }

    public function pauseCampaign(Request $request, $id)
    {
        $campaign = SMSCampaign::findOrFail($id);

        if ($campaign->status != 'working') {
            return $this->sendError([
                'message' => __('You can only pause a campaign if it is on "Working" state, Please reload this page', 'fluentcampaign-pro')
            ]);
        }

        $campaign->status = 'paused';
        $campaign->save();

        SMSMessage::where('campaign_id', $campaign->id)
                     ->whereIn('status', ['scheduled', 'pending', 'scheduling'])
                     ->update([
                         'status' => 'paused'
                     ]);

        // $campaign = SMSCampaign::findOrFail($id);

        return [
            'message'  => __('Campaign has been successfully marked as paused', 'fluentcampaign-pro'),
            'campaign' => $campaign
        ];
    }

    public function resumeCampaign(Request $request, $id)
    {
        $campaign = SMSCampaign::findOrFail($id);

        if ($campaign->status != 'paused') {
            return $this->sendError([
                'message' => __('You can only resume a campaign if it is on "paused" state, Please reload this page', 'fluentcampaign-pro')
            ]);
        }

        $campaign->status = 'working';
        $campaign->save();

        SMSMessage::where('campaign_id', $campaign->id)
                     ->where('status', 'paused')
                     ->update([
                         'status'       => 'scheduled',
                         'scheduled_at' => current_time('mysql')
                     ]);

        // Schedule batch sending via Action Scheduler
        as_schedule_single_action(time(), 'fluentcrm_send_sms_batch', [$campaign->id, 0], 'fluent-crm-sms');

        return [
            'message'  => __('Campaign has been successfully resumed', 'fluentcampaign-pro'),
            'campaign' => $campaign
        ];
    }

    public function duplicateCampaign(Request $request, $id)
    {
        $oldCampaign = SMSCampaign::findOrFail($id);
        $newCampaign = [
            'title'            => __('[Duplicate] ', 'fluentcampaign-pro') . $oldCampaign->title,
            'message_content'  => $oldCampaign->message_content,
            'status'           => 'draft',
            'created_by'       => get_current_user_id(),
            'settings'         => $oldCampaign->settings
        ];
        $labelIds = $oldCampaign->getFormattedLabels()->pluck('id')->toArray();

        $campaign = SMSCampaign::create($newCampaign);
        $campaign->attachLabels($labelIds);

        do_action('fluent_crm/sms_campaign_duplicated', $campaign, $oldCampaign);

        return [
            'campaign' => $campaign,
            'message'  => __('Campaign has been successfully duplicated', 'fluentcampaign-pro')
        ];

    }

    public function unSchedule(Request $request, $id)
    {
        $campaign = SMSCampaign::findOrFail($id);

        $validStatuses = [
            'scheduled',
            'pending-scheduled',
            'processing'
        ];

        if (!in_array($campaign->status, $validStatuses)) {
            return $this->sendError([
                'message' => __('You can only un-schedule a campaign if it is on "scheduled" state, Please reload this page', 'fluentcampaign-pro')
            ]);
        }

        if ($campaign->status == 'processing' && strtotime($campaign->scheduled_at) < current_time('timestamp')) {
            return $this->sendError([
                'message' => __('You can only un-schedule a campaign if it is on "scheduled" state, Please reload this page', 'fluentcampaign-pro')
            ]);
        }

        $campaign->status = 'draft';
        $campaign->save();

        // check if there has any emails, if yes then delete all of them
        SMSMessage::where('campaign_id', $campaign->id)
                     ->delete();

        SMSMessage::withoutGlobalScope('type')->where('campaign_id', $campaign->id)
                     ->whereIn('status', ['scheduled', 'scheduling'])
                     ->delete();

        return [
            'message' => __('SMS Campaign has been successfully un-scheduled', 'fluentcampaign-pro')
        ];
    }

    /**
     * Get all SMS messages with pagination and filtering
     *
     * @param  Request  $request
     *
     * @return array
     */
    public function getAllMessages(Request $request)
    {
        $perPage = $request->get('per_page', 10);
        $page = $request->get('page', 1);
        $status = $request->get('status', '');
        $search = $request->getSafe('search', '');

        // Query SMS messages from database
        $query = SMSMessage::with(['campaign', 'subscriber'])
            ->orderBy('created_at', 'desc');

        // Apply status filter
        if ($status && $status !== 'all') {
            $query->where('status', $status);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('message_content', 'LIKE', '%' . $search . '%')
                    ->orWhere('mobile_number', 'LIKE', '%' . $search . '%')
                    ->orWhereHas('subscriber', function ($subscriberQuery) use ($search) {
                        $subscriberQuery->searchBy($search);
                    });
            });
        }

        // Get paginated results
        $messages = $query->paginate($perPage, ['*'], 'page', $page);

        // Get status counts for filter dropdown (strictly aligned with SMSMessage status constants)
        $statusCounts = SMSMessage::selectRaw('status, COUNT(*) as count')
            ->whereNotNull('status')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        $allowedStatuses = [
            SMSMessage::STATUS_PENDING,
            SMSMessage::STATUS_SENT,
            SMSMessage::STATUS_DELIVERED,
            SMSMessage::STATUS_FAILED,
            SMSMessage::STATUS_CANCELLED
        ];

        $statuses = [];
        foreach ($allowedStatuses as $allowedStatus) {
            if (!isset($statusCounts[$allowedStatus])) {
                continue;
            }

            $statuses[] = [
                'value' => $allowedStatus,
                'label' => ucwords(str_replace('_', ' ', $allowedStatus)),
                'count' => (int)$statusCounts[$allowedStatus]
            ];
        }

        return [
            'sms' => [
                'data' => $messages->items(),
                'total' => $messages->total(),
                'per_page' => $messages->perPage(),
                'current_page' => $messages->currentPage(),
                'last_page' => $messages->lastPage(),
                'from' => $messages->firstItem(),
                'to' => $messages->lastItem(),
                'next_page_url' => $messages->nextPageUrl(),
                'prev_page_url' => $messages->previousPageUrl()
            ],
            'statuses' => $statuses
        ];
    }

    /**
     * Delete multiple SMS messages
     *
     * @param Request $request
     */
    public function deleteMessages(Request $request)
    {
        $ids = $request->get('ids', []);
        
        if (empty($ids)) {
            return $this->sendError([
                'message' => 'No SMS messages selected for deletion',
                'code' => 400
            ]);
        }

        // Delete the messages
        $deleted = SMSMessage::whereIn('id', $ids)->delete();

        return $this->sendSuccess([
            'message' => sprintf(__('%d SMS messages deleted successfully', 'fluentcampaign-pro'), $deleted),
            'deleted_count' => $deleted
        ]);
    }

    /**
     * Resend a specific SMS message
     *
     * @param Request $request
     * @param int $message_id
     */
    public function resendMessage(Request $request, $id)
    {
        // Find the original message
        $originalMessage = SMSMessage::with(['subscriber'])->find($id);
        
        if (!$originalMessage) {
            return $this->sendError([
                'message' => 'SMS message not found',
                'code' => 404
            ]);
        }
        
        $subscriber = $originalMessage->subscriber;
        if (!$subscriber) {
            return $this->sendError([
                'message' => 'Subscriber not found',
                'code' => 404
            ]);
        }

        // Get subscriber phone number
        $phoneNumber = $subscriber->phone;
        if (!$phoneNumber) {
            return $this->sendError([
                'message' => 'Subscriber does not have a phone number',
                'code' => 400
            ]);
        }

        // Validate message content length
        $messageContent = $originalMessage->message_content;
        if (strlen($messageContent) > 1600) {
            return $this->sendError([
                'message' => 'Original message is too long to resend. Maximum 1600 characters allowed.',
                'code' => 400
            ]);
        }

        try {
            // Create a new message entry for resending
            $newMessage = SMSMessage::create([
                'subscriber_id' => $originalMessage->subscriber_id,
                'mobile_number' => $originalMessage->mobile_number,
                'message_content' => $messageContent,
                'campaign_id' => $originalMessage->campaign_id,
                'sms_type' => $originalMessage->sms_type,
                'status' => SMSMessage::STATUS_PENDING,
                'scheduled_at' => current_time('mysql'),
                'created_at' => current_time('mysql'),
                'updated_at' => current_time('mysql')
            ]);
            
            // Schedule instant send via Action Scheduler
            as_schedule_single_action(time(), 'fluentcrm_send_single_sms', [$newMessage->id], 'fluent-crm-sms');

            return $this->sendSuccess([
                'message' => __('SMS message has been queued for resending', 'fluentcampaign-pro'),
                'new_message_id' => $newMessage->id
            ]);
        } catch (\Exception $e) {
            return $this->sendError([
                'message' => __('Failed to queue SMS for resending', 'fluentcampaign-pro'),
                'code' => 500
            ]);
        }
    }

    public function handleBulkAction(Request $request)
    {
        $actionName = $request->getSafe('action_name', 'sanitize_text_field', '');
        $campaignIds = array_map('intval', $request->get('campaign_ids'));

        $selectAllCampaigns = $request->getSafe('select_all', '');

        $campaignIds = array_map(function ($id) {
            return (int)$id;
        }, $campaignIds);

        $campaignIds = array_unique(array_filter($campaignIds));

        if ($selectAllCampaigns == 'true') {
            $campaignIds = SMSCampaign::where('type', 'campaign')->pluck('id')->toArray();
        }

        if (!$campaignIds) {
            return $this->sendError([
                'message' => __('Please provide campaign IDs', 'fluentcampaign-pro')
            ]);
        }

        if ($actionName == 'apply_labels') {
            // labels are coming as array of ids from request
            $newLabels = (array) $request->get('labels', []);
            $newLabels = array_map('intval', $newLabels);
            $newLabels = array_unique(array_filter($newLabels));

            if (!is_array($newLabels)) {
                $newLabels = [$newLabels];
            }

            $newLabels = array_values(array_unique(array_filter(array_map('intval', $newLabels))));

            if (!$newLabels) {
                return $this->sendError([
                    'message' => __('Please provide labels', 'fluentcampaign-pro')
                ]);
            }

            $campaigns = SMSCampaign::whereIn('id', $campaignIds)->get();

            foreach ($campaigns as $campaign) {
                $campaign->attachLabels($newLabels);
            }

            return $this->sendSuccess([
                'message' => __('Labels has been applied successfully', 'fluentcampaign-pro'),
            ]);

        }

        if ($actionName == 'delete_campaigns') {
            $campaigns = SMSCampaign::whereIn('id', $campaignIds)->get();
            foreach ($campaigns as $campaign) {
                $campaignId = $campaign->id;
                $campaign->deleteCampaignData();
                $campaign->delete();
                do_action('fluent_crm/sms_campaign_deleted', $campaignId);
            }

            return $this->sendSuccess([
                'message' => __('Selected Campaigns has been deleted permanently', 'fluentcampaign-pro'),
            ]);
        }

        return $this->sendError([
            'message' => __('invalid bulk action', 'fluentcampaign-pro')
        ]);
    }

    public function updateLabels(Request $request, $campaignId)
    {
        $campaign = SMSCampaign::findOrFail($campaignId);
        $action = $request->getSafe('action', 'detach');
        $labelIds = $request->getSafe('label_ids', []);

        if (!is_array($labelIds)) {
            $labelIds = [$labelIds];
        }

        $labelIds = array_values(array_unique(array_filter(array_map('intval', $labelIds))));

        if (!$labelIds) {
            return $this->sendError([
                'message' => __('Please provide labels', 'fluentcampaign-pro')
            ]);
        }

        if ($action === 'detach') {
            $campaign->detachLabels($labelIds);
        } else {
            $campaign->attachLabels($labelIds);
        }

        return $this->sendSuccess([
            'message' => __('Labels has been updated', 'fluentcampaign-pro')
        ]);
    }

    public function getCampaignStatus(Request $request, $campaignId)
    {
        $requestCounter = $request->get('request_counter');
        $campaign = SMSCampaign::whereIn('type', ['campaign', 'automation'])
            ->findOrFail($campaignId);

        if ($campaign->status == 'processing' || $campaign->status == 'pending-scheduled') {
            return [
                'current_timestamp' => fluentCrmTimestamp(),
                'stat'              => [],
                'campaign'          => $campaign,
                'sent_count'        => 0,
                'analytics'         => [],
                'subject_analytics' => []
            ];
        }
        if ($campaign->status == 'scheduled' && $campaign->scheduled_at) {
            if (strtotime($campaign->scheduled_at) < strtotime(current_time('mysql'))) {
                $campaign->status = 'working';
                $campaign->save();
            }
        }

        if ($campaign->status == 'working') {
            $ranged = $campaign->rangedScheduleDates();
            if (!$ranged) {
                if (($requestCounter % 4) === 0) {
                    $lastChecked = fluentCrmGetOptionCache('_fcrm_last_sms_process_cleanup', 600);
                    if (!$lastChecked || time() - $lastChecked > 140) {
                        $dateStamp = date('Y-m-d H:i:s', (current_time('timestamp') - 150));
                        SMSMessage::where('status', 'processing')
                                     ->where('updated_at', '<', $dateStamp)
                                     ->update([
                                         'status' => 'pending'
                                     ]);
                        fluentCrmSetOptionCache('_fcrm_last_sms_process_cleanup', time(), 600);
                    }
                }

                // If no send batch action is scheduled for any campaign, schedule recovery
                $existing = as_next_scheduled_action('fluentcrm_send_sms_batch', null, 'fluent-crm-sms');
                if (!$existing) {
                    as_schedule_single_action(time(), 'fluentcrm_send_sms_batch', [$campaignId, 0], 'fluent-crm-sms');
                }
            }
        }

        $analytics = [];

        $sentCount = SMSMessage::select('id')
                                  ->where('campaign_id', $campaignId)
                                  ->where('status', 'sent')
                                  ->count();


        if ($campaign->status == 'archived') {

            if (isset($analytics['open']) && $analytics['open']['total'] > $sentCount) {
                $analytics['open']['total'] = $sentCount;
            }

            if (isset($analytics['click']) && $analytics['click']['total'] > $sentCount) {
                $analytics['click']['total'] = $sentCount;
            }

        }

        if ($campaign->status == 'working') {

            $campaign->scheduling_range = $ranged;

            $processingCount = SMSMessage::select('id')
                                            ->where('campaign_id', $campaignId)
                                            ->where('status', 'processing')
                                            ->count();

            if ($processingCount) {
                $maximumProcessingTime = fluentCrmMaxRunTime() + 40;
                SMSMessage::where('campaign_id', $campaignId)
                             ->where('status', 'processing')
                             ->where('updated_at', '<', gmdate('Y-m-d H:i:s', (current_time('timestamp') - $maximumProcessingTime)))
                             ->update([
                                 'status' => 'pending'
                             ]);
            } elseif ($sentCount) {
                $futureCount = SMSMessage::select('id')
                                            ->where('campaign_id', $campaignId)
                                            ->whereIn('status', ['pending', 'scheduled', 'paused', 'processing', 'draft'])
                                            ->count();

                if (!$futureCount) {
                    SMSCampaign::where('id', $campaign->id)->update([
                        'status'     => 'archived',
                        'updated_at' => current_time('mysql')
                    ]);
                    $campaign = SMSCampaign::findOrFail($campaignId);

                    do_action('fluent_crm/sms_campaign_archived', $campaign);
                }
            }
        }

        $stat = SMSMessage::select('status', fluentCrmDb()->raw('count(*) as total'))
                             ->where('campaign_id', $campaignId)
                             ->groupBy('status')
                             ->get();

        //attaching who sent the campaign
        $CampaignSentBy = $this->getCampaignSentData($campaign->id);
        $campaign->sent_by = $CampaignSentBy;

        return $this->sendSuccess([
            'current_timestamp' => fluentCrmTimestamp(),
            'stat'              => $stat,
            'campaign'          => $campaign,
            'sent_count'        => $sentCount,
            'analytics'         => $analytics,
        ], 200);


    }

    public function getCampaignRecipients(Request $request, $campaignId)
    {
        $filterType = $request->get('filter_type');
        $search = $request->getSafe('search', 'sanitize_text_field');

        $smsQuery = SMSMessage::with(['subscriber'])->where('campaign_id', $campaignId);

        if ($search) {
            $smsQuery->whereHas('subscriber', function ($q) use ($search) {
                $q->searchBy($search);
            });
        }

        $filterType = in_array($filterType, ['click', 'failed', 'sent', 'delivered']) ? $filterType : '';

        if ($filterType == 'failed') {
            $smsQuery = $smsQuery->where('status', 'failed');
        } else if ($filterType == 'sent') {
            $smsQuery = $smsQuery->where('status', 'sent');
        } else if ($filterType == 'delivered') {
            $smsQuery = $smsQuery->where('delivery_status', 'delivered');
        }

        $smsQuery = $smsQuery->orderBy('created_at', 'DESC');

        $smsMessages = $smsQuery->paginate();

        return $this->sendSuccess([
            'recipients'  => $smsMessages,
            'failed_counts' => SMSMessage::where('campaign_id', $campaignId)->where('status', 'failed')->count()
        ]);
    }

    private function getCampaignSentData($campaignId)
    {
        $campaignSentById = fluentcrm_get_sms_campaign_meta($campaignId, '_sms_campaign_sent_by', true);
        $user = get_userdata($campaignSentById);
        if($user) {
            return $user->display_name . ' (' . $user->user_email . ')';
        }
        return false;
    }

    public function processingStat(Request $request, $campaignId)
    {
        $campaign = SMSCampaign::whereIn('type', fluentCrmAutoProcessSmsCampaignTypes())
            ->findOrFail($campaignId);

        if ($campaign->status == 'pending-scheduled' && (strtotime($campaign->scheduled_at) - current_time('timestamp')) < 360) {
            $campaign->status = 'processing';
            $campaign->recipients_count = 0;
            $campaign->save();
            do_action('fluent_crm/sms_campaign_processing_start', $campaign);

            // Ensure generation is scheduled — AS may not have been set for this campaign
            if (!as_next_scheduled_action('fluentcrm_generate_sms_batch', [$campaignId], 'fluent-crm-sms')) {
                as_schedule_single_action(time(), 'fluentcrm_generate_sms_batch', [$campaignId], 'fluent-crm-sms');
            }
        }

        if ($campaign->status != 'processing') {
//            if ($campaign->status == 'scheduled' && current_time('timestamp') - strtotime($campaign->scheduled_at) > 300) {
//                if (Scheduler::markArchiveCampaigns()) {
//                    $campaign = Campaign::withoutGlobalScope('type')
//                        ->findOrFail($campaignId);
//                }
//            }

            return [
                'reload'   => true,
                'campaign' => $campaign
            ];
        }

        $campaign->scheduling_range = $campaign->rangedScheduleDates();

        $completedCount = SMSMessage::where('campaign_id', $campaignId)
            ->whereIn('status', ['sent', 'delivered', 'failed'])
            ->count();

        if (!$campaign->recipients_count || $campaign->recipients_count < $completedCount) {
            $campaign->recipients_count = $completedCount;
        }

        $sendingType = '';
        if (!empty($campaign->settings) && is_array($campaign->settings)) {
            $sendingType = $campaign->settings['sending_type'] ?? '';
        }

        //will implement later for sms
        // This is the processing status
//        $processor = (new CampaignProcessor($campaign->id));
//        $processedCampaign = $processor->processEmails(30, 10);
//
//        $didRun = false;
//        if ($processedCampaign) {
//            $campaign = $processedCampaign;
//            $didRun = true;
//        }
//
//        $campaign->scheduling_range = $campaign->rangedScheduleDates();
//
//        return [
//            'campaign'          => $campaign,
//            'didRun'            => $didRun,
//            'scheduling_method' => $processor->getSchedulingMethod()
//        ];

        return [
            'reload'            => false,
            'campaign'          => $campaign,
            'didRun'            => false,
            'scheduling_method' => $sendingType
        ];
    }
    public function doTagActions(Request $request, $campaignId)
    {
        $campaign = SMSCampaign::findOrFail($campaignId);

        if ($campaign->status != 'archived') {
            return $this->sendError([
                'message' => __('You can do this action if campaign is in archived status only', 'fluentcampaign-pro')
            ]);
        }

        $this->validate($request->all(), [
            'action_type'     => 'required',
            'tags'            => 'required',
            'activity_type'   => 'required',
            'processing_page' => 'required|integer'
        ]);

        $actionType = $request->get('action_type');
        $tags = $request->get('tags');
        $activityType = $request->get('activity_type');

        $processingPage = intval($request->get('processing_page'));
        $limit = apply_filters('fluent_crm/sms_campaign_action_limit', 50);
        $offset = ($processingPage - 1) * $limit;
        $count = false;

        $smsQuery = SMSMessage::where('campaign_id', $campaignId)
            ->select('subscriber_id')
            ->groupBy('subscriber_id');

        if ($activityType == 'sms_sent') {
            $smsQuery->where('status', 'sent');
        } else if ($activityType == 'sms_failed') {
            $smsQuery->where('status', 'failed');
        } else if ($activityType == 'sms_delivered') {
            $smsQuery->where('delivery_status', 'delivered');
        } else if ($activityType != 'all_recipients') {
            return $this->sendError([
                'message' => __('Invalid activity type selection', 'fluentcampaign-pro')
            ]);
        }

        if ($processingPage == 1) {
            $countQuery = clone $smsQuery;
            $count = $countQuery->distinct()->count('subscriber_id');
        }

        $subscriberIds = $smsQuery->offset($offset)
            ->limit($limit)
            ->get()->pluck('subscriber_id')->toArray();

        $subscribers = Subscriber::whereIn('id', $subscriberIds)
            ->where('status', 'subscribed')
            ->get();

        if ($actionType == 'add_tags') {
            foreach ($subscribers as $subscriber) {
                $subscriber->attachTags($tags);
            }
        } else if ($actionType == 'remove_tags') {
            foreach ($subscribers as $subscriber) {
                $subscriber->detachTags($tags);
            }
        }

        $totalSubscribers = count($subscribers);

        return [
            'processed_page'     => $processingPage,
            'processed_contacts' => $totalSubscribers,
            'has_more'           => !!$totalSubscribers,
            'total_count'        => $count,
        ];
    }

    public function delete(Request $request, $campaignId)
    {
        $campaign = SMSCampaign::findOrFail($campaignId);
        $campaign->deleteCampaignData();
        $campaign->delete();
        do_action('fluent_crm/sms_campaign_deleted', $campaignId);

        return $this->send([
            'success' => true
        ]);
    }
}
