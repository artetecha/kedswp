<?php

namespace FluentCampaign\App\Services\Integrations\WooCommerce;

use FluentCrm\App\Models\Subscriber;
use FluentCrm\App\Services\AutoSubscribe;
use FluentCrm\App\Services\Funnel\FunnelHelper;
use FluentCrm\App\Services\PermissionManager;
use FluentCrm\Framework\Support\Arr;

class WooInit
{
    public function init()
    {
        new \FluentCampaign\App\Services\Integrations\WooCommerce\WooOrderSuccessTrigger();
        new \FluentCampaign\App\Services\Integrations\WooCommerce\WooOrderSuccessBenchmark();
        new \FluentCampaign\App\Services\Integrations\WooCommerce\WooOrderCompletedTrigger();
        new \FluentCampaign\App\Services\Integrations\WooCommerce\WooOrderStatusChangeTrigger();
        new \FluentCampaign\App\Services\Integrations\WooCommerce\WooOrderRefundedTrigger();
        new \FluentCampaign\App\Services\Integrations\WooCommerce\OrderStatusChangeAction();
        new \FluentCampaign\App\Services\Integrations\WooCommerce\AddOrderNoteAction();
        new \FluentCampaign\App\Services\Integrations\WooCommerce\CreateCouponAction();

        /*
         * Dynamically create coupon smartcode for CreateCouponAction
         */
        add_action('fluent_crm/sequence_created_fcrm_create_woo_coupon', function ($createdSequence) {
            $settings = $createdSequence->settings;
            $settings['code_settings']['smart_code'] = '{{woo_coupon.' . $createdSequence->funnel_id . '_' . $createdSequence->id . '}}';
            $createdSequence->settings = $settings;
            $createdSequence->save();
        });

        add_filter('fluent_crm/admin_vars', function ($vars) {
            $vars['woo_currency_sign'] = get_woocommerce_currency_symbol();
            return $vars;
        });

        add_filter('fluent_crm/sales_stats', array($this, 'pushStatus'));

        add_action('woocommerce_new_order', [$this, 'maybeCampaignMeta'], 10, 2);

        add_action('woocommerce_order_status_changed', function ($orderId, $from, $to, $order) {
            if (!$order instanceof \WC_Order) {
                $order = wc_get_order($orderId);
            }

            if (!$order instanceof \WC_Order) {
                return;
            }

            // check if paid statuses
            $paidStatuses = wc_get_is_paid_statuses();
            if (!in_array($to, $paidStatuses)) {
                return;
            }

            $this->maybeRecordPayment($orderId, $order);
        }, 10, 4);

        add_action('add_meta_boxes', array($this, 'maybeAddOrderWidget'), 99, 2);

        add_filter('woocommerce_checkout_fields', array($this, 'addSubscribeBox'), 1, 100);
        add_action('woocommerce_checkout_update_order_meta', array($this, 'maybeSubscriptionChecked'), 99, 2);

        add_action('before_delete_post', array($this, 'updateCampaignRevenue'), 10, 1);

        (new WooProductAdmin())->init();

        (new DeepIntegration())->init();
        new WooImporter();

        (new AutomationConditions())->init();
        (new WooSmartCodeParse())->init();

        if (defined('WCS_INIT_TIMESTAMP')) {
            new WooSubscriptionStartedTrigger();
            new WooSubscriptionRenewalPaymentTrigger();
            new WooSubscriptionRenewalFailedTrigger();
            new WooSubscriptionExpiredTrigger();
            new WooSubscriptionCancelledTrigger();
            add_filter('fluent_crm/subscriber_top_widgets', array($this, 'pushSubscriptionWidgets'), 10, 2);
        }

        // This class boots on `init` priority 1, after Woo may have already fired `woocommerce_init` (priority 0).
        $hasWooInitFired = did_action('woocommerce_init') > 0;
        // Blocks bootstrap can fire separately; track it independently so we can keep a fallback hook.
        $hasBlocksLoadedFired = did_action('woocommerce_blocks_loaded') > 0;

        // Attempt immediate registration for already-fired hooks.
        if ($hasWooInitFired || $hasBlocksLoadedFired) {
            $this->addSubscribeBoxForBlockCheckout();
        }

        // If Woo init has not fired yet, register a fallback callback.
        if (!$hasWooInitFired) {
            add_action('woocommerce_init', [$this, 'addSubscribeBoxForBlockCheckout']);
        }

        // Always keep a blocks-loaded fallback when it has not fired yet.
        if (!$hasBlocksLoadedFired) {
            add_action('woocommerce_blocks_loaded', [$this, 'addSubscribeBoxForBlockCheckout']);
        }

        add_action('woocommerce_store_api_checkout_update_order_from_request', function ($order, $request) {
            $this->captureBlockCheckoutOptinState($order, $request);
            // Process opt-in here so pending contacts are created before later order-status sync hooks run.
            $this->maybeHandleBlockCheckoutSubscription($order);
        }, 20, 2);

        add_action('woocommerce_store_api_checkout_order_processed', function ($order) {
            $this->maybeCampaignMeta($order->get_id(), $order);
            $this->maybeHandleBlockCheckoutSubscription($order);
        });

        add_action('woocommerce_payment_complete', function ($orderId) {
            // need to update the campaign revenue here for block based checkout
            $order = wc_get_order($orderId);
            if (!$order instanceof \WC_Order) {
                return;
            }

            $this->maybeRecordPayment($orderId, $order);
            $this->maybeHandleBlockCheckoutSubscription($order);
        });
    }

    /**
     * Add WooCommerce sales totals to the FluentCRM dashboard stats list.
     *
     * The dashboard expects each sales stat as a title/content pair. Keep the
     * existing labels stable while delegating the actual total calculation to
     * helpers that can support HPOS, modern non-HPOS stores, and legacy Woo.
     */
    public function pushStatus($stats)
    {
        if (current_user_can('view_woocommerce_reports') || current_user_can('manage_woocommerce') || current_user_can('publish_shop_orders')) {

            $todaySales = $this->getWooSalesByRange($this->getWooDateRange('today'));
            $monthSales = $this->getWooSalesByRange($this->getWooDateRange('month'));

            $wooStats = [
                [
                    'title'   => __('Sales (Today)', 'fluentcampaign-pro'),
                    'content' => wc_price($todaySales)
                ],
                [
                    'title'   => __('Sales (This Month)', 'fluentcampaign-pro'),
                    'content' => wc_price($monthSales)
                ]
            ];
            $stats = array_merge($stats, $wooStats);
        }
        return $stats;
    }

    /**
     * Build a WordPress-timezone date range for WooCommerce dashboard sales.
     *
     * WooCommerce stores order dates in MySQL datetime strings, so this returns
     * the exact lower and upper bounds used by the stats query. The month range
     * starts on the first day of the current month and ends on the current day.
     */
    private function getWooDateRange($period)
    {
        $currentTimestamp = current_time('timestamp');

        if ($period == 'month') {
            $startDate = gmdate('Y-m-01 00:00:00', $currentTimestamp);
        } else {
            $startDate = gmdate('Y-m-d 00:00:00', $currentTimestamp);
        }

        return [
            'start' => $startDate,
            'end'   => gmdate('Y-m-d 23:59:59', $currentTimestamp)
        ];
    }

    /**
     * Get WooCommerce report statuses normalized for the wc_order_stats table.
     *
     * WooCommerce exposes reportable statuses without the wc- prefix through
     * the woocommerce_reports_order_statuses filter. The order stats table stores
     * the prefixed values, so normalize them before building the SQL condition.
     */
    private function getWooReportStatuses()
    {
        $statuses = apply_filters('woocommerce_reports_order_statuses', ['completed', 'processing', 'on-hold']);

        if (!$statuses || !is_array($statuses)) {
            $statuses = ['completed', 'processing', 'on-hold'];
        }

        $normalizedStatuses = [];
        foreach ($statuses as $status) {
            $status = sanitize_key($status);

            if (!$status) {
                continue;
            }

            if (strpos($status, 'wc-') !== 0) {
                $status = 'wc-' . $status;
            }

            $normalizedStatuses[] = $status;
        }

        return array_values(array_unique($normalizedStatuses));
    }

    /**
     * Get the WooCommerce analytics date column allowed for order stats queries.
     *
     * WooCommerce stores the selected analytics date type in an option. Only
     * known wc_order_stats date columns are allowed here because the column name
     * must be interpolated into SQL after validation.
     */
    private function getWooStatsDateColumn()
    {
        $dateColumn = get_option('woocommerce_date_type', 'date_paid');
        $allowedColumns = ['date_created', 'date_paid', 'date_completed'];

        if (!in_array($dateColumn, $allowedColumns, true)) {
            return 'date_paid';
        }

        return $dateColumn;
    }

    /**
     * Get WooCommerce net sales from the modern order stats table.
     *
     * The wc_order_stats table is the preferred source for HPOS and modern
     * WooCommerce analytics. If the table is missing, incomplete, or not synced
     * yet, fall back to the legacy reports API so older installs still work.
     */
    private function getWooSalesByRange(array $range)
    {
        global $wpdb;

        $tableName = $wpdb->prefix . 'wc_order_stats';

        // Prefer WooCommerce's modern analytics table, but keep old installs supported.
        $tableExists = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $tableName));

        if ($tableExists != $tableName) {
            return $this->getLegacyWooSalesByRange($range);
        }

        // Guard required columns so older or partially synced stores do not fatal.
        $columns = $wpdb->get_col('DESC ' . $tableName, 0);
        $requiredColumns = ['net_total', 'status', 'date_paid', 'date_completed', 'date_created'];

        if (!$columns || array_diff($requiredColumns, $columns)) {
            return $this->getLegacyWooSalesByRange($range);
        }

        // Empty analytics tables can happen before Woo sync; check existence without scanning all rows.
        $hasOrderStats = absint($wpdb->get_var('SELECT 1 FROM ' . $tableName . ' LIMIT 1'));
        if (!$hasOrderStats) {
            return $this->getLegacyWooSalesByRange($range);
        }

        // Match WooCommerce report status customization before querying stats.
        $statuses = $this->getWooReportStatuses();
        if (!$statuses) {
            return $this->getLegacyWooSalesByRange($range);
        }

        $statusPlaceholders = implode(', ', array_fill(0, count($statuses), '%s'));
        $dateColumn = $this->getWooStatsDateColumn();

        // Query the validated analytics date column directly so MySQL can use Woo's date index.
        $sql = "SELECT COALESCE(SUM(net_total), 0) FROM {$tableName} WHERE status IN ({$statusPlaceholders}) AND {$dateColumn} >= %s AND {$dateColumn} <= %s";
        $queryParams = array_merge($statuses, [$range['start'], $range['end']]);

        // Prepare the dynamic status placeholders and date bounds as one safe query.
        $preparedSql = call_user_func_array([$wpdb, 'prepare'], array_merge([$sql], $queryParams));

        return (float) $wpdb->get_var($preparedSql);
    }

    /**
     * Get WooCommerce net sales using the legacy reports API.
     *
     * This is only used as a compatibility fallback when wc_order_stats cannot
     * be queried safely. It preserves the previous dashboard behavior for old
     * WooCommerce installs that still rely on WC_Report_Sales_By_Date.
     */
    private function getLegacyWooSalesByRange(array $range)
    {
        if (!class_exists('\WC_Report_Sales_By_Date')) {
            if (!function_exists('WC')) {
                return 0;
            }

            include_once(WC()->plugin_path() . '/includes/admin/reports/class-wc-admin-report.php');
            include_once(WC()->plugin_path() . '/includes/admin/reports/class-wc-report-sales-by-date.php');
        }

        if (!class_exists('\WC_Report_Sales_By_Date')) {
            return 0;
        }

        $salesQuery = new \WC_Report_Sales_By_Date();
        $salesQuery->start_date = strtotime($range['start']);
        $salesQuery->end_date = strtotime($range['end']);
        $salesQuery->chart_groupby = 'month';
        $salesQuery->group_by_query = 'YEAR(posts.post_date), MONTH(posts.post_date), DAY(posts.post_date)';
        $salesData = $salesQuery->get_report_data();

        return (float) $salesData->net_sales;
    }

    public function maybeCampaignMeta($orderId, $order)
    {
        if (!isset($_COOKIE['fc_cid'])) {
            return false;
        }

        if (!$order instanceof \WC_Order) {
            $order = wc_get_order($orderId);
        }

        if (!$order instanceof \WC_Order) {
            return false;
        }

        $campaignId = intval($_COOKIE['fc_cid']);
        if ($campaignId) {
            $order->update_meta_data('_fc_cid', $campaignId);
            $order->save();
        }
    }


    /**
     * @param $orderId int
     * @param $order \WC_Order
     */
    public function maybeRecordPayment($orderId, $order)
    {
        $campaignId = $order->get_meta('_fc_cid');

        if ($campaignId) {
            if ($order->get_meta('_fc_revenue_recorded') == 'yes') {
                return;
            }

            $order->update_meta_data('_fc_revenue_recorded', 'yes');
            $order->save();
            $paymentTotal = intval($order->get_total() * 100);
            \FluentCrm\App\Services\Helper::recordCampaignRevenue($campaignId, $paymentTotal, $orderId, $order->get_currency());
        }
    }

    public function maybeAddOrderWidget($postType, $post)
    {
        if (!in_array($postType, ['woocommerce_page_wc-orders', 'shop_order'])) {
            return;
        }

        $hasPermission = apply_filters('fluent_crm/can_view_contact_card_in_plugin', PermissionManager::currentUserCan('fcrm_read_contacts'), 'woo');

        if (!$hasPermission) {
            return;
        }

        if ($postType == 'woocommerce_page_wc-orders') {
            $orderId = $post->get_id(); // for WooCommerce High Performance Order storage
        } else {
            $orderId = $post->ID; // for WordPress post tables $postType = 'shop_order';
        }
        $order = wc_get_order($orderId);

        $userId = $order->get_user_id();
        if (!$userId) {
            $userId = $order->get_billing_email();
        }

        $profileHtml = fluentcrm_get_crm_profile_html($userId, false);

        if (!$profileHtml) {
            return;
        }

        add_meta_box('fluentcrm_woo_order_widget', __('FluentCRM Profile', 'fluentcampaign-pro'), function () use ($profileHtml) {
            echo $profileHtml;
        }, $postType, 'side', 'low');
    }


    public function updateCampaignRevenue($post_ID)
    {
        $type = get_post_type($post_ID);
        if ($type !== 'shop_order') {
            return;
        }

        $order = wc_get_order($post_ID);
        if (empty($order)) {
            return;
        }

        $campaignId = $order->get_meta('_fc_cid');
        if (!$campaignId) {
            return;
        }

        if ($order->get_meta('_fc_revenue_recorded') == 'yes') {
            return;
        }

        $existing = fluentcrm_get_campaign_meta($campaignId, '_campaign_revenue');
        if (empty($existing)) {
            return;
        }

        $currency = strtolower($order->get_currency());
        $orderTotal = intval($order->get_total() * 100);
        $orderId = $order->get_id();

        $currentValue = $existing->value;
        $currentValue[$currency] -= $orderTotal;

        $key = array_search($orderId, $currentValue['orderIds']);

        if ($key !== false) {
            unset($currentValue['orderIds'][$key]);
            $currentValue['orderIds'] = array_values($currentValue['orderIds']);
        }

        fluentcrm_update_campaign_meta($campaignId, '_campaign_revenue', $currentValue);
    }

    public function addSubscribeBox($fields)
    {
        $settings = fluentcrm_get_option('woo_checkout_form_subscribe_settings', []);

        if (!$settings || Arr::get($settings, 'status') != 'yes') {
            return $fields;
        }

        if (Arr::get($settings, 'show_only_new') == 'yes') {
            $contact = fluentcrm_get_current_contact();
            if ($contact && $contact->status == 'subscribed') {
                return $fields;
            }
        }

        $heading = Arr::get($settings, 'checkbox_label');

        $defaultValue = \WC()->checkout->get_value('_fc_woo_checkout_subscribe');

        if (Arr::get($settings, 'auto_checked') == 'yes') {
            $defaultValue = '1';
        }

        $ordersFields = Arr::get($fields, 'order', []);

        $checkboxField = [
            '_fc_woo_checkout_subscribe' => array(
                'type'          => 'checkbox',
                'label_class'   => 'fc_woo',
                'class'         => array('input-checkbox', 'fc_subscribe_woo'),
                'label'         => $heading,
                'checked_value' => '1',
                'default'       => $defaultValue
            )
        ];

        // add the checkbox field to the begining of $ordersFields
        $fields['order'] = array_merge($checkboxField, $ordersFields);

        $fields = apply_filters('fluent_crm/woo_checkout_fields', $fields);

        return $fields;
    }

    public function addSubscribeBoxForBlockCheckout()
    {
        static $isRegistered = false;

        if ($isRegistered) {
            return;
        }

        // Support both stable and legacy Woo Blocks field registration APIs.
        $registerField = null;

        if (function_exists('woocommerce_register_additional_checkout_field')) {
            $registerField = 'woocommerce_register_additional_checkout_field';
        } elseif (function_exists('__experimental_woocommerce_blocks_register_checkout_field')) {
            $registerField = '__experimental_woocommerce_blocks_register_checkout_field';
        }

        if (!$registerField) {
            // Do not lock registration here; `woocommerce_blocks_loaded` may make the API available later.
            return;
        }

        $settings = fluentCrmGetFromCache('woo_checkout_form_subscribe_settings', function () {
            return (new AutoSubscribe())->getWooCheckoutSettings();
        }, 86400);

        if (!$settings || Arr::get($settings, 'status') != 'yes') {
            // Deterministic in-request outcome; skip duplicate work if the second hook runs.
            $isRegistered = true;
            return;
        }

        if (Arr::get($settings, 'show_only_new') == 'yes') {
            $contact = fluentcrm_get_current_contact();
            if ($contact && $contact->status == 'subscribed') {
                // Deterministic in-request outcome; no need to re-evaluate on the fallback hook.
                $isRegistered = true;
                return;
            }
        }

        $isChecked = Arr::get($settings, 'auto_checked') == 'yes';

        add_filter('woocommerce_get_default_value_for_fluent-crm/_fc_woo_checkout_subscribe', function ($value) use ($isChecked) {
            if ($value) {
                return $value;
            }

            // Woo expects checkbox defaults as booleans in checkout fields service.
            return (bool)$isChecked;
        }, 10, 1);

        call_user_func($registerField, array(
            'id'                         => 'fluent-crm/_fc_woo_checkout_subscribe',          // Unique ID (use your theme/plugin namespace)
            'label'                      => Arr::get($settings, 'checkbox_label'),
            'location'                   => apply_filters('fluent_crm/woo_block_checkout_consent_position', 'order'),
            'type'                       => 'checkbox',
            'required'                   => false,
            'sanitize_callback'          => function ($value) {
                return (int) $value;
            },
            'show_in_order_confirmation' => false
        ));

        $isRegistered = true;
    }

    public function maybeSubscriptionChecked($orderId, $postedData)
    {
        $isChecked = Arr::get($postedData, '_fc_woo_checkout_subscribe') == 1;
        do_action('fluent_crm/before_woo_checkout_check', $isChecked, $orderId);

        if (!$isChecked) {
            return false;
        }

        $settings = (new AutoSubscribe())->getWooCheckoutSettings();
        if (!$settings || Arr::get($settings, 'status') != 'yes') {
            return false;
        }

        $order = wc_get_order($orderId);
        if (!$order instanceof \WC_Order) {
            return false;
        }

        return $this->addContactFromOrderOptin($order, $settings);
    }

    protected function maybeHandleBlockCheckoutSubscription($order)
    {
        if (!$order instanceof \WC_Order) {
            return false;
        }

        if ($order->get_meta('_fc_woo_checkout_optin_processed') === 'yes') {
            return true;
        }

        $optinValue = $order->get_meta('_fc_woo_checkout_subscribe', true);

        // Backward compatibility: fall back to Woo additional-fields meta if our normalized key is missing.
        if ($optinValue === '') {
            $optinValue = $order->get_meta('_wc_other/fluent-crm/_fc_woo_checkout_subscribe', true);
        }

        if ((string)$optinValue !== '1') {
            return false;
        }

        $processed = $this->maybeSubscriptionChecked($order->get_id(), [
            '_fc_woo_checkout_subscribe' => 1
        ]);

        if ($processed) {
            $order->update_meta_data('_fc_woo_checkout_optin_processed', 'yes');
            $order->save();
        }

        return (bool)$processed;
    }

    protected function captureBlockCheckoutOptinState($order, $request)
    {
        if (!$order instanceof \WC_Order) {
            return;
        }

        $additionalFields = [];

        if ($request instanceof \WP_REST_Request) {
            $additionalFields = (array)$request->get_param('additional_fields');
        } elseif (is_array($request)) {
            $additionalFields = (array)Arr::get($request, 'additional_fields', []);
        }

        $rawValue = Arr::get($additionalFields, 'fluent-crm/_fc_woo_checkout_subscribe', null);

        // Treat missing/empty as unchecked to avoid stale truthy values from previous draft/order state.
        $isChecked = in_array($rawValue, [1, '1', true, 'true', 'yes', 'on'], true);

        $order->update_meta_data('_fc_woo_checkout_subscribe', $isChecked ? '1' : '0');
    }

    protected function addContactFromOrderOptin($order, $settings = null)
    {
        if (!$settings) {
            $settings = (new AutoSubscribe())->getWooCheckoutSettings();
        }

        $subscriberData = Helper::prepareSubscriberData($order);

        if ($listId = Arr::get($settings, 'target_list')) {
            $subscriberData['lists'] = [$listId];
        }

        if ($tags = Arr::get($settings, 'target_tags')) {
            $subscriberData['tags'] = $tags;
        }

        $isDoubleOptin = Arr::get($settings, 'double_optin') == 'yes';

        if ($isDoubleOptin) {
            $subscriberData['status'] = 'pending';
        } else {
            $subscriberData['status'] = 'subscribed';
        }

        $subscriberData = apply_filters('fluent_crm/woo_checkout_auto_subscribe_data', $subscriberData, $order);
        $contact = FunnelHelper::createOrUpdateContact($subscriberData);

        if (!$contact) {
            return false;
        }

        if ($contact->status == 'pending') {
            $contact->sendDoubleOptinEmail();
        }

        return true;
    }

    public function pushSubscriptionWidgets($widgets, $subscriber)
    {
        if (!$subscriber->user_id || apply_filters('fluent_crm/disable_woo_subscriptions_widget', false, $subscriber)) {
            return $widgets;
        }

        if (!function_exists('wcs_get_users_subscriptions')) {
            return $widgets;
        }

        $subscriptions = wcs_get_users_subscriptions($subscriber->user_id);

        if (!$subscriptions) {
            return $widgets;
        }

        $html = '<div class="max_height_340"><ul class="fc_full_listed">';

        foreach ($subscriptions as $subscription) {

            $statusName = wcs_get_subscription_status_name($subscription->get_status());

            $items = $subscription->get_items();
            $names = [];
            foreach ($items as $item) {
                $names[] = $item->get_name();
            }

            $name = implode(' & ', $names);

            $pricing = $subscription->get_formatted_order_total();

            $html .= '<li><span style="font-weight: bold;">' . $name . ' <span class="subscription-status status-' . $subscription->get_status() . '">' . $statusName . '</span></span>';

            $startDate = sprintf('<time class="%s" title="%s">%s</time>', esc_attr('start_date'), esc_attr(date(__('Y/m/d g:i:s A', 'woocommerce-subscriptions'), $subscription->get_time('start_date', 'site'))), esc_html($subscription->get_date_to_display('start_date')));

            $html .= '<p style="margin: 5px 0 0;font-size: 12px;color: #5e5d5d;">' . $pricing . '<span class="fc_middot">·</span>Started at: ' . $startDate;

            if ($nextDate = $subscription->get_time('next_payment_date', 'site')) {
                $html .= '<span class="fc_middot">·</span>Next Payment: ' . sprintf('<time class="%s" title="%s">%s</time>', esc_attr('next_payment_date'), esc_attr(date(__('Y/m/d g:i:s A', 'woocommerce-subscriptions'), $nextDate)), esc_html($subscription->get_date_to_display('next_payment_date')));
            }


            $html .= '</p></li>';
        }
        $html .= '</ul></div>';

        $html .= '<style>.subscription-status {font-weight: normal; display: inline-flex;color: #777;background: #e5e5e5;border-radius: 4px;border-bottom: 1px solid rgba(0,0,0,.05);white-space: nowrap;max-width: 100%;padding: 0px 7px; }.subscription-status.status-active { background: #c6e1c6;color: #5b841b;}.status-cancelled{ background: #9d0303;color: white; }.subscription-status.status-expired {background: #bd94af;color: #724663;}.subscription-status.status-pending-cancel {background: #bfbfbf;color: #737373;}</style>';

        $widgets[] = [
            'title'   => sprintf(__('Woo Subscriptions (%d)', 'fluentcampaign-pro'), count($subscriptions)),
            'content' => $html
        ];

        return $widgets;

    }
}
