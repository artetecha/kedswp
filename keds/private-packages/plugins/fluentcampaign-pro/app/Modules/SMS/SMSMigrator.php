<?php

namespace FluentCampaign\App\Modules\SMS;

class SMSMigrator
{
    public static function migrate($isForced = false)
    {
        self::migrateSMSMessagesTable($isForced);
        self::migrateSMSCampaignsTable($isForced);
        self::updateSubscribersTable();
    }

    public static function migrateSMSMessagesTable($isForced = false)
    {
        global $wpdb;

        $charsetCollate = $wpdb->get_charset_collate();
        $table = $wpdb->prefix . 'fc_sms_messages';
        $indexPrefix = $wpdb->prefix . 'fc_sms_msg';

        if ($wpdb->get_var("SHOW TABLES LIKE '$table'") != $table || $isForced) {
            $sql = "CREATE TABLE $table (
                `id` BIGINT UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT,
                `campaign_id` BIGINT UNSIGNED NULL,
                `sms_type` VARCHAR(50) NULL DEFAULT 'campaign',
                `subscriber_id` BIGINT UNSIGNED NULL,
                `mobile_number` VARCHAR(20) NULL,
                `click_counter` INT UNSIGNED NULL DEFAULT 0,
                `message_content` TEXT,
                `status` VARCHAR(50) NOT NULL DEFAULT 'pending',
                `delivery_status` VARCHAR(50) NULL DEFAULT 'queued',
                `notes` TEXT NULL,
                `provider_message_id` VARCHAR(100) NULL,
                `settings` LONGTEXT NULL,
                `scheduled_at` TIMESTAMP NULL,
                `sent_at` TIMESTAMP NULL,
                `created_at` TIMESTAMP NULL,
                `updated_at` TIMESTAMP NULL,
                INDEX `{$indexPrefix}_campaign_id_idx` (`campaign_id`),
                INDEX `{$indexPrefix}_status_idx` (`status`),
                INDEX `{$indexPrefix}_scheduled_at_idx` (`scheduled_at`)
            ) $charsetCollate;";
            
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($sql);
        }
    }

    public static function migrateSMSCampaignsTable($isForced = false)
    {
        global $wpdb;

        $charsetCollate = $wpdb->get_charset_collate();
        $table = $wpdb->prefix . 'fc_sms_campaigns';
        $indexPrefix = $wpdb->prefix . 'fc_sms_camp';

        if ($wpdb->get_var("SHOW TABLES LIKE '$table'") != $table || $isForced) {
            $sql = "CREATE TABLE $table (
                `id` BIGINT UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT,
                `parent_id` BIGINT UNSIGNED NULL,
                `type` VARCHAR(50) NOT NULL DEFAULT 'campaign',
                `title` VARCHAR(192) NOT NULL,
                `slug` VARCHAR(192) NOT NULL,
                `status` VARCHAR(50) NOT NULL DEFAULT 'draft',
                `message_content` TEXT NOT NULL,
                `sender_number` VARCHAR(20) NULL,
                `recipients_count` INT UNSIGNED NOT NULL DEFAULT 0,
                `sent_count` INT UNSIGNED NOT NULL DEFAULT 0,
                `failed_count` INT UNSIGNED NOT NULL DEFAULT 0,
                `delay` INT UNSIGNED NULL DEFAULT 0,
                `scheduled_at` TIMESTAMP NULL,
                `settings` LONGTEXT NULL,
                `created_by` BIGINT UNSIGNED NULL,
                `created_at` TIMESTAMP NULL,
                `updated_at` TIMESTAMP NULL,
                INDEX `{$indexPrefix}_status_idx` (`status`),
                INDEX `{$indexPrefix}_scheduled_at_idx` (`scheduled_at`),
                INDEX `{$indexPrefix}_type_idx` (`type`)
            ) $charsetCollate;";
            
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($sql);
        }
    }

    public static function updateSubscribersTable()
    {
        global $wpdb;

        $table = $wpdb->prefix . 'fc_subscribers';

        // Add sms_status column if it doesn't exist
        if ($wpdb->get_var("SHOW COLUMNS FROM `$table` LIKE 'sms_status'") === null) {
            $wpdb->query("ALTER TABLE `$table` ADD `sms_status` VARCHAR(50) NULL DEFAULT 'sms_subscribed' AFTER `status`");
        }

    }

    public static function dropTable()
    {
        global $wpdb;

        $tables = [
            $wpdb->prefix . 'fc_sms_messages',
            $wpdb->prefix . 'fc_sms_campaigns'
        ];

        foreach ($tables as $table) {
            if ($wpdb->get_var("SHOW TABLES LIKE '$table'") == $table) {
                $wpdb->query("DROP TABLE IF EXISTS $table");
            }
        }
    }
}