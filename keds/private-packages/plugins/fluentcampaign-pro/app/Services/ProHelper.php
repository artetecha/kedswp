<?php

namespace FluentCampaign\App\Services;

class ProHelper
{
    public static function hasSmartLink()
    {
        $enabled = fluentCrmGetOptionCache('_fcrm_smart_link_status', 604800); // 7 days
        if ($enabled) {
            return $enabled === 'yes';
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'fc_smart_links';
        $query = $wpdb->prepare('SHOW TABLES LIKE %s', $wpdb->esc_like($table_name));
        $status = ($wpdb->get_var($query) == $table_name) ? 'yes' : 'no';
        fluentCrmSetOptionCache('_fcrm_smart_link_status', $status, 604800); // 7 days

        return $status === 'yes';
    }
}
