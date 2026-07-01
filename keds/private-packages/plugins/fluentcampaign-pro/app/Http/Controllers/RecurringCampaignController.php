<?php

namespace FluentCampaign\App\Http\Controllers;

use FluentCampaign\App\Models\RecurringCampaign;
use FluentCampaign\App\Models\RecurringMail;
use FluentCampaign\App\Services\RecurringCampaignRunner;
use FluentCampaign\App\Http\Controllers\Controller;
use FluentCrm\App\Models\CampaignEmail;
use FluentCrm\App\Services\Helper;
use FluentCrm\App\Services\Sanitize;
use FluentCrm\Framework\Support\Arr;
use FluentCrm\Framework\Http\Request\Request;

class RecurringCampaignController extends Controller
{
    public function getCampaigns(Request $request)
    {
        $order = strtoupper($request->get('order', ''));
        $order = in_array($order, ['ASC', 'DESC'], true) ? $order : 'DESC';

        $allowedOrderBy = ['id', 'title', 'status', 'scheduled_at', 'created_at'];
        $orderBy = $request->get('orderBy', 'id');
        $orderBy = in_array($orderBy, $allowedOrderBy, true) ? $orderBy : 'id';

        $labels = $request->get('labels', []);
        $labels = is_array($labels) ? array_map('intval', $labels) : [];
        $labels = array_filter($labels);

        $statuses = $request->get('statuses', []);
        $statuses = is_array($statuses) ? array_map('sanitize_text_field', $statuses) : [];

        $search = $request->getSafe('search', 'sanitize_text_field');

        $campaignQuery = RecurringCampaign::select([
            'id', 'title', 'settings', 'scheduled_at', 'created_at', 'status'
        ])
            ->orderBy($orderBy, $order);

        if ($search) {
            global $wpdb;
            $campaignQuery->where('title', 'LIKE', '%' . $wpdb->esc_like($search) . '%');
        }

        if (!empty($labels)) {
            $campaignQuery->whereHas('labelsTerm', function ($query) use ($labels) {
                $query->whereIn('term_id', $labels);
            });
        }

        if (!empty($statuses)) {
            $campaignQuery->whereIn('status', $statuses);
        }

        $campaigns = $campaignQuery->paginate();

        $campaignIds = $campaigns->pluck('id')->toArray();

        if ($campaignIds) {
            // Batch: draft recurring mails grouped by parent_id
            $draftMap = fluentCrmDb()->table('fc_campaigns')
                ->select('parent_id')
                ->where('type', 'recurring_mail')
                ->where('status', 'draft')
                ->whereIn('parent_id', $campaignIds)
                ->groupBy('parent_id')
                ->pluck('parent_id')
                ->flip()
                ->toArray();

            // Batch: email counts grouped by parent_id
            $emailCounts = fluentCrmDb()->table('fc_campaigns')
                ->select('parent_id')
                ->selectRaw('COUNT(*) as total')
                ->where('type', 'recurring_mail')
                ->whereIn('parent_id', $campaignIds)
                ->groupBy('parent_id')
                ->get()
                ->keyBy('parent_id');

            // Batch: labels via fc_term_relations + fc_terms
            $labelRelations = fluentCrmDb()->table('fc_term_relations')
                ->whereIn('object_id', $campaignIds)
                ->where('object_type', 'FluentCampaign\App\Models\RecurringCampaign')
                ->get();

            $labelIdsByCampaign = [];
            $allLabelIds = [];
            foreach ($labelRelations as $rel) {
                $labelIdsByCampaign[$rel->object_id][] = $rel->term_id;
                $allLabelIds[] = $rel->term_id;
            }

            $allLabels = [];
            if ($allLabelIds) {
                $allLabels = fluentCrmDb()->table('fc_terms')
                    ->whereIn('id', array_unique($allLabelIds))
                    ->get()
                    ->keyBy('id');
            }

            foreach ($campaigns as $campaign) {
                if ($campaign->status == 'active' && Arr::get($campaign->settings, 'scheduling_settings.send_automatically') != 'yes') {
                    $campaign->has_draft = isset($draftMap[$campaign->id]);
                }

                $campaign->emails_count = isset($emailCounts[$campaign->id]) ? (int) $emailCounts[$campaign->id]->total : 0;

                // Map labels from batch
                $campaignLabelIds = $labelIdsByCampaign[$campaign->id] ?? [];
                $campaign->labels = array_values(array_filter(array_map(function ($labelId) use ($allLabels) {
                    $label = $allLabels[$labelId] ?? null;
                    if (!$label) {
                        return null;
                    }
                    $settings = maybe_unserialize($label->settings);
                    return [
                        'id'    => $label->id,
                        'slug'  => $label->slug,
                        'title' => $label->title,
                        'color' => is_array($settings) ? ($settings['color'] ?? '') : ''
                    ];
                }, $campaignLabelIds)));
            }
        }

        return [
            'campaigns' => $campaigns
        ];
    }

    public function createCampaign(Request $request)
    {
        $campaignData = Helper::parseArrayOrJson($request->get('campaign'));

        $this->validate($campaignData, [
            'title'                             => 'required',
            'settings.scheduling_settings.time' => 'required',
            'settings.scheduling_settings.type' => 'required'
        ]);

        $campaignData['title'] = sanitize_text_field($campaignData['title']);

        if (RecurringCampaign::where('title', $campaignData['title'])->first()) {
            return $this->sendError([
                'message'    => __('Another campaign with the same name already exist. Please provide a different name', 'fluentcampaign-pro'),
                'go_to_step' => 0
            ]);
        }

        $data = [
            'title'    => $campaignData['title'],
            'settings' => [
                'scheduling_settings'  => Arr::get($campaignData, 'settings.scheduling_settings', []),
                'sending_conditions'   => Arr::get($campaignData, 'settings.sending_conditions', []),
                'subscribers_settings' => Arr::get($campaignData, 'settings.subscribers_settings', []),
            ]
        ];

        $createdCampaign = RecurringCampaign::create($data);

        return [
            'message'     => __('Recurring campaign has been created. Please setup the email contents now'),
            'campaign_id' => $createdCampaign->id
        ];
    }

    public function getCampaign(Request $request, $id)
    {
        $campaign = RecurringCampaign::findOrFail($id);

        return [
            'campaign' => $campaign
        ];
    }

    public function updateCampaignData(Request $request)
    {
        $campaign = RecurringCampaign::findOrFail($request->get('campaign_id'));

        $campaignData = Helper::parseArrayOrJson($request->get('campaign'));

        $this->validate($campaignData, [
            'email_body'    => 'required',
            'email_subject' => 'required'
        ]);

        $campaignData = Arr::only($campaignData, [
            'title',
            'email_subject',
            'email_body',
            'email_pre_header',
            'template_id',
            'utm_status',
            'utm_source',
            'utm_medium',
            'utm_campaign',
            'utm_term',
            'utm_content',
            'design_template',
            'settings'
        ]);

        $campaignData = Sanitize::campaign($campaignData);

        $footerSettings =  Arr::get($campaignData, 'settings.footer_settings');
        if($footerSettings) {
            if (($footerSettings['custom_footer'] == 'yes') && !Helper::hasComplianceText($footerSettings['footer_content'])) {
                return $this->sendError([
                    'message' => __('##crm.manage_subscription_url## or ##crm.unsubscribe_url## string is required for compliance. Please include unsubscription or manage subscription link', 'fluentcampaign-pro')
                ]);
            }
        }

        $campaignData['scheduled_at'] = RecurringCampaignRunner::getNextScheduledAt($campaignData['settings']['scheduling_settings']);

        $campaign->fill($campaignData)->save();

        return [
            'message'  => __('Email data has been updated', 'fluentcampaign-pro'),
            'campaign' => $campaign
        ];
    }

    public function updateCampaignSettings(Request $request, $campaignId)
    {
        $campaign = RecurringCampaign::findOrFail($campaignId);
        $campaignData = Helper::parseArrayOrJson($request->get('campaign'));

        $campaignData['title'] = sanitize_text_field($campaignData['title']);

        if (RecurringCampaign::where('title', $campaignData['title'])->where('id', '!=', $campaign->id)->first()) {
            return $this->sendError([
                'message' => __('Another campaign with the same name already exist. Please provide a different name', 'fluentcampaign-pro'),
            ]);
        }

        $settings = Arr::get($campaignData, 'settings', []);

        $this->validate($campaignData, [
            'title'                             => 'required',
            'settings.scheduling_settings.time' => 'required',
            'settings.scheduling_settings.type' => 'required'
        ]);


        $campaign->settings = $settings;
        $campaign->title = $campaignData['title'];
        $campaign->scheduled_at = RecurringCampaignRunner::getNextScheduledAt($campaignData['settings']['scheduling_settings']);
        $campaign->save();

        RecurringCampaignRunner::setCalculatedScheduledAt();

        return [
            'message'  => __('Settings has been updated', 'fluentcampaign-pro'),
            'campaign' => $campaign
        ];
    }

    public function changeStatus(Request $request, $id)
    {
        $campaign = RecurringCampaign::findOrFail($id);
        $status = $request->get('status');

        if ($status != 'active') {
            $status = 'draft';
        }

        if ($status == 'active') {
            $this->validate([
                'email_subject' => $campaign->email_subject,
                'email_body' => $campaign->email_body
            ], [
                'email_subject' => 'required',
                'email_body' => 'required'
            ]);
        }

        $campaign->status = $status;
        $campaign->scheduled_at = RecurringCampaignRunner::getNextScheduledAt($campaign->settings['scheduling_settings']);

        $campaign->save();

        RecurringCampaignRunner::setCalculatedScheduledAt();

        return [
            'message'    => sprintf(__('Campaign status has been changed to %s', 'fluentcampaign-pro'), $status),
            'new_status' => $status,
            'campaign' => $campaign
        ];
    }

    public function duplicate(Request $request, $id)
    {
        $campaign = RecurringCampaign::findOrFail($id);

        $newData = [
            'title'            => __('[Duplicate] ', 'fluentcampaign-pro') . $campaign->title . ' @ ' . gmdate('Y-m-d'),
            'settings'         => $campaign->settings,
            'template_id'      => $campaign->template_id,
            'email_subject'    => $campaign->email_subject,
            'email_pre_header' => $campaign->email_pre_header,
            'email_body'       => $campaign->email_body,
            'utm_status'       => $campaign->utm_status,
            'utm_source'       => $campaign->utm_source,
            'utm_medium'       => $campaign->utm_medium,
            'utm_campaign'     => $campaign->utm_campaign,
            'utm_term'         => $campaign->utm_term,
            'utm_content'      => $campaign->utm_content,
            'design_template'  => $campaign->design_template,
            'status'           => 'draft'
        ];
        $labelIds = $campaign->getFormattedLabels()->pluck('id')->toArray();

        $newCampaign = RecurringCampaign::create($newData);
        $newCampaign->attachLabels($labelIds);

        return [
            'campaign'    => $newCampaign,
            'campaign_id' => $newCampaign->id,
            'message'     => __('Selected Campaign has been successfully duplicated', 'fluentcampaign-pro')
        ];

    }

    public function deleteBulk(Request $request)
    {
        $campaignIds = (array) $request->get('campaign_ids', []);
        $campaignIds = array_map('intval', $campaignIds);
        $campaignIds = array_unique(array_filter($campaignIds));

        if (empty($campaignIds)) {
            return $this->sendError([
                'message' => 'Please provide valid IDs'
            ]);
        }

        $deleted = $this->deleteCampaignsByIds($campaignIds);

        return $this->sendSuccess([
            'message' => __('Selected Recurring Email Campaigns has been deleted', 'fluentcampaign-pro')
        ]);
    }

    public function getEmails(Request $request, $id)
    {
        $campaign = RecurringCampaign::findOrFail($id);

        $data = [
            'emails' => RecurringMail::where('parent_id', $id)->orderBy('id', 'DESC')->where('status', '!=', 'draft')->paginate()
        ];

        if ($request->get('page') == 1) {
            $data['drafts'] = RecurringMail::where('parent_id', $id)->orderBy('id', 'DESC')->where('status', 'draft')->get();
        }

        return $data;
    }

    public function getEmail(Request $request, $campaignId, $emailId)
    {
        $campaign = RecurringCampaign::findOrFail($campaignId);
        $campaignEmail = RecurringMail::findOrFail($emailId);

        if ($campaignEmail->status == 'scheduled' && $campaignEmail->scheduled_at) {
            if (strtotime($campaignEmail->scheduled_at) < current_time('timestamp')) {
                $campaignEmail->status = 'working';
                $campaignEmail->save();
            }
        }

        return [
            'campaign' => $campaign,
            'email'    => $campaignEmail
        ];
    }

    public function patchCampaignEmail(Request $request, $campaignId, $emailId)
    {
        $campaign = RecurringCampaign::findOrFail($campaignId);
        $campaignEmail = RecurringMail::findOrFail($emailId);

        $status = sanitize_text_field($request->get('status'));

        if ($status) {
            // Change Status here
            $changeFromStatuses = ['draft', 'cancelled'];
            $changeToStatuses = ['draft', 'cancelled'];

            if (in_array($campaignEmail->status, $changeFromStatuses) && in_array($status, $changeToStatuses)) {
                $campaignEmail->status = $status;
                $campaignEmail->save();
                return [
                    'message' => sprintf(__('Email status has been changed to %s', 'fluentcampaign-pro'), $status)
                ];
            }
        }

        return $this->sendError([
            'message' => __('Status change is not allowed for this email', 'fluentcampaign-pro')
        ]);
    }

    public function updateCampaignEmail(Request $request, $campaignId)
    {
        $campaign = RecurringCampaign::findOrFail($campaignId);
        $emailData = Helper::parseArrayOrJson($request->get('email'));
        $step = $request->get('step');

        $email = RecurringMail::where('parent_id', $campaign->id)->findOrFail($emailData['id']);

        if ($step == 'edit') {
            if (empty($emailData['email_body'])) {
                return $this->sendError([
                    'message' => __('Email body is required', 'fluentcampaign-pro')
                ]);
            }

            $email->email_body = $emailData['email_body'];
            $email->settings = $emailData['settings'];
            $email->design_template = $emailData['design_template'];
            $email->save();
            return [
                'message' => __('Email body has been successfully updated', 'fluentcampaign-pro')
            ];
        }

        if ($step == 'review') {
            $this->validate($emailData, [
                'email_subject' => 'required',
                'scheduled_at'  => 'required',
                'settings'      => 'required',
                'status'        => 'required'
            ]);

            if (strtotime($emailData['scheduled_at']) < current_time('timestamp')) {
                $emailData['scheduled_at'] = current_time('mysql');
            }

            if ($emailData['status'] == 'pending-scheduled' && $campaign->status != 'active') {
                return $this->sendError([
                    'message' => sprintf('Recurring campaign status is set to %s. You cannot publish this email. Please make your recurring campaign status as active first.', $campaign->status)
                ]);
            }

            if ($emailData['status'] == 'pending-scheduled' && $email->status == 'draft') {
                fluentcrm_update_campaign_meta($campaign->id, '_recipient_processed', 0);
                fluentcrm_update_campaign_meta($campaign->id, '_last_recipient_id', 0);
            }

            $email->status = $emailData['status'];
            $email->email_subject = $emailData['email_subject'];
            $email->scheduled_at = $emailData['scheduled_at'];
            $email->settings = $emailData['settings'];
            $email->recipients_count = 0;
            $email->save();

            return [
                'message' => __('Settings has been successfully updated', 'fluentcampaign-pro')
            ];
        }

    }

    public function handleBulkAction(Request $request)
    {
        $actionName = $request->getSafe('action_name', 'sanitize_text_field', '');
        $campaignIds = array_map('intval', (array)$request->get('campaign_ids', []));
        $selectAllCampaigns = filter_var($request->get('select_all'), FILTER_VALIDATE_BOOLEAN);
        $campaignIds = array_map(function ($id) {
            return (int)$id;
        }, $campaignIds);

        $campaignIds = array_unique(array_filter($campaignIds));

        // all campaigns selected for bulk action
        if ($selectAllCampaigns) {
            $campaignIds = RecurringCampaign::pluck('id')->toArray();
        }

        if (!$campaignIds) {
            return $this->sendError([
                'message' => __('Please provide Recurring Campaign IDs', 'fluentcampaign-pro')
            ]);
        }

        if ($actionName == 'apply_labels') {
            // labels are coming as array of ids from request
            $newLabels = (array) $request->get('labels', []);
            $newLabels = array_map('intval', $newLabels);
            $newLabels = array_unique(array_filter($newLabels));

            if (!$newLabels) {
                return $this->sendError([
                    'message' => __('Please provide labels', 'fluent-crm')
                ]);
            }

            $applied = $this->applyLabels($campaignIds, $newLabels);

            if ($applied) {
                return $this->sendSuccess([
                    'message' => __('Labels has been applied successfully', 'fluentcampaign-pro')
                ]);
            }

        }

        if ($actionName == 'delete_campaigns') {
            $deleted = $this->deleteCampaignsByIds($campaignIds);

            return $this->sendSuccess([
                'message' => __('Selected Recurring Email Campaigns has been deleted', 'fluentcampaign-pro')
            ]);
        }

        return $this->sendError([
            'message' => __('invalid bulk action', 'fluent-crm')
        ]);
    }

    /**
     * Apply labels to the specified recurring campaigns.
     *
     * @param array $campaignIds An array of campaign IDs to which the labels will be applied.
     * @param array $newLabels An array of new labels to be applied to the campaigns.
     * @return bool Returns true after successfully applying the labels.
     */
    private function applyLabels($campaignIds, $newLabels)
    {
        // Retrieve the recurring campaigns based on the provided campaign IDs
        $rcampaigns = RecurringCampaign::whereIn('id', $campaignIds)->get();

        foreach ($rcampaigns as $campaign) {
            $campaign->attachLabels($newLabels);
        }

        return true;
    }

    private function deleteCampaignsByIds($campaignIds)
    {
        $campaigns = RecurringCampaign::whereIn('id', $campaignIds)->get();
        if ($campaigns->isEmpty()) {
            return $this->sendError([
                'message' => 'No campaigns found based on your request'
            ]);
        }

        foreach ($campaigns as $campaign) {
            // Delete the child campaigns
            $childIds = RecurringMail::where('parent_id', $campaign->id)->pluck('id')->toArray();

            if ($childIds) {
                CampaignEmail::whereIn('campaign_id', $childIds)->delete();
                RecurringMail::whereIn('id', $childIds)->delete();

                foreach ($childIds as $childId) {
                    do_action('fluent_crm/campaign_deleted', $childId);
                }
            }

            $campaignId = $campaign->id;

            $campaign->delete();
            do_action('fluent_crm/campaign_deleted', $campaignId);
        }

        return $this->sendSuccess([
            'message' => __('Recurring Email campaign has been deleted', 'fluentcampaign-pro')
        ]);

    }

    public function updateLabels(Request $request, $campaign_id)
    {
        $campaign = RecurringCampaign::findOrFail($campaign_id);
        $action = $request->getSafe('action', 'sanitize_text_field');
        $labelIds = $request->get('label_ids', []);

        $labelIds = is_array($labelIds) ? $labelIds : [$labelIds];

        if ($action == 'attach') {
            $campaign->attachLabels($labelIds);
        } else {
            $campaign->detachLabels($labelIds);
        }

        return $this->sendSuccess([
            'message' => __('Labels has been updated', 'fluent-crm')
        ]);

    }



}
