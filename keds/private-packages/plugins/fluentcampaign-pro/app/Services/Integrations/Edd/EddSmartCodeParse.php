<?php

namespace FluentCampaign\App\Services\Integrations\Edd;

use FluentCampaign\App\Services\Commerce\Commerce;
use FluentCampaign\App\Services\Commerce\ContactRelationItemsModel;
use FluentCampaign\App\Services\Commerce\ContactRelationModel;
use FluentCrm\App\Models\FunnelSubscriber;

class EddSmartCodeParse
{
    public function init()
    {
        add_filter('fluent_crm/smartcode_group_callback_edd_customer', array($this, 'parseCustomer'), 10, 4);
        add_filter('fluent_crm/smartcode_group_callback_edd_order', array($this, 'parseCurrentOrder'), 10, 4);
        add_filter('fluent_crm/smartcode_group_callback_edd_license', array($this, 'parseLicenseCodes'), 10, 4);

        add_filter('fluent_crm/extended_smart_codes', array($this, 'pushGeneralCodes'));
        add_filter('fluent_crm_funnel_context_smart_codes', array($this, 'pushContextCodes'), 10, 2);
    }

    public function parseCustomer($code, $valueKey, $defaultValue, $subscriber)
    {
        $userId = $subscriber->getWpUserId();

        $customer = false;

        if ($userId) {
            $customer = fluentCrmDb()->table('edd_customers')
                ->where('user_id', $userId)
                ->first();
        }

        if (!$customer) {
            $customer = fluentCrmDb()->table('edd_customers')
                ->where('email', $subscriber->email)
                ->first();
        }

        if ($customer) {
            switch ($valueKey) {
                case 'total_order_count':
                    return $customer->purchase_count;
                case 'total_spent':
                    return edd_format_amount($customer->purchase_value);
            }
        }

        if (!Commerce::isEnabled('edd')) {
            return $defaultValue;
        }

        $commerce = ContactRelationModel::provider('edd')->where('subscriber_id', $subscriber->id)->first();

        if (!$commerce) {
            return $defaultValue;
        }

        switch ($valueKey) {
            case 'first_order_date':
            case 'last_order_date':
                return date_i18n(get_option('date_format'), strtotime($commerce->{$valueKey}));
            case 'total_order_count':
                return $commerce->total_order_count;
            case 'total_spent':
                return edd_format_amount($commerce->total_order_value);
        }

        return $defaultValue;
    }

    public function parseLastOrder($code, $valueKey, $defaultValue, $subscriber)
    {
        $lastOrder = false;
        if (Commerce::isEnabled('edd')) {
            $lastItem = ContactRelationItemsModel::provider('edd')
                ->where('subscriber_id', $subscriber->id)
                ->orderBy('origin_id', 'DESC')
                ->first();
            if ($lastItem && $lastItem->origin_id) {
                $lastOrder = $this->getOrder($lastItem->origin_id);
            } else {
                return $defaultValue;
            }
        } else {
            return $defaultValue;
        }

        if (!$lastOrder || !$lastOrder->id) {
            return $defaultValue;
        }

        return $this->parseOrderProps($lastOrder, $valueKey, $defaultValue);
    }

    public function parseCurrentOrder($code, $valueKey, $defaultValue, $subscriber)
    {
        if (empty($subscriber->funnel_subscriber_id)) {
            return $this->parseLastOrder($code, $valueKey, $defaultValue, $subscriber);
        }

        $funnelSub = FunnelSubscriber::where('id', $subscriber->funnel_subscriber_id)->first();

        if (!$funnelSub || !$funnelSub->source_ref_id || !Helper::isEddTrigger($funnelSub->source_trigger_name)) {
            return $this->parseLastOrder($code, $valueKey, $defaultValue, $subscriber);
        }

        $order = $this->getOrder($funnelSub->source_ref_id);

        if (!$order || !$order->id) {
            return $defaultValue;
        }

        return $this->parseOrderProps($order, $valueKey, $defaultValue);
    }

    public function parseLicenseCodes($code, $valueKey, $defaultValue, $subscriber)
    {
        if (!defined('EDD_SL_VERSION')) {
            return $defaultValue;
        }

        if (empty($subscriber->funnel_subscriber_id)) {
            return $defaultValue;
        }

        $funnelSub = FunnelSubscriber::where('id', $subscriber->funnel_subscriber_id)->first();
        if (!$funnelSub || !$funnelSub->source_ref_id) {
            return $defaultValue;
        }

        $licenseId = $funnelSub->source_ref_id;

        $license = edd_software_licensing()->get_license($licenseId);

        if (!$license || !$license->ID) {
            return $defaultValue;
        }

        switch ($valueKey) {
            case 'license_key':
                return $license->license_key;
            case 'product_name':
                if ($license->download_id && $product = get_post($license->download_id)) {
                    return $product->post_title;
                }
                return $defaultValue;
            case 'product_id':
                return $license->download_id;
            case 'expire_date':
                if ($license->expiration) {
                    return date_i18n(get_option('date_format'), strtotime($license->expiration));
                }
                return 'lifetime';
            case 'renew_url':
                return $license->get_renewal_url();
        }

        return $defaultValue;
    }

    public function pushGeneralCodes($codes)
    {
        $codes['edd_customer'] = [
            'key'        => 'edd_customer',
            'title'      => 'Edd Customer',
            'shortcodes' => $this->getSmartCodes()
        ];

        return $codes;
    }

    public function pushContextCodes($codes, $context)
    {
        if ($context == 'edd_sl_post_set_status' && defined('EDD_SL_VERSION')) {
            $codes[] = [
                'key'        => 'edd_license',
                'title'      => 'Edd License',
                'shortcodes' => $this->getSmartCodes('license')
            ];
            return $codes;
        }

        if (!Helper::isEddTrigger($context)) {
            return $codes;
        }

        $codes[] = [
            'key'        => 'edd_order',
            'title'      => 'Current Order - Edd',
            'shortcodes' => $this->getSmartCodes('order')
        ];

        return $codes;
    }

    /**
     * @param $order \EDD\Orders\Order
     * @param $valueKey string
     * @param $defaultValue string
     * @return string
     */
    protected function parseOrderProps($order, $valueKey, $defaultValue = '')
    {
        if (!$order || !$order->id) {
            return $defaultValue;
        }

        switch ($valueKey) {
            case 'address':
                return $this->formatOrderAddress($order);
            case 'order_number':
                $orderNumber = method_exists($order, 'get_number') ? $order->get_number() : $order->order_number;
                return $orderNumber ?: $order->id;
            case 'order_id':
                return $order->id;
            case 'status':
                return edd_get_status_label($order->status);
            case 'currency':
                return $order->currency;
            case 'total_amount':
                return edd_format_amount($order->total);
            case 'payment_method':
                return $order->gateway;
            case 'date':
                return date_i18n(get_option('date_format'), strtotime($order->date_created));
            case 'items_count':
                return count($this->getOrderItems($order));
            case 'order_items_table':
                return $this->getOrderDetailsTable($order);
            case 'download_lists':
                return $this->getDownloadList($order);
        }

        return $defaultValue;
    }

    private function getSmartCodes($context = '')
    {
        if (!$context) {
            $generalCodes = [
                '{{edd_customer.total_order_count}}' => 'Total Order Count',
                '{{woo_customer.total_spent}}'       => 'Total Spent',
            ];

            if (Commerce::isEnabled('edd')) {
                $generalCodes['{{edd_customer.first_order_date}}'] = 'First Order Date';
                $generalCodes['{{edd_customer.last_order_date}}'] = 'Last Order Date';
            }

            return $generalCodes;
        }

        if ($context == 'order') {
            return [
                '{{edd_order.address}}'           => 'Address',
                '{{edd_order.order_number}}'      => 'Order Number',
                '{{edd_order.order_id}}'          => 'Customer Order ID',
                '{{edd_order.status}}'            => 'Status',
                '{{edd_order.currency}}'          => 'Currency',
                '{{edd_order.total_amount}}'      => 'Total Amount',
                '{{edd_order.payment_method}}'    => 'Payment Method',
                '{{edd_order.date}}'              => 'Order Date',
                '{{edd_order.items_count}}'       => 'Items Count',
                '{{edd_order.order_items_table}}' => 'Ordered Items (table)',
                '{{edd_order.download_lists}}'    => 'Order Download Lists'
            ];
        }

        if ($context == 'license') {
            return [
                '{{edd_license.license_key}}'  => 'License Key',
                '{{edd_license.product_name}}' => 'Product Name',
                '{{edd_license.product_id}}'   => 'Product ID',
                '{{edd_license.expire_date}}'  => 'Expire Date',
                '##edd_license.renew_url##'    => 'Renew URL'
            ];
        }

        return [];
    }

    /**
     * Render an EDD 3 order items table for smart code output.
     *
     * @param $order \EDD\Orders\Order
     * @return false|string
     */
    private function getOrderDetailsTable($order, $default = '')
    {
        $order_items = $this->getOrderItems($order);

        if (!$order_items) {
            return $default;
        }
        ob_start();
        ?>
        <div class="wp-block-table">
            <table class="woo_order_table">
                <thead>
                <tr>
                    <th style="text-align: left;"><?php esc_html_e('Product', 'fluentcampaign-pro'); ?></th>
                    <th style="text-align: left;"><?php esc_html_e('Total', 'fluentcampaign-pro'); ?></th>
                </tr>
                </thead>

                <tbody>
                <?php
                foreach ($order_items as $item) {
                    ?>
                        <tr>
                            <td style="text-align: left; padding: 5px 10px; border: 1px solid #5f5f5f;">
                                <?php echo esc_html($item->product_name); ?>
                                <?php if (!is_null($item->price_id) && edd_has_variable_prices($item->product_id)) : ?>
                                    <span
                                        class="edd_purchase_receipt_price_name">&nbsp;&ndash;&nbsp;<?php echo esc_html(edd_get_price_option_name($item->product_id, $item->price_id, $order->id)); ?></span>
                                <?php endif; ?>
                                <?php if (!empty($item->status) && 'complete' !== $item->status) : ?>
                                    &ndash; <?php echo esc_html(edd_get_status_label($item->status)); ?>
                                <?php endif; ?>
                                x <?php echo esc_html($item->quantity); ?>
                            </td>
                            <td style="text-align: left; border: 1px solid #5f5f5f;">
                                <?php echo esc_html(edd_format_amount($this->getOrderItemTotal($item)));
                                ?>
                            </td>
                        </tr>
                <?php } ?>

                <?php if (method_exists($order, 'get_fees') && ($fees = $order->get_fees())) : ?>
                    <?php foreach ($fees as $fee) : ?>
                        <?php
                        $feeLabel = isset($fee->description) ? $fee->description : __('Fee', 'fluentcampaign-pro');
                        $feeAmount = isset($fee->total) ? $fee->total : 0;
                        ?>
                        <tr>
                            <td style="text-align: left; padding: 5px 10px; border: 1px solid #5f5f5f;"
                                class="edd_fee_label">
                                <?php echo esc_html($feeLabel); ?></td>
                            <td style="text-align: left; border: 1px solid #5f5f5f;"
                                class="edd_fee_amount"><?php echo esc_html(edd_currency_filter(edd_format_amount($feeAmount))); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Render EDD 3 secure download links for an order.
     *
     * @param $order \EDD\Orders\Order
     * @return false|string
     */
    private function getDownloadList($order, $default = '')
    {
        $order_items = $this->getOrderItems($order);

        if (!$order_items) {
            return $default;
        }

        ob_start();

        echo '<ul>';
        $isComplete = method_exists($order, 'is_complete') && $order->is_complete();
        foreach ($order_items as $item) :
            if (method_exists($item, 'is_deliverable') && !$item->is_deliverable()) {
                continue;
            }
            $download_files = edd_get_download_files($item->product_id, $item->price_id);
            if ($isComplete && !empty($download_files) && is_array($download_files)) :
                foreach ($download_files as $filekey => $file) :
                    ?>
                    <li class="edd_download_file">
                        <a href="<?php echo esc_url(edd_get_download_file_url($item, $order->email, $filekey)); ?>"
                           class="edd_download_file_link"><?php echo esc_html(edd_get_file_name($file)); ?></a>
                    </li>
                <?php endforeach;
            endif;
        endforeach;
        echo '</ul>';
        return ob_get_clean();
    }

    /**
     * Resolve an EDD 3 order by ID without instantiating legacy payment objects.
     */
    private function getOrder($orderId)
    {
        $orderId = absint($orderId);

        if (!$orderId || !Helper::isEdd3() || !function_exists('edd_get_order')) {
            return false;
        }

        return edd_get_order($orderId);
    }

    /**
     * Return EDD 3 order items in receipt order.
     */
    private function getOrderItems($order)
    {
        if (!$order || !method_exists($order, 'get_items')) {
            return [];
        }

        return $order->get_items();
    }

    /**
     * Format the EDD 3 billing address as one smart-code friendly string.
     */
    private function formatOrderAddress($order)
    {
        if (!$order || !method_exists($order, 'get_address')) {
            return '';
        }

        $address = $order->get_address();

        if (!$address) {
            return '';
        }

        $parts = array_filter([
            $address->address,
            $address->address2,
            $address->city,
            $address->region,
            $address->postal_code,
            $address->country
        ]);

        return implode(' ', $parts);
    }

    /**
     * Get the display total for an EDD 3 order item.
     */
    private function getOrderItemTotal($item)
    {
        if (!$item) {
            return 0;
        }

        if (isset($item->subtotal)) {
            return $item->subtotal;
        }

        return isset($item->total) ? $item->total : 0;
    }
}
