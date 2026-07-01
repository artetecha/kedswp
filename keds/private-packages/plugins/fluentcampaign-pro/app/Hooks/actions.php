<?php
/**
 * @var \FluentCrm\Framework\Foundation\Application $app
 */



(new \FluentCampaign\App\Hooks\Handlers\IntegrationHandler())->init();
(new \FluentCampaign\App\Hooks\Handlers\CampaignArchiveFront())->register();
(new \FluentCampaign\App\Hooks\Handlers\FrontendPortalHandler())->register();
(new \FluentCampaign\App\Hooks\Handlers\VisualEmailBuilderHandler())->register();
(new \FluentCampaign\App\Hooks\Handlers\ExtendedSmartCodesHandler())->register();

(new \FluentCampaign\App\Hooks\Handlers\SmartLinkHandler())->register();


// Register WooCommerce driver when AbandonCart fires its driver registration action
add_action('fluent_crm/abandon_cart_register_drivers', function () {
    \FluentCrm\App\Modules\AbandonCart\Drivers\DriverManager::register(
        new \FluentCampaign\App\Modules\AbandonCart\Woo\WooDriver()
    );
});

add_action('init', function () {
    (new \FluentCampaign\App\Hooks\Handlers\DynamicSegment())->init();
}, 1);

/*
 * MCP — Register Pro abilities under the `fluent-crm/` namespace.
 *
 * Hooks `fluent_crm/mcp_loaded` (fired from FluentCRM's MCPInit). The MCPInit
 * itself sits behind a `function_exists('wp_register_ability')` guard, so this
 * code runs only when the WP MCP Adapter plugin is active.
 */
(new \FluentCampaign\App\Modules\MCP\MCPInit())->init();

/*
 * Cleanup actions
 */
$app->addAction('fluentcrm_sequence_email_deleted', 'FluentCampaign\App\Hooks\Handlers\Cleanup@deleteCampaignAssets', 10, 1);
$app->addAction('fluentcrm_sequence_deleted', 'FluentCampaign\App\Hooks\Handlers\Cleanup@deleteSequenceAssets', 10, 1);
$app->addAction('fluentcrm_after_subscribers_deleted', 'FluentCampaign\App\Hooks\Handlers\Cleanup@deleteCommerceItems', 10, 1);

// fluentcrm_scheduled_hourly_tasks
$app->addAction('fluentcrm_scheduled_hourly_tasks', 'FluentCampaign\App\Hooks\Handlers\EmailScheduleHandler@handle');
$app->addAction('fluentcrm_scheduled_maybe_regular_tasks', 'FluentCampaign\App\Hooks\Handlers\EmailScheduleHandler@handle');

// Backward-compat: drain any in-flight Action Scheduler jobs queued by the legacy
// server-side contacts export. The handler is a no-op so existing scheduled jobs
// complete cleanly without flooding error logs after the upgrade to the new
// client-side export. Safe to remove once enough time has passed for queued
// jobs to drain (typically next major release).
$app->addAction('fluentcrm_prepare_contacts_csv_export_file', function () {
    // Intentional no-op: legacy server-side export has been replaced by ExportController.
}, 10, 5);

$app->addAction('wp_ajax_fluentcrm_export_companies', 'FluentCampaign\App\Hooks\Handlers\DataExporter@exportCompanies');
$app->addAction('wp_ajax_fluentcrm_import_funnel', 'FluentCampaign\App\Hooks\Handlers\DataExporter@importFunnel');
$app->addAction('wp_ajax_fluentcrm_import_sequence', 'FluentCampaign\App\Hooks\Handlers\DataExporter@importEmailSequence');
$app->addAction('wp_ajax_fluentcrm_export_notes', 'FluentCampaign\App\Hooks\Handlers\DataExporter@exportNotes');
$app->addAction('wp_ajax_fluentcrm_export_sequence', 'FluentCampaign\App\Hooks\Handlers\DataExporter@exportEmailSequence');
$app->addAction('wp_ajax_fluentcrm_export_template', 'FluentCampaign\App\Hooks\Handlers\DataExporter@exportEmailTemplate');
$app->addAction('wp_ajax_fluentcrm_import_template', 'FluentCampaign\App\Hooks\Handlers\DataExporter@importEmailTemplate');
$app->addAction('wp_ajax_fluentcrm_export_email_campaign', 'FluentCampaign\App\Hooks\Handlers\DataExporter@exportEmailCampaign');
$app->addAction('wp_ajax_fluentcrm_import_email_campaign', 'FluentCampaign\App\Hooks\Handlers\DataExporter@importEmailCampaign');
$app->addAction('wp_ajax_fluentcrm_export_archived_campaign_emails', 'FluentCampaign\App\Hooks\Handlers\DataExporter@exportArchivedCampaignEmails');
$app->addAction('wp_ajax_fluentcrm_export_sms_campaign', 'FluentCampaign\App\Hooks\Handlers\DataExporter@exportSMSCampaign');
$app->addAction('wp_ajax_fluentcrm_import_sms_campaign', 'FluentCampaign\App\Hooks\Handlers\DataExporter@importSMSCampaign');


// Export/Import Recurring Campaign
$app->addAction('wp_ajax_fluentcrm_export_recurring_campaign', 'FluentCampaign\App\Hooks\Handlers\DataExporter@exportRecurringCampaign');
$app->addAction('wp_ajax_fluentcrm_import_recurring_campaigns', 'FluentCampaign\App\Hooks\Handlers\DataExporter@importRecurringCampaign');


$app->addAction('set_user_role', 'FluentCampaign\App\Hooks\Handlers\IntegrationHandler@maybeAutoAlterTags', 11, 2);
$app->addAction('add_user_role', 'FluentCampaign\App\Hooks\Handlers\IntegrationHandler@maybeAutoAlterTags', 11, 2);

$app->addAction('fluent_crm/recurring_mail_created_as_draft', 'FluentCampaign\App\Hooks\Handlers\RecurringCampaignHandler@draftMailCreated', 10, 2);

$app->addAction('wp_ajax_fluent_crm_export_dynamic_segment', 'FluentCampaign\App\Hooks\Handlers\DataExporter@exportDynamicSegment');
$app->addAction('wp_ajax_fluent_crm_import_dynamic_segment', 'FluentCampaign\App\Hooks\Handlers\DataExporter@importDynamicSegment');



// add_action('fluent_crm/register_sms_providers', function () {
//     new \FluentCampaign\App\Modules\SMS\Examples\CustomSMSDriverExample();
