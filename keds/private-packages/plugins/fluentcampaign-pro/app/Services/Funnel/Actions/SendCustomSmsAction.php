<?php

namespace FluentCampaign\App\Services\Funnel\Actions;

use FluentCrm\App\Services\Funnel\BaseAction;
use FluentCrm\App\Services\Funnel\FunnelHelper;
use FluentCrm\Framework\Support\Arr;
use FluentCampaign\App\Modules\SMS\Models\SMSMessage;
use FluentCampaign\App\Modules\SMS\SMSHelper;

class SendCustomSmsAction extends BaseAction
{
    public function __construct()
    {
        $this->actionName = 'send_custom_sms';
        $this->priority = 20;
        parent::__construct();
    }

    public function getBlock()
    {
        return [
            'category' => __('SMS', 'fluentcampaign-pro'),
            'title'       => __('Send a SMS', 'fluentcampaign-pro'),
            'description' => __('Send a SMS to the subscriber or any other contact', 'fluentcampaign-pro'),
            'icon' => 'el-icon-chat-line-square',
            'settings'    => [
                'send_sms_to_type' => 'contact',
                'send_sms_custom'  => '',
                'sms_body' => ''
            ]
        ];
    }

    public function getBlockFields()
    {
        return [
            'title'     => __('Create Custom SMS', 'fluentcampaign-pro'),
            'sub_title' => __('Please provide sms details that you want to send', 'fluentcampaign-pro'),
            'fields'    => [
                'send_sms_to_type' => [
                    'type'          => 'radio',
                    'wrapper_class' => 'fc_half_field',
                    'label'         => __('Send SMS to', 'fluentcampaign-pro'),
                    'options'       => [
                        [
                            'id'    => 'contact',
                            'title' => __('Send To the contact', 'fluentcampaign-pro')
                        ],
                        [
                            'id'    => 'custom',
                            'title' => __('Send to Custom Number', 'fluentcampaign-pro')
                        ]
                    ]
                ],
                'send_sms_custom'  => [
                    'wrapper_class' => 'fc_half_field',
                    'type'          => 'input-text',
                    'label'         => __('Send To Number', 'fluentcampaign-pro'),
                    'placeholder'   => __('Custom Contact Number', 'fluentcampaign-pro'),
                    'dependency'    => [
                        'depends_on' => 'send_sms_to_type',
                        'operator'   => '=',
                        'value'      => 'custom'
                    ]
                ],
                'sms_body'  => [
                    'type' => 'input-text-area',
                    'field_type'  => 'textarea',
                    'rows'          => 4,
                    'label'         => __('SMS Body', 'fluent-crm'),
                    'placeholder'   => __('SMS Body', 'fluent-crm'),
                    'show_sms_counter' => true,
                ]
            ]
        ];
    }

    public function handle($subscriber, $sequence, $funnelSubscriberId, $funnelMetric)
    {
        $data = $sequence->settings;

        $smsSendToOption = Arr::get($data, 'send_sms_to_type');
        $customContactNumber = Arr::get($data, 'send_sms_custom');
        $smsBody = Arr::get($data, 'sms_body');

        if ($smsSendToOption == 'custom') {
            $contactNumber = $customContactNumber;
        } else {
            $contactNumber = $subscriber->phone;
        }

        if (!$contactNumber || !$smsBody) {
            return;
        }

        if (
            $smsSendToOption !== 'custom' &&
            $subscriber->sms_status !== 'sms_subscribed'
        ) {
            return;
        }

        // Parse smartcodes in SMS body
        $smsBody = SMSHelper::parseMessageContent($smsBody, $subscriber);

        // Create SMSMessage record for proper tracking
        $smsMessage = SMSMessage::create([
            'campaign_id'     => null,
            'sms_type'        => SMSMessage::TYPE_AUTOMATION,
            'subscriber_id'   => $subscriber->id,
            'mobile_number'   => $contactNumber,
            'message_content' => $smsBody,
            'status'          => SMSMessage::STATUS_PENDING,
            'scheduled_at'    => current_time('mysql'),
            'created_at'      => current_time('mysql'),
            'updated_at'      => current_time('mysql'),
        ]);

        // Schedule instant send via Action Scheduler
        as_schedule_single_action(
            time(),
            'fluentcrm_send_single_sms',
            [$smsMessage->id],
            'fluent-crm-sms'
        );
    }
}
