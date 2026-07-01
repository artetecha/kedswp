<?php

/**
 * @var $router FluentCrm\Framework\Http\Router
 */

use FluentCampaign\App\Http\Policies\DynamicSegmentPolicy;
use FluentCampaign\App\Http\Policies\SequencePolicy;
use FluentCampaign\App\Http\Policies\SmartLinksPolicy;
use FluentCrm\App\Http\Policies\CampaignPolicy;
use FluentCrm\App\Http\Policies\FunnelPolicy;
use FluentCrm\App\Http\Policies\SettingsPolicy;
use FluentCampaign\App\Http\Controllers\CommerceReportsController;
use FluentCampaign\App\Http\Controllers\ProReportController;
use FluentCampaign\App\Http\Controllers\RecurringCampaignController;
use FluentCampaign\App\Http\Controllers\SequenceController;
use FluentCampaign\App\Modules\SMS\Http\Controllers\SettingsController as SMSSettingsController;
use FluentCampaign\App\Http\Controllers\SequenceMailController;
use FluentCampaign\App\Http\Controllers\DynamicSegmentController;
use FluentCampaign\App\Http\Controllers\CampaignsProController;
use FluentCampaign\App\Http\Controllers\DynamicPostDataController;
use FluentCampaign\App\Http\Controllers\SmartLinksController;
use FluentCampaign\App\Http\Controllers\LicenseController;
use FluentCampaign\App\Http\Controllers\ManagerController;
use FluentCampaign\App\Http\Controllers\FunnelImporter;
use FluentCampaign\App\Http\Controllers\ExportController;
use FluentCampaign\App\Http\Policies\ExportPolicy;

/*
 * Email Sequences Route
 */
$router->prefix('sequences')->withPolicy(SequencePolicy::class)->group(function ($router) {

    $router->get('/', [SequenceController::class, 'sequences']);
    $router->post('/', [SequenceController::class, 'create']);

    $router->get('subscriber/{subscriber_id}/sequences', [SequenceController::class, 'subscriberSequences'])->int('subscriber_id');

    $router->post('do-bulk-action', [SequenceController::class, 'handleBulkAction']);

    $router->get('{id}', [SequenceController::class, 'sequence'])->int('id');
    $router->put('{id}', [SequenceController::class, 'update'])->int('id');
    $router->post('{id}/duplicate', [SequenceController::class, 'duplicate'])->int('id');
    $router->delete('{id}', [SequenceController::class, 'delete'])->int('id');

    /*
     * @todo: Use this route in the december Update
     */
    $router->post('sequence-email-update-create', [SequenceMailController::class, 'routeFallBackSequenceEmailCreateUpdate']);

    $router->get('{id}/email/{email_id}', [SequenceMailController::class, 'get'])->int('id')->int('email_id');
    $router->post('{id}/email', [SequenceMailController::class, 'create'])->int('id');
    $router->post('{id}/email/duplicate', [SequenceMailController::class, 'duplicate'])->int('id');

    // this below {id}/email/{email_id} endpoint is not called from ui may be . found in v3-check
    $router->put('{id}/email/{email_id}', [SequenceMailController::class, 'update'])->int('id')->int('email_id'); // first one is sequence id and second one is email id
    $router->delete('{id}/email/{email_id}', [SequenceMailController::class, 'delete'])->int('id')->int('email_id');
    $router->patch('{id}/email/{email_id}/delay', [SequenceMailController::class, 'updateDelay'])->int('id')->int('email_id');

    $router->get('{id}/subscribers', [SequenceController::class, 'getSubscribers'])->int('id');
    $router->post('{id}/subscribers', [SequenceController::class, 'subscribe'])->int('id');
    $router->delete('{id}/subscribers', [SequenceController::class, 'deleteSubscribes'])->int('id');

    $router->post('{id}/reapply', [SequenceController::class, 'reapplySequence']);

});


$router->prefix('recurring-campaigns')->withPolicy(SequencePolicy::class)->group(function ($router) {
    $router->get('/', [RecurringCampaignController::class, 'getCampaigns']);
    $router->post('/', [RecurringCampaignController::class, 'createCampaign']);
    $router->post('/update-campaign-data', [RecurringCampaignController::class, 'updateCampaignData']);

    $router->get('{campaign_id}', [RecurringCampaignController::class, 'getCampaign'])->int('campaign_id');
    $router->post('{campaign_id}/change-status', [RecurringCampaignController::class, 'changeStatus'])->int('campaign_id');
    $router->post('{campaign_id}/update-settings', [RecurringCampaignController::class, 'updateCampaignSettings'])->int('campaign_id');
    $router->post('{campaign_id}/duplicate', [RecurringCampaignController::class, 'duplicate'])->int('campaign_id');
    $router->get('{campaign_id}/emails', [RecurringCampaignController::class, 'getEmails'])->int('campaign_id');

    $router->get('{campaign_id}/emails/{email_id}', [RecurringCampaignController::class, 'getEmail'])->int('campaign_id')->int('email_id');
    $router->put('{campaign_id}/emails/{email_id}', [RecurringCampaignController::class, 'patchCampaignEmail'])->int('campaign_id')->int('email_id');
    $router->post('{campaign_id}/emails/update-email', [RecurringCampaignController::class, 'updateCampaignEmail'])->int('campaign_id');


    $router->post('/delete-bulk', [RecurringCampaignController::class, 'deleteBulk']);
    $router->post('/do-bulk-action', [RecurringCampaignController::class, 'handleBulkAction']);
    $router->put('{campaign_id}/update-labels', [RecurringCampaignController::class, 'updateLabels'])->int('campaign_id');

});

/*
 * Dynamic Segments
 */
$router->prefix('dynamic-segments')->withPolicy(DynamicSegmentPolicy::class)->group(function ($router) {

    $router->get('/', [DynamicSegmentController::class, 'index']);
    $router->get('/stats', [DynamicSegmentController::class, 'getStats']);
    $router->post('/', [DynamicSegmentController::class, 'createCustomSegment']);
    $router->post('estimated-contacts', [DynamicSegmentController::class, 'getEstimatedContacts']);
    $router->put('{id}', [DynamicSegmentController::class, 'updateCustomSegment'])->int('id');

    $router->delete('{id}', [DynamicSegmentController::class, 'deleteCustomSegment']);
    $router->post('/duplicate/{id}', [DynamicSegmentController::class, 'duplicate'])->int('id');

    $router->get('{slug}/subscribers/{id}', [DynamicSegmentController::class, 'getSegment'])->alphaNumDash('slug')->int('id');
    $router->get('custom-fields', [DynamicSegmentController::class, 'getCustomFields']);

});

/*
 * Dynamic Segments
 */
$router->prefix('campaigns-pro')->withPolicy(CampaignPolicy::class)->group(function ($router) {

    $router->post('{id}/resend-failed-emails', [CampaignsProController::class, 'resendFailedEmails']);
    $router->post('{id}/resend-unopened-emails', [CampaignsProController::class, 'resendUnopenedEmails']);
    $router->post('{id}/resend-emails', [CampaignsProController::class, 'resendEmails']);
    $router->post('{id}/tag-actions', [CampaignsProController::class, 'doTagActions']);

    $router->get('posts', [DynamicPostDataController::class, 'getPosts']);
    $router->get('posts/taxonomies', [DynamicPostDataController::class, 'getPostsTaxonomies']);
    $router->get('products', [DynamicPostDataController::class, 'getProducts']);

});

/*
 * Action Links
 */
$router->prefix('smart-links')->withPolicy(SmartLinksPolicy::class)->group(function ($router) {

    $router->get('/', [SmartLinksController::class, 'getLinks']);
    $router->post('/', [SmartLinksController::class, 'createLink']);
    $router->post('activate', [SmartLinksController::class, 'activate']);
    $router->put('{id}', [SmartLinksController::class, 'update']);
    $router->delete('{id}', [SmartLinksController::class, 'delete']);

});

/*
 * Dynamic Segments
 */
$router->prefix('campaign-pro-settings')->withPolicy(SettingsPolicy::class)->group(function ($router) {

    $router->get('license', [LicenseController::class, 'getStatus']);
    $router->post('license', [LicenseController::class, 'saveLicense']);
    $router->delete('license', [LicenseController::class, 'deactivateLicense']);

    $router->get('managers', [ManagerController::class, 'getManagers']);
    $router->post('managers', [ManagerController::class, 'addManager']);
    $router->put('managers/{id}', [ManagerController::class, 'updateManager'])->int('id');
    $router->delete('managers/{id}', [ManagerController::class, 'deleteManager'])->int('id');

    $router->post('import_funnel', [FunnelImporter::class, 'import']);

    $router->get('sms', [SMSSettingsController::class, 'getSettings']);
    $router->post('sms', [SMSSettingsController::class, 'saveSettings']);

    // NEW: dedicated endpoint to disable SMS (sets _fc_sms_enable = 'no')
    $router->post('sms/disable', [SMSSettingsController::class, 'disable']);
});

$router->prefix('commerce-reports')->withPolicy(SettingsPolicy::class)->group(function ($router) {
    $router->get('/{provider}', [CommerceReportsController::class, 'getReports'])->alphaNumDash('provider');
    $router->get('/{provider}/report', [CommerceReportsController::class, 'getReport'])->alphaNumDash('provider');
});

$router->prefix('reports')->withPolicy(SettingsPolicy::class)->group(function ($router) {
    $router->get('/top-campaigns', [ProReportController::class, 'getTopCampaigns']);
});

/*
 * Contacts Export (client-side CSV generation)
 */
$router->prefix('subscribers-export')->withPolicy(ExportPolicy::class)->group(function ($router) {
    // POST is preferred for export (filter payloads can exceed GET URL limits);
    // GET kept for backward compatibility.
    $router->get('/', [ExportController::class, 'getContactsPage']);
    $router->post('/', [ExportController::class, 'getContactsPage']);
});

