<?php
/**
* @var $router FluentCrm\Framework\Http\Router
*/

use FluentCampaign\App\Modules\SMS\Http\Controllers\SMSController;
use FluentCampaign\App\Modules\SMS\Http\Policies\SMSPolicy;

$router->prefix('sms')->withPolicy(SMSPolicy::class)->group(function ($router) {
    // SMS Management routes for subscribers
    $router->get('/subscribers/{id}/logs', [SMSController::class, 'getLogs'])->int('id');
    $router->post('/subscribers/{id}/send', [SMSController::class, 'sendCustomSMS'])->int('id');
    $router->get('/subscribers/{id}/stats', [SMSController::class, 'getStats'])->int('id');

    // SMS Campaign routes
    $router->get('/campaigns', [SMSController::class, 'campaigns']);
    $router->post('/campaigns', [SMSController::class, 'create']);
    $router->get('/campaigns/{id}', [SMSController::class, 'campaign'])->int('id');
    $router->put('/campaigns/{id}', [SMSController::class, 'update']);
    $router->put('/{id}/update-labels', [SMSController::class, 'updateLabels'])->int('id');
    $router->post('/campaigns/estimated-contacts', [SMSController::class, 'getContactEstimation']);
    $router->get('/campaigns/{id}/estimated-recipients-count', [SMSController::class, 'getRecipientsCount'])->int('id');
    $router->post('/campaigns/{id}/schedule', [SMSController::class, 'schedule'])->int('id');
    $router->post('/campaigns/{id}/pause', [SMSController::class, 'pauseCampaign'])->int('id');
    $router->post('/campaigns/{id}/resume', [SMSController::class, 'resumeCampaign'])->int('id');
    // Keep legacy route for older admin screens.
    $router->post('/campaigns/{id}/un-schedule', [SMSController::class, 'unSchedule'])->int('id');
    $router->post('/campaigns/{id}/unschedule', [SMSController::class, 'unSchedule'])->int('id');
    $router->post('/campaigns/do-bulk-action', [SMSController::class, 'handleBulkAction']);
    $router->get('/campaigns/{id}/status', [SMSController::class, 'getCampaignStatus'])->int('id');
    $router->get('/campaigns/{id}/recipients', [SMSController::class, 'getCampaignRecipients'])->int('id');
    $router->post('/campaigns/{id}/tag-actions', [SMSController::class, 'doTagActions'])->int('id');
    $router->get('/campaigns/{id}/processing-stat', [SMSController::class, 'processingStat'])->int('id');
    $router->post('/campaigns/{id}/duplicate', [SMSController::class, 'duplicateCampaign'])->int('id');
    $router->delete('/campaigns/{id}', [SMSController::class, 'delete'])->int('id');

    // All SMS messages routes
    $router->get('/messages', [SMSController::class, 'getAllMessages']);
    $router->delete('/messages', [SMSController::class, 'deleteMessages']);
    $router->post('/messages/{id}/resend', [SMSController::class, 'resendMessage'])->int('id');
});
