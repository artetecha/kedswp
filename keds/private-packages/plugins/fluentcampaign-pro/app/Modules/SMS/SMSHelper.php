<?php

namespace FluentCampaign\App\Modules\SMS;

use FluentCampaign\App\Modules\SMS\Models\SMSMessage;
use FluentCrm\App\Models\Subscriber;
use FluentCrm\App\Services\Libs\Parser\Parser;

class SMSHelper
{
    const SMS_WEBHOOK_KEY_OPTION = '_fc_sms_webhook_key';

    public static function getSettings()
    {
        $settings = [];

        $is_enabled = fluentcrm_get_option('_fc_sms_enable', 'no');

        $current_provider = fluentcrm_get_option('_fc_sms_provider', '');

        $settings['enabled'] = $is_enabled;
        $settings['sms_provider'] = $current_provider;

        $provider_api_options = self::getApiOptions();

        foreach ($provider_api_options['providers'] as $provider_name => $options) {
            $current_settings = fluentcrm_get_option("_fc_sms_{$provider_name}_settings", []);
            if (is_array($current_settings) && !empty($current_settings)) {
                $settings[$provider_name] = $current_settings;
            } else {
                foreach ($options['fields'] as $key => $value) {
                    $settings[$provider_name][$key] = $value['default'] ?? '';
                }
            }
        }

        if (empty($settings)) {
            $settings = [
                'enabled' => 'no',
            ];
        }

        return $settings;
    }

    public static function getCurrentProvider()
    {
        return fluentcrm_get_option('_fc_sms_provider', '');
    }

    public static function getAllowedProviders()
    {
        return Providers\SMSDriverManager::getSlugs();
    }

    public static function twilioSettings()
    {
        $settings = self::getSettings();
        return [
            'twilio_account_sid' => $settings['twilio']['account_sid'] ?? '',
            'twilio_auth_token' => $settings['twilio']['auth_token'] ?? '',
            'twilio_from_number' => $settings['twilio']['from_number'] ?? ''
        ];
    }

    public static function awsEumSettings()
    {
        $settings = self::getSettings();
        return [
            'aws_eum_access_key' => $settings['aws_end_user_message']['access_key_id'] ?? '',
            'aws_eum_secret_key' => $settings['aws_end_user_message']['secret_access_key'] ?? '',
            'aws_eum_region' => $settings['aws_end_user_message']['region'] ?? 'us-east-1',
            'aws_eum_sender_id' => $settings['aws_end_user_message']['sender_id'] ?? ''
        ];
    }

    public static function clickSendSettings()
    {
        $settings = self::getSettings();
        return [
            'clicksend_username' => $settings['click_send']['username'] ?? '',
            'clicksend_api_key' => $settings['click_send']['api_key'] ?? '',
            'clicksend_from_number' => $settings['click_send']['from_number'] ?? ''
        ];
    }

    public static function isActive()
    {
        $settings = self::getSettings();

        return !empty($settings['enabled']) && $settings['enabled'] === 'yes';
    }

    // NEW: generic accessor to get saved settings for any provider (including custom ones)
    public static function getProviderSettings($provider)
    {
        $all = self::getSettings();
        return $all[$provider] ?? [];
    }

    public static function getApiOptions()
    {
        // Build definitions from registered providers
        $provider_definitions = [];
        foreach (Providers\SMSDriverManager::getAll() as $slug => $provider) {
            $provider_definitions[$slug] = [
                'label'  => $provider->getLabel(),
                'fields' => $provider->getFields(),
            ];
        }

        // Build select options
        $provider_options = [];
        foreach ($provider_definitions as $key => $def) {
            $provider_options[$key] = $def['label'] ?? ucfirst(str_replace('_', ' ', $key));
        }

        $provider_select = [
            'type'    => 'select',
            'label'   => __('SMS Provider', 'fluentcampaign-pro'),
            'options' => $provider_options,
            'default' => array_key_first($provider_options) ?: '',
        ];

        if (empty($provider_select['default']) || !isset($provider_select['options'][$provider_select['default']])) {
            $first = array_key_first($provider_select['options'] ?? []);
            if ($first) {
                $provider_select['default'] = $first;
            }
        }

        return [
            'sms_providers' => $provider_select,
            'providers'     => $provider_definitions,
        ];
    }


    public static function verifyWebhookHash($hash, $provider, $url)
    {
        if (!$hash || !is_string($hash) || strlen($hash) < 16 || !$provider) {
            return false;
        }

        $hashInDb = fluentcrm_get_option(self::SMS_WEBHOOK_KEY_OPTION, '');

        if (!$hashInDb || !is_string($hashInDb)) {
            return false;
        }

        return hash_equals($hashInDb, $hash);
    }

    public static function getSMSWebhookUrl($provider, $create = false)
    {
        if (!$provider) {
            return '';
        }

        $key = fluentcrm_get_option(self::SMS_WEBHOOK_KEY_OPTION, '');
        if (!$key && $create) {
            $key = wp_generate_uuid4();
            fluentcrm_update_option(self::SMS_WEBHOOK_KEY_OPTION, $key);
        }

        return $key && is_string($key) ? add_query_arg([
            'fluentcrm' => '1',
            'route'     => 'webhook',
            'handler'   => 'sms_webhook',
            'provider'  => $provider,
            'hash'      => $key,
        ], site_url('/')) : '';
    }

    public static function optOut($phone, $data = [])
    {
        if (!$phone) {
            return false;
        }

        $subscriber = Subscriber::where('phone', $phone)->first();
        if ($subscriber) {
            $subscriber->sms_status = 'sms_unsubscribed';
            $subscriber->save();

            // Twilio already sends its own compliance reply for STOP/START keywords,
            // so we only queue an extra confirmation SMS for other providers.
            if (($data['provider'] ?? '') !== 'twilio') {
                self::queueStatusConfirmationMessage($subscriber, 'opt_out', $data);
            }

            do_action('fluent_crm/contact_sms_unsubscribed', $subscriber, $data);
            return true;
        }
        return false;
    }

    public static function optIn($phone, $data = [])
    {
        if (!$phone) {
            return false;
        }

        $subscriber = Subscriber::where('phone', $phone)->first();
        if ($subscriber) {
            $subscriber->sms_status = 'sms_subscribed';
            $subscriber->save();

            // Twilio already sends its own compliance reply for STOP/START keywords,
            // so we only queue an extra confirmation SMS for other providers.
            if (($data['provider'] ?? '') !== 'twilio') {
                self::queueStatusConfirmationMessage($subscriber, 'opt_in', $data);
            }

            do_action('fluent_crm/contact_sms_subscribed', $subscriber, $data);
            return true;
        }
        return false;
    }

    protected static function queueStatusConfirmationMessage($subscriber, $event, $data = [])
    {
        /*
        * We are not doing this for Twilio because they already send their own compliance messages for STOP/START keywords,
        * so we don't want to duplicate them. For other providers, we send a confirmation message to the subscriber after they opt-in or opt-out.
        */
        if(($data['provider'] ?? '') === 'twilio') { // extra safety check to prevent duplicate messages in case the optIn/optOut methods are called directly without the provider context
            return false;
        }

        if (!$subscriber || !$subscriber->phone) {
            return false;
        }

        $incomingMessageSid = $data['MessageSid'] ?? $data['SmsSid'] ?? $data['SmsMessageSid'] ?? '';
        $note = $event === 'opt_out' ? 'sms opt-out confirmation' : 'sms opt-in confirmation';

        // Twilio can retry the same inbound webhook. We store the inbound MessageSid
        // on the outgoing SMS record below and use it here to prevent duplicate queueing.
        if ($incomingMessageSid) {
            // TODO: Replace this JSON LIKE dedupe with an indexed equality lookup.
            // `provider_message_id` already exists, but verify it will not be overwritten
            // by the outgoing confirmation SMS provider SID before reusing it here.
            $existingMessage = SMSMessage::where('subscriber_id', $subscriber->id)
                ->where('notes', $note)
                ->where('settings', 'like', '%"incoming_message_sid":"' . $incomingMessageSid . '"%')
                ->first();

            if ($existingMessage) {
                return $existingMessage;
            }
        }

        if ($event === 'opt_out') {
            $messageContent = __('You have been unsubscribed. Reply START to resubscribe.', 'fluentcampaign-pro');
            $messageContent = apply_filters('fluent_crm/sms_opt_out_confirmation_message', $messageContent, $subscriber, $data);
        } else {
            $messageContent = __('You have been subscribed. Reply STOP to unsubscribe.', 'fluentcampaign-pro');
            $messageContent = apply_filters('fluent_crm/sms_opt_in_confirmation_message', $messageContent, $subscriber, $data);
        }

        if (!$messageContent) {
            return false;
        }

        $smsMessage = SMSMessage::create([
            'campaign_id'     => null,
            'sms_type'        => SMSMessage::TYPE_CUSTOM_SMS,
            'subscriber_id'   => $subscriber->id,
            'mobile_number'   => $subscriber->phone,
            'message_content' => $messageContent,
            'status'          => SMSMessage::STATUS_PENDING,
            'scheduled_at'    => current_time('mysql'),
            'created_at'      => current_time('mysql'),
            'updated_at'      => current_time('mysql'),
            'notes'           => $note,
            // Keep the inbound webhook metadata with the outgoing confirmation so
            // we can trace which STOP/START message triggered this queued SMS.
            'settings'        => wp_json_encode([
                'provider'             => $data['provider'] ?? (self::getCurrentProvider() ?: 'unknown'),
                'event'                => $event,
                'incoming_message_sid' => $incomingMessageSid,
            ]),
        ]);

        // Send via the normal Action Scheduler single-send flow so it is tracked
        // the same way as the rest of the plugin's queued SMS messages.
        as_schedule_single_action(
            time(),
            'fluentcrm_send_single_sms',
            [$smsMessage->id],
            'fluent-crm-sms'
        );

        return $smsMessage;
    }

    public static function processIncomingMessage($from, $to, $body, $data = [])
    {
        if (!$from || !$to || !$body) {
            return false;
        }

        $subscriber = Subscriber::where('phone', $from)->first();

        if ($subscriber) {
            $providerMessageId = $data['MessageSid'] ?? $data['SmsSid'] ?? $data['SmsMessageSid'] ?? '';

            // Twilio may retry inbound webhooks, so reuse the existing record when we already
            // saved this incoming message SID instead of creating a duplicate activity entry.
            if ($providerMessageId) {
                $existingMessage = SMSMessage::where('provider_message_id', $providerMessageId)->first();
                if ($existingMessage) {
                    return $existingMessage;
                }
            }

            SMSMessage::create([
                'sms_type' => 'incoming', // it could be inbox , incoming or received
                'subscriber_id' => $subscriber->id,
                'mobile_number' => $to,
                'provider_message_id' => $providerMessageId,
                'message_content' => $body,
                'settings' => json_encode([
                    'from' => $from,
                    'to' => $to,
                    'provider' => $data['provider'] ?? 'unknown',
                ]),
                'notes' => 'incoming message',
                'status' => 'received'
            ]);


            return true;
        }
        return false;
    }

    /**
     * Parse SMS message content with subscriber data.
     *
     * SMS intentionally uses the same shared smartcode parser as email
     * content so contact fields, contact custom fields, CRM values, WP values,
     * and extension-provided smartcode groups resolve from one source of truth.
     * Do not reintroduce a separate SMS allowlist here unless the picker and
     * parser behavior are also updated together.
     *
     * Some CRM link smartcodes are deferred by the shared parser because email
     * templates replace them later in the email pipeline. SMS does not pass
     * through that email-only step, so this method resolves those deferred CRM
     * smartcodes and then converts the final output to plain text before send.
     *
     * @param string $content
     * @param \FluentCrm\App\Models\Subscriber $subscriber
     * @return string
     */
    public static function parseMessageContent($content, $subscriber)
    {
        // Keep SMS aligned with the shared parser instead of maintaining a separate allowlist.
        $content = Parser::parse((string)$content, $subscriber);
        $content = self::parseDeferredCrmSmartCodes($content, $subscriber);
        $content = apply_filters('fluent_crm/parse_sms_smartcode', $content, $subscriber);

        return self::sanitizeParsedMessageContent($content);
    }

    /**
     * Resolve CRM link smartcodes that are intentionally deferred by Parser::parse().
     *
     * The shared parser returns unsubscribe/manage-subscription smartcodes as
     * placeholders because email rendering handles those links at a later stage.
     * SMS messages are sent directly after SMSHelper::parseMessageContent(), so
     * without this pass a user could insert a CRM link smartcode from the SMS
     * picker and still send the raw placeholder to the contact.
     *
     * This method only targets the deferred CRM link codes and leaves all other
     * already-parsed content untouched.
     *
     * @param string $content
     * @param \FluentCrm\App\Models\Subscriber $subscriber
     * @return string
     */
    protected static function parseDeferredCrmSmartCodes($content, $subscriber)
    {
        if (!$subscriber instanceof Subscriber) {
            return $content;
        }

        return preg_replace_callback(
            '/({{|##)\s*crm\.(unsubscribe_url|manage_subscription_url|unsubscribe_html|manage_subscription_html)(.*?)\s*(}}|##)/',
            function ($matches) use ($subscriber) {
                // The shared parser defers these email link codes; SMS needs the resolved text/link.
                return Parser::parseCrmValue($matches[0], $subscriber);
            },
            $content
        );
    }

    /**
     * Normalize parsed smartcode output for plain-text SMS delivery.
     *
     * The shared parser may return HTML for some email-oriented smartcodes,
     * especially CRM hyperlink codes. SMS providers expect text, so links are
     * converted to "Label (url)", line-breaking HTML tags are preserved as
     * newlines, and remaining HTML is stripped. Any unresolved smartcode
     * placeholders are removed as a final safety net to avoid sending raw
     * merge-code syntax to contacts.
     *
     * @param string $content
     * @return string
     */
    protected static function sanitizeParsedMessageContent($content)
    {
        // Preserve link destinations after HTML is stripped for plain-text SMS delivery.
        $content = preg_replace_callback(
            '/<a[^>]*href=[\'"]([^\'"]+)[\'"][^>]*>(.*?)<\/a>/is',
            function ($matches) {
                $url = trim($matches[1]);
                $label = trim(wp_strip_all_tags($matches[2]));

                if ($label === '' || $label === $url) {
                    return $url;
                }

                return $label . ' (' . $url . ')';
            },
            (string)$content
        );

        $content = preg_replace('/<\s*br\s*\/?>/i', "\n", $content);
        $content = preg_replace('/<\/(p|div|li|h[1-6]|tr)>/i', "\n", $content);
        $content = wp_strip_all_tags($content);
        $content = html_entity_decode($content, ENT_QUOTES, 'UTF-8');
        $content = preg_replace('/({{|##).*?(}}|##)/', '', $content);
        $content = preg_replace('/[ \t\x{00A0}]+/u', ' ', $content);
        $content = preg_replace("/[ \t]*\n[ \t]*/", "\n", $content);
        $content = preg_replace("/\n{3,}/", "\n\n", $content);

        return trim($content);
    }
}
