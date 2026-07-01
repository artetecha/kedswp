<?php

namespace FluentCampaign\App\Http\Controllers;

use FluentCampaign\App\Models\Sequence;
use FluentCampaign\App\Models\SequenceMail;
use FluentCampaign\App\Models\SequenceTracker;
use FluentCampaign\App\Http\Controllers\Controller;
use FluentCrm\App\Models\Campaign;
use FluentCrm\App\Models\CampaignEmail;
use FluentCrm\App\Models\CampaignUrlMetric;
use FluentCrm\App\Models\Subscriber;
use FluentCrm\App\Services\Helper;
use FluentCrm\Framework\Support\Arr;
use FluentCrm\Framework\Http\Request\Request;
use FluentCrm\Framework\Validator\ValidationException;

class SequenceController extends Controller
{
    public function sequences(Request $request)
    {
        // Sequence uses fc_campaigns table (campaigns with type='email_sequence'),
        // so the allowlist mirrors fc_campaigns columns. Required because the
        // framework rewrite made orderBy() throw on names that don't match
        // ^[a-zA-Z0-9_\.]+$.
        $allowedOrderBy = [
            'id', 'parent_id', 'type', 'title', 'available_urls', 'slug',
            'status', 'template_id', 'email_subject', 'email_pre_header',
            'email_body', 'recipients_count', 'delay', 'utm_status',
            'utm_source', 'utm_medium', 'utm_campaign', 'utm_term',
            'utm_content', 'design_template', 'scheduled_at', 'settings',
            'created_by', 'created_at', 'updated_at',
        ];
        $order = strtoupper((string) $request->get('order'));
        $order = in_array($order, ['ASC', 'DESC'], true) ? $order : 'DESC';
        $orderBy = sanitize_key((string) ($request->get('orderBy') ?: 'id'));
        if (!in_array($orderBy, $allowedOrderBy, true)) {
            $orderBy = 'id';
        }

        $sequences = Sequence::orderBy($orderBy, $order);
        if (!empty($request->get('search'))) {
            $sequences = $sequences->where('title', 'LIKE', '%' . $request->get('search') . '%');
        }
        $sequences = $sequences->paginate();

        $with = $request->get('with', []);
        if (in_array('stats', $with)) {
            foreach ($sequences as $sequence) {
                $sequence->stats = $sequence->stat();
            }
        }

        return $this->sendSuccess(compact('sequences'));
    }

    public function create(Request $request)
    {
        try {
            $data = $this->validate($request->only('title'), [
                'title' => 'required|unique:fc_campaigns',
            ]);

            return $this->sendSuccess([
                'sequence' => Sequence::create($data),
                'message'  => __('Sequence has been created', 'fluentcampaign-pro')
            ]);
        } catch (ValidationException $e) {
            return $this->validationErrors($e);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $data = $this->validate($request->only(['title', 'settings', 'id']), [
                // The title must be unique because the slug
                'title' => 'required'
            ]);

            $existing = Sequence::findOrFail($id);

            if (isset($data['settings']) && empty($data['settings'])) {
                unset($data['settings']);
            } else {
                $mailerSettings = Arr::get($data, 'settings.mailer_settings');
                $existingMailerSettings = Arr::get($existing->settings, 'mailer_settings', []);
                if (array_diff($existingMailerSettings, $mailerSettings)) {
                    // It's a change
                    $data['settings']['mailer_settings'] = $mailerSettings;
                    $sequenceMails = SequenceMail::where('parent_id', $id)->get();
                    foreach ($sequenceMails as $sequenceMail) {
                        $sequenceMail->updateMailerSettings($mailerSettings);
                    }
                }
            }

            $existing->fill($data)->save();

            return $this->sendSuccess([
                'sequence' => $existing,
                'message'  => __('Sequence has been updated', 'fluentcampaign-pro')
            ]);
        } catch (ValidationException $e) {
            return $this->validationErrors($e);
        }
    }

    public function duplicate(Request $request, $id)
    {
        $sequence = Sequence::findOrFail($id);

        $sequenceData = [
            'title'           => __('[Duplicate] ', 'fluentcampaign-pro') . $sequence->title,
            'settings'        => $sequence->settings,
            'design_template' => $sequence->design_template
        ];

        $createdSequence = Sequence::create($sequenceData);

        $sequenceEmails = SequenceMail::where('parent_id', $id)
            ->orderBy('delay', 'ASC')
            ->get()->toArray();

        foreach ($sequenceEmails as $email) {
            $emailData = Arr::only($email, [
                'title',
                'type',
                'available_urls',
                'status',
                'template_id',
                'email_subject',
                'email_pre_header',
                'email_body',
                'delay',
                'utm_status',
                'utm_source',
                'utm_medium',
                'utm_campaign',
                'utm_term',
                'utm_content',
                'design_template',
                'scheduled_at',
                'settings'
            ]);

            $emailData['template_id'] = intval($emailData['template_id']);

            $emailData = array_filter($emailData);

            $emailData['parent_id'] = $createdSequence->id;

            $createdMail = SequenceMail::create($emailData);

            if ($createdMail->design_template == 'visual_builder') {
                $oldDesign = fluentcrm_get_campaign_meta($email['id'], '_visual_builder_design', true);
                fluentcrm_update_campaign_meta($createdMail->id, '_visual_builder_design', $oldDesign);
            }
        }

        return [
            'sequence' => $createdSequence,
            'message'  => __('Selected sequence has been successfully duplicated', 'fluentcampaign-pro')
        ];

    }

    public function sequence(Request $request, $id)
    {
        $sequence = Sequence::find($id);
        $data['sequence'] = $sequence;
        $with = $request->get('with', []);
        if (in_array('sequence_emails', $with)) {
            $sequenceEmails = SequenceMail::where('parent_id', $id)
                ->orderBy('delay', 'ASC')
                ->get();
            if (in_array('email_stats', $with)) {
                $emailIds = $sequenceEmails->pluck('id')->toArray();
                if ($emailIds) {
                    // Batch email stats in a single GROUP BY query
                    $emailStats = fluentCrmDb()->table('fc_campaign_emails')
                        ->select('campaign_id')
                        ->selectRaw('COUNT(*) as total')
                        ->selectRaw("SUM(CASE WHEN status = 'sent' THEN 1 ELSE 0 END) as sent")
                        ->selectRaw("SUM(CASE WHEN is_open = 1 THEN 1 ELSE 0 END) as views")
                        ->whereIn('campaign_id', $emailIds)
                        ->groupBy('campaign_id')
                        ->get()
                        ->keyBy('campaign_id');

                    // Batch click counts
                    $clickCounts = fluentCrmDb()->table('fc_campaign_url_metrics')
                        ->select('campaign_id')
                        ->selectRaw('COUNT(*) as total')
                        ->where('type', 'click')
                        ->whereIn('campaign_id', $emailIds)
                        ->groupBy('campaign_id')
                        ->get()
                        ->keyBy('campaign_id');

                    // Batch unsubscribe counts
                    $unsubCounts = fluentCrmDb()->table('fc_campaign_url_metrics')
                        ->select('campaign_id')
                        ->selectRaw('COUNT(DISTINCT subscriber_id) as total')
                        ->where('type', 'unsubscribe')
                        ->whereIn('campaign_id', $emailIds)
                        ->groupBy('campaign_id')
                        ->get()
                        ->keyBy('campaign_id');

                    // Batch revenue meta
                    $revenueMeta = fluentCrmDb()->table('fc_meta')
                        ->where('key', '_campaign_revenue')
                        ->where('object_type', 'FluentCrm\App\Models\Campaign')
                        ->whereIn('object_id', $emailIds)
                        ->get()
                        ->keyBy('object_id');

                    foreach ($sequenceEmails as $sequenceEmail) {
                        $stat = $emailStats[$sequenceEmail->id] ?? null;
                        $click = $clickCounts[$sequenceEmail->id] ?? null;
                        $unsub = $unsubCounts[$sequenceEmail->id] ?? null;
                        $revMeta = $revenueMeta[$sequenceEmail->id] ?? null;

                        $revs = false;
                        if ($revMeta && $revMeta->value) {
                            $revenueData = maybe_unserialize($revMeta->value);
                            if (is_array($revenueData) || is_object($revenueData)) {
                                $amounts = [];
                                $currencies = [];
                                foreach ((array) $revenueData as $currency => $cents) {
                                    if (is_numeric($cents) && $cents && $currency !== 'orderIds') {
                                        $money = (int) $cents / 100;
                                        $amounts[] = number_format($money, (is_int($money)) ? 0 : 2);
                                        $currencies[] = $currency;
                                    }
                                }
                                if ($amounts && $currencies) {
                                    $revs = [
                                        'total' => implode(' | ', $amounts),
                                        'label' => 'Revenue (' . implode(' | ', $currencies) . ')'
                                    ];
                                }
                            }
                        }

                        $sequenceEmail->stats = [
                            'total'         => $stat ? (int) $stat->total : 0,
                            'sent'          => $stat ? (int) $stat->sent : 0,
                            'clicks'        => $click ? (int) $click->total : 0,
                            'views'         => $stat ? (int) $stat->views : 0,
                            'unsubscribers' => $unsub ? (int) $unsub->total : 0,
                            'revenue'       => $revs
                        ];
                    }
                }
            }
            $data['sequence_emails'] = $sequenceEmails;
        }

        return $this->sendSuccess($data);
    }

    public function delete(Request $request, $id)
    {
        Sequence::where('id', $id)->delete();
        $sequenceCampaignIds = SequenceMail::where('parent_id', $id)->pluck('id')->toArray();
        if ($sequenceCampaignIds) {
            SequenceMail::where('parent_id', $id)->delete();
            CampaignEmail::whereIn('campaign_id', $sequenceCampaignIds)->delete();
            CampaignUrlMetric::whereIn('campaign_id', $sequenceCampaignIds)->delete();
            SequenceTracker::where('campaign_id', $id)->delete();

            foreach ($sequenceCampaignIds as $sequenceCampaignId) {
                fluentcrm_delete_campaign_meta($sequenceCampaignId, '');
            }
        }

        do_action('fluentcrm_sequence_deleted', $id);

        return $this->sendSuccess([
            'message' => __('Email sequence successfully deleted', 'fluentcampaign-pro')
        ]);
    }

    public function subscribe(Request $request, $sequenceId)
    {
        $page = (int)$request->get('page', 1);
        $subscribersSettings = [
            'subscribers'         => $request->get('subscribers'),
            'excludedSubscribers' => $request->get('excludedSubscribers'),
            'sending_filter'      => $request->get('sending_filter', 'list_tag'),
            'dynamic_segment'     => $request->get('dynamic_segment'),
            'advanced_filters'    => Helper::parseArrayOrJson($request->get('advanced_filters'))
        ];

        $campaign = new Campaign;

        $data = $campaign->getSubscriberIdsBySegmentSettings($subscribersSettings);

        $subscriberIds = $data['subscriber_ids'];
        $inTotal = count($subscriberIds);
        if (!count($subscriberIds)) {
            return $this->sendError([
                'message' => __('No Subscribers found based on your selection', 'fluentcampaign-pro')
            ]);
        }

        $alreadySubscriberIds = SequenceTracker::where('campaign_id', $sequenceId)->pluck('subscriber_id')->toArray();

        $subscriberIds = array_diff($subscriberIds, $alreadySubscriberIds);

        $totalSubscribers = count($subscriberIds);

        $processPerRequest = (int) apply_filters('fluent_crm/process_subscribers_per_request', 200);

        $subscriberIds = array_slice($subscriberIds, 0, $processPerRequest);

        $subscribers = Subscriber::whereIn('id', $subscriberIds)->get();

        $sequence = Sequence::find($sequenceId);
        if (!$sequence) {
            return $this->sendError([
                'message' => __('Email sequence could not be found', 'fluentcampaign-pro')
            ]);
        }

        $sequence->subscribe($subscribers);

        $remaining = $totalSubscribers - count($subscribers);

        if ($remaining <= 0) {
            $remaining = 0;
        }

        return $this->sendSuccess([
            'total'      => $totalSubscribers,
            'remaining'  => $remaining,
            'next_page'  => $page + 1,
            'page_total' => ceil($totalSubscribers / $processPerRequest),
            'in_total'   => $inTotal
        ]);
    }

    public function getSubscribers(Request $request, $sequenceId)
    {
        return SequenceTracker::where('campaign_id', $sequenceId)
            ->orderBy('id', 'DESC')
            ->with('subscriber')
            ->paginate();
    }

    public function deleteSubscribes(Request $request, $sequenceId)
    {
        if ($trackerIds = $request->get('tracker_ids', [])) {
            SequenceTracker::where('campaign_id', $sequenceId)
                ->whereIn('id', $trackerIds)
                ->delete();
        } else if ($subscriberIds = $request->get('subscriber_ids', [])) {
            SequenceTracker::where('campaign_id', $sequenceId)
                ->whereIn('subscriber_id', $subscriberIds)
                ->delete();
        }

        return [
            'message' => __('Selected subscribers has been successfully removed from this sequence', 'fluentcampaign-pro')
        ];

    }

    public function subscriberSequences(Request $request, $subscriberId)
    {
        $sequenceTrackers = SequenceTracker::where('subscriber_id', $subscriberId)
            ->with(['sequence', 'last_sequence', 'next_sequence'])
            ->orderBy('id', 'DESC')
            ->paginate();

        return [
            'sequence_trackers' => $sequenceTrackers
        ];
    }

    public function handleBulkAction(Request $request)
    {
        $sequenceIds = array_map('intval', (array)$request->get('sequence_ids', []));

        $sequenceIds = array_unique(array_filter($sequenceIds));

        $selectAllSequences = filter_var($request->get('select_all'), FILTER_VALIDATE_BOOLEAN);

        $sequenceIds = array_map(function ($id) {
            return (int)$id;
        }, $sequenceIds);

        $sequenceIds = array_filter($sequenceIds);

        if ($selectAllSequences) {
            $sequenceIds = Sequence::pluck('id')->toArray();
        }

        if (!$sequenceIds) {
            return $this->sendError([
                'message' => __('Please provide email sequence IDs', 'fluentcampaign-pro')
            ]);
        }

        Sequence::whereIn('id', $sequenceIds)->delete();
        $sequenceCampaignIds = SequenceMail::whereIn('parent_id', $sequenceIds)->pluck('id')->toArray();
        if ($sequenceCampaignIds) {
            SequenceMail::whereIn('parent_id', $sequenceIds)->delete();
            CampaignEmail::whereIn('campaign_id', $sequenceCampaignIds)->delete();
            CampaignUrlMetric::whereIn('campaign_id', $sequenceCampaignIds)->delete();

            foreach ($sequenceCampaignIds as $sequenceCampaignId) {
                fluentcrm_delete_campaign_meta($sequenceCampaignId, '');
            }
        }

        return $this->sendSuccess([
            'message' => __('Selected Sequences has been deleted permanently', 'fluentcampaign-pro'),
        ]);
    }

    public function reapplySequence(Request $request, $id)
    {
        $sequence = Sequence::findOrFail($id);
        $result = $sequence->reapplySequence();

        if ($result) {
            return [
                'result' => $result,
                'message' => __('Sequences have been re-applied', 'fluentcampaign-pro')
            ];
        }

        return $this->sendError([
            'message' => __('No sequences found to re-apply', 'fluentcampaign-pro')
        ]);
    }
}
