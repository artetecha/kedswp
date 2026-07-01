<?php

namespace FluentCampaign\App\Modules\AbandonCart\Woo;

use FluentCrm\App\Modules\AbandonCart\AbandonCartModel;
use FluentCrm\App\Modules\AbandonCart\AbCartHelper;
use FluentCrm\App\Modules\AbandonCart\Drivers\AbstractCartDriver;
use FluentCrm\Framework\Support\Arr;

class WooDriver extends AbstractCartDriver
{
    public function getProviderSlug()
    {
        return 'woo';
    }

    public function getProviderLabel()
    {
        return __('WooCommerce', 'fluentcampaign-pro');
    }


    public function getSettingsFields()
    {
        if (!$this->isAvailable()) {
            return [];
        }

        $allStatuses = wc_get_order_statuses();

        foreach ($allStatuses as $key => $value) {
            $newKey = (string)preg_replace('/^wc-/', '', $key);
            $allStatuses[$newKey] = $value;
            unset($allStatuses[$key]);
        }

        unset($allStatuses['refunded']);
        unset($allStatuses['failed']);
        unset($allStatuses['cancelled']);
        unset($allStatuses['checkout-draft']);

        $formattedStatuses = [];

        foreach ($allStatuses as $key => $value) {
            $formattedStatuses[] = [
                'id'    => $key,
                'label' => ucfirst($value)
            ];
        }

        return [
            'wc_recovered_statuses' => [
                'name'        => 'wc_recovered_statuses',
                'label'       => __('Mark Cart as Recovered when WooCommerce Order Status Changes to:', 'fluentcampaign-pro'),
                'type'        => 'checkbox-group',
                'options'     => $formattedStatuses,
                'inline_help' => __('Automatically mark a cart as recovered when the corresponding WooCommerce order status changes to the selected status.', 'fluentcampaign-pro'),
            ]
        ];
    }

    public function isAvailable()
    {
        return defined('WC_PLUGIN_FILE');
    }

    public function register()
    {
        (new WooCartTrackingInit())->register();
    }

    public function registerAutomationTrigger()
    {
        // Already registered inside WooCartTrackingInit::register()
        // but kept here for the driver contract
    }

    protected function getViewsBasePath()
    {
        return FLUENTCAMPAIGN_PLUGIN_PATH . 'app/Modules/AbandonCart/Views/';
    }

    public function isWithinCoolOffPeriod(AbandonCartModel $cart)
    {
        $coolOffPeriodDay = AbCartHelper::getSetting('cool_off_period_days', 0);
        if (!$coolOffPeriodDay) {
            return false;
        }

        $coolOffDateTime = gmdate('Y-m-d H:i:s', time() - ($coolOffPeriodDay * DAY_IN_SECONDS));

        $orderStatuses = AbCartHelper::getSetting('wc_recovered_statuses', ['processing', 'completed']);

        $orderStatuses = array_map(function ($status) {
            return 'wc-' . $status;
        }, $orderStatuses);

        if (\FluentCampaign\App\Services\Integrations\WooCommerce\Helper::isWooHposEnabled()) {
            return fluentCrmDb()->table('wc_orders')
                ->where('type', 'shop_order')
                ->where('date_created_gmt', '>=', $coolOffDateTime)
                ->whereIn('status', $orderStatuses)
                ->where(function ($q) use ($cart) {
                    $q->where('billing_email', $cart->email);
                    if ($cart->user_id) {
                        $q->orWhere('customer_id', $cart->user_id);
                    }
                })
                ->exists();
        }

        $check_statuses = implode("','", array_map('esc_sql', $orderStatuses));

        global $wpdb;

        $order_query = $wpdb->prepare(
            "SELECT posts.ID
            FROM $wpdb->posts AS posts
            LEFT JOIN {$wpdb->postmeta} AS meta on posts.ID = meta.post_id
            WHERE meta.meta_key = '_billing_email'
            AND   meta.meta_value = %s
            AND   posts.post_type = 'shop_order'
            AND   posts.post_status IN ( '" . $check_statuses . "' )
            AND   posts.post_date >= %s
            ORDER BY posts.ID DESC LIMIT 0,1",
            $cart->email,
            $coolOffDateTime
        );

        $last_order = $wpdb->get_var($order_query);

        return (bool)$last_order;
    }

    public function getCartItemsHtml(AbandonCartModel $cart)
    {
        $cartItems = Arr::get($cart->cart, 'cart_contents', []);
        $updatedCartItems = $this->reconstructCartItemsWithTax($cartItems);

        return $this->loadView('AbandonCartItems', [
            'cartItems' => $updatedCartItems,
            'currency'  => $cart->currency
        ]);
    }

    public function formatPrice($amount, $currency = '')
    {
        if (!function_exists('wc_price')) {
            return '$' . number_format((float)$amount, 2);
        }

        return wc_price($amount, $currency ? ['currency' => $currency] : []);
    }

    public function getRecoveryUrl(AbandonCartModel $cart)
    {
        if ($cart->status != 'processing') {
            return '';
        }

        return add_query_arg([
            'fluentcrm'  => 1,
            'route'      => 'general',
            'handler'    => $this->getHandlerName(),
            'fc_ab_hash' => $cart->checkout_key
        ], home_url());
    }

    public function extractCartConditionData(AbandonCartModel $cart)
    {
        $items = Arr::get($cart->cart, 'cart_contents', []);

        $productIds = [];
        $categoryIds = [];

        foreach ($items as $item) {
            $productIds[] = $item['product_id'];
        }

        if ($productIds) {
            $cats = fluentCrmDb()->table('term_relationships')
                ->join('term_taxonomy', 'term_relationships.term_taxonomy_id', '=', 'term_taxonomy.term_taxonomy_id')
                ->whereIn('term_relationships.object_id', $productIds)
                ->where('term_taxonomy.taxonomy', 'product_cat')
                ->select('term_taxonomy.term_id')
                ->get();

            foreach ($cats as $cat) {
                $categoryIds[] = $cat->term_id;
            }
        }

        return [
            'product_ids'  => $productIds,
            'category_ids' => $categoryIds
        ];
    }

    public function enrichCartForListing(AbandonCartModel $cart)
    {
        if ($cart->order_id) {
            $cart->order_url = admin_url('admin.php?page=wc-orders&action=edit&id=' . $cart->order_id);
        }

        if (!empty($cart->cart['cart_contents']) && function_exists('wc_get_product')) {
            $newCart = $cart->cart;
            foreach ($newCart['cart_contents'] as $key => $cartItem) {
                $product = wc_get_product($cartItem['product_id']);
                if ($product) {
                    $product_image_url = wp_get_attachment_url($product->get_image_id());
                    if (!$product_image_url) {
                        $product_image_url = wc_placeholder_img_src();
                    }
                    $newCart['cart_contents'][$key]['product_image'] = $product_image_url;
                }
            }
            $cart->cart = $newCart;
        }

        return $cart;
    }

    public function getProviderSettingsResponse()
    {
        if (!defined('WC_PLUGIN_FILE')) {
            return [];
        }

        $allStatuses = wc_get_order_statuses();

        foreach ($allStatuses as $key => $value) {
            $newKey = (string)preg_replace('/^wc-/', '', $key);
            $allStatuses[$newKey] = $value;
            unset($allStatuses[$key]);
        }

        unset($allStatuses['refunded']);
        unset($allStatuses['failed']);
        unset($allStatuses['cancelled']);
        unset($allStatuses['checkout-draft']);

        return [
            'all_statuses'  => $allStatuses,
            'paid_statuses' => wc_get_is_paid_statuses(),
        ];
    }

    public function getProviderSettingsDefaults()
    {
        return [
            'wc_recovered_statuses' => ['processing', 'completed'],
        ];
    }

    public function processSettings($settings)
    {
        if (defined('WC_PLUGIN_FILE')) {
            $recoveredStatuses = (array)Arr::get($settings, 'wc_recovered_statuses', []);

            $settings['wc_recovered_statuses'] = array_values(
                array_unique(
                    array_merge(wc_get_is_paid_statuses(), $recoveredStatuses)
                )
            );
        }

        return $settings;
    }

    /**
     * Check if a WooCommerce order status counts as a successful recovery.
     *
     * @param string $orderStatus
     * @return bool
     */
    public function isWinOrderStatus($orderStatus)
    {
        $settings = AbCartHelper::getSettings();

        $recoveredStatuses = Arr::get($settings, 'wc_recovered_statuses', []);
        $result = in_array($orderStatus, $recoveredStatuses, true);

        return apply_filters('fluent_crm/ab_cart_is_win_status', $result, $orderStatus, $this);
    }

    private function reconstructCartItemsWithTax($cartItems)
    {
        $pricesIncludeTax = get_option('woocommerce_tax_display_cart') === 'incl';

        foreach ($cartItems as $key => &$item) {
            if ($pricesIncludeTax) {
                $item['line_total_with_tax'] = $item['line_total'] + $item['line_tax'];
                $item['tax_including'] = 'yes';
            } else {
                $item['line_total_with_tax'] = $item['line_total'];
                $item['tax_including'] = 'no';
            }
        }

        return $cartItems;
    }

    public function getLogo()
    {
        return FLUENTCRM_PLUGIN_URL . 'assets/images/woo.svg';
    }
}
