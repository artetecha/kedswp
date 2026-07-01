<?php

namespace FluentCampaign\App\Services\Integrations\Edd;


use FluentCrm\App\Services\PermissionManager;

class EddInit
{
    /**
     * Register EDD 3 automation, attribution, sync, and admin integrations.
     */
    public function init()
    {
        new \FluentCampaign\App\Services\Integrations\Edd\EddPaymentSuccessTrigger();
        new \FluentCampaign\App\Services\Integrations\Edd\EddOrderSuccessBenchmark();

        if (defined('EDD_SL_VERSION')) {
            new \FluentCampaign\App\Services\Integrations\Edd\EddLicenseExpiredTrigger();
        }

        if (defined('EDD_RECURRING_VERSION')) {
            new \FluentCampaign\App\Services\Integrations\Edd\EddRecurringPaymentTrigger();
            new \FluentCampaign\App\Services\Integrations\Edd\EddRecurringExpired();
            new \FluentCampaign\App\Services\Integrations\Edd\EddSubscriptionActiveBenchmark();
        }


        add_filter('fluent_crm/sales_stats', array($this, 'pushStats'));
        add_action('edd_built_order', array($this, 'maybeCampaignMeta'), 10, 2);
        add_action('edd_transition_order_status', array($this, 'maybeRecordPayment'), 10, 3);

        add_action('edd_view_order_details_sidebar_after', array($this, 'printCrmProfileWidget'));

        if (!apply_filters('fluentcrm_disable_integration_metaboxes', false, 'edd')) {
            (new \FluentCampaign\App\Services\Integrations\Edd\EddMetaBoxes())->init();
        }

        (new EddImporter)->init();
        (new DeepIntegration())->init();
        (new EddSmartCodeParse())->init();

    }

    public function pushStats($stats)
    {
        if (current_user_can(apply_filters('edd_dashboard_stats_cap', 'view_shop_reports'))) {

            $eddStat = new \EDD_Payment_Stats;
            $eddStats = [
                [
                    'title'   => __('Earnings (Today)', 'fluentcampaign-pro'),
                    'content' => edd_currency_filter(edd_format_amount($eddStat->get_earnings(0, 'today')))
                ],
                [
                    'title'   => __('Earnings (Current Month)', 'fluentcampaign-pro'),
                    'content' => edd_currency_filter(edd_format_amount($eddStat->get_earnings(0, 'this_month')))
                ],
                [
                    'title'   => __('Earnings (All Time)', 'fluentcampaign-pro'),
                    'content' => edd_currency_filter(edd_format_amount(edd_get_total_earnings()))
                ]
            ];
            $stats = array_merge($stats, $eddStats);
        }
        return $stats;
    }

    /**
     * Store FluentCRM campaign attribution on the EDD 3 order meta table.
     */
    public function maybeCampaignMeta($orderId, $orderData)
    {
        if (isset($_COOKIE['fc_cid'])) {
            $campaignId = intval($_COOKIE['fc_cid']);
            if ($campaignId) {
                edd_update_order_meta($orderId, '_fc_cid', $campaignId);
            }
        }

        if (isset($_COOKIE['fc_sid'])) {
            $subscriberId = intval($_COOKIE['fc_sid']);
            if ($subscriberId) {
                edd_update_order_meta($orderId, '_fc_sid', $subscriberId);
            }
        }
    }

    /**
     * Record campaign revenue when an EDD 3 order transitions to a paid status.
     */
    public function maybeRecordPayment($oldStatus, $newStatus, $orderId)
    {
        if ($newStatus == $oldStatus) {
            return;
        }

        $orderId = absint($orderId);
        if (!$orderId) {
            return;
        }

        $successStatuses = ['complete', 'completed'];

        if (in_array($newStatus, $successStatuses)) {
            $campaignId = edd_get_order_meta($orderId, '_fc_cid', true);
            if ($campaignId) {
                $order = edd_get_order($orderId);
                if (!$order) {
                    return;
                }

                // Fast path for orders already recorded by an earlier request.
                if (edd_get_order_meta($orderId, '_fc_revenue_recorded', true) == 'yes') {
                    return;
                }

                $paymentTotal = $order->total * 100;

                if (!method_exists('\FluentCrm\App\Services\Helper', 'recordCampaignRevenue')) {
                    return;
                }

                global $wpdb;

                // Campaign revenue is read-modify-write, so serialize it per EDD order.
                $lockName = 'fcrm_edd_rev_' . $orderId;
                if ($wpdb->get_var($wpdb->prepare('SELECT GET_LOCK(%s, 5)', $lockName)) != 1) {
                    return;
                }

                try {
                    // Re-check after waiting for the lock; another request may have recorded it.
                    if (edd_get_order_meta($orderId, '_fc_revenue_recorded', true) == 'yes') {
                        return;
                    }

                    $recorded = \FluentCrm\App\Services\Helper::recordCampaignRevenue($campaignId, $paymentTotal, $orderId, $order->currency);

                    if (!$recorded) {
                        return;
                    }

                    // Mark the order after revenue meta is saved so failed writes can be retried.
                    edd_update_order_meta($orderId, '_fc_revenue_recorded', 'yes');
                } finally {
                    // Release the named lock for this DB connection even when the write path returns early.
                    $wpdb->get_var($wpdb->prepare('SELECT RELEASE_LOCK(%s)', $lockName));
                }
            }
        }
    }

    public function printCrmProfileWidget($paymentId)
    {
        $hasPermission = apply_filters('fluent_crm/can_view_contact_card_in_plugin', PermissionManager::currentUserCan('fcrm_read_contacts'), 'edd');

        if (!$hasPermission) {
            return;
        }

        $payment = edd_get_payment($paymentId);
        $userId = $payment->user_id;
        if (!$userId) {
            $userId = $payment->email;
        }

        $profileHtml = fluentcrm_get_crm_profile_html($userId, false);

        if (!$profileHtml) {
            return;
        }

        ?>
        <div id="fc-profile" class="postbox edd-fc-profile">
            <h3 class="hndle">
                <span><?php _e('FluentCRM Profile', 'fluentcampaign-pro'); ?></span>
            </h3>
            <div class="inside">
                <?php echo $profileHtml; ?>
            </div>
        </div>
        <?php
    }
}
