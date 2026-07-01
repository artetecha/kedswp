<?php

namespace FluentCampaign\App\Modules\SMS;

class SMSReceiver
{

    public static function handleAwsEumMessages($data)
    {
        
    }

    /**
     * Handle incoming Twilio webhook
     * 
     * @param array $bodyData The sanitized POST data from the webhook
     */
    public static function handleTwilioWebhook($bodyData = [])
    {
        $from = $bodyData['From'] ?? '';
        $to = $bodyData['To'] ?? '';
        $body = isset($bodyData['Body']) ? trim($bodyData['Body']) : '';
        $provider = 'twilio';
        $bodyData['provider'] = $provider;

        if ($from && $to && $body) {

            // Normalize body, lower case etc.
            $body_norm = strtolower($body);

            // Check if message is opt-out
            $isOptOut = false;

            // Define opt-out keywords
            $optOutKeywords = ['stop', 'cancel', 'unsubscribe'];

            if (in_array($body_norm, $optOutKeywords)) {
                $isOptOut = true;
            }

            if ($isOptOut) {
                // Save the inbound STOP/CANCEL message in the activity log before updating the contact.
                SMSHelper::processIncomingMessage($from, $to, $body_norm, $bodyData);

                // Handle opt-out logic here
                $handled = SMSHelper::optOut($from, $bodyData);

                // Known contacts get their confirmation from the queued outbound SMS above.
                // Unknown numbers fall back to an inline TwiML reply so they still get feedback.
                self::respondToTwilio($handled ? '' : 'You have been unsubscribed. Reply START to resubscribe.');
                exit;
            }

            // Check if message is opt-in
            $isOptIn = false;
            $optInKeywords = ['start', 'subscribe'];

            if (in_array($body_norm, $optInKeywords)) {
                $isOptIn = true;
            }

            if ($isOptIn) {
                // Save the inbound START/SUBSCRIBE message in the activity log before updating the contact.
                SMSHelper::processIncomingMessage($from, $to, $body_norm, $bodyData);

                // Handle opt-in logic here
                $handled = SMSHelper::optIn($from, $bodyData);

                // Same fallback pattern as STOP: queue for matched contacts, reply inline otherwise.
                self::respondToTwilio($handled ? '' : 'You have been subscribed. Reply STOP to unsubscribe.');
                exit;
            }

            // Process other incoming messages as needed
            SMSHelper::processIncomingMessage($from, $to, $body_norm, $bodyData);

            return true;
        }

        return false;
    }

    protected static function respondToTwilio($message = '')
    {
        header('Content-Type: text/xml');
        echo '<?xml version="1.0" encoding="UTF-8"?>';
        echo '<Response>';
        if ($message) {
            echo '<Message>' . esc_html($message) . '</Message>';
        }
        echo '</Response>';
    }

    /**
     * Handle custom provider webhook
     * 
     * @param string $provider The provider name
     * @param array $bodyData The sanitized POST data from the webhook
     */
    public static function handleCustomProviderWebhook($provider, $bodyData = [])
    {
        /**
         * Allow custom providers to handle their webhooks
         * 
         * @param array $bodyData The webhook data
         * @param string $provider The provider name
         */
        do_action('fluent_crm_sms_custom_provider_webhook', $bodyData, $provider);
        do_action("fluent_crm_sms_{$provider}_webhook", $bodyData);

        return true;
    }
}
