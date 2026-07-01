<?php

namespace FluentCampaign\App\Modules\MCP\Tools;

use FluentCampaign\App\Models\Sequence;
use FluentCampaign\App\Models\SequenceMail;
use FluentCampaign\App\Models\SequenceTracker;
use FluentCrm\App\Models\CampaignEmail;
use FluentCrm\App\Models\CampaignUrlMetric;
use FluentCrm\App\Models\Subscriber;
use FluentCrm\App\Modules\MCP\Helpers\MCPHelper;
use FluentCrm\App\Services\ContactsQuery;

/**
 * Pro sequence tools — list-sequences, get-sequence, manage-sequence-subscribers.
 *
 * Reads query Sequence/SequenceMail/SequenceTracker directly. Writes delegate
 * to Sequence::subscribe() (subscribe action) or SequenceTracker::delete()
 * (unsubscribe), matching SequenceController's existing paths.
 */
class SequenceTools
{
    // -----------------------------------------------------------------
    // list-sequences
    // -----------------------------------------------------------------

    public static function listSequences($params)
    {
        $params = (array) $params;

        MCPHelper::paginationFromInput($params);

        $search    = sanitize_text_field((string) ($params['search'] ?? ''));
        // Sequence uses fc_campaigns table — allowlist covers every column so
        // agents can sort by any real field, not just the 4 most common ones.
        // Required because the framework rewrite made orderBy() throw on names
        // that don't match ^[a-zA-Z0-9_\.]+$.
        $allowedSortBy = [
            'id', 'parent_id', 'type', 'title', 'available_urls', 'slug',
            'status', 'template_id', 'email_subject', 'email_pre_header',
            'email_body', 'recipients_count', 'delay', 'utm_status',
            'utm_source', 'utm_medium', 'utm_campaign', 'utm_term',
            'utm_content', 'design_template', 'scheduled_at', 'settings',
            'created_by', 'created_at', 'updated_at',
        ];
        $sortBy    = sanitize_key((string) ($params['sort_by'] ?? 'id'));
        if (!in_array($sortBy, $allowedSortBy, true)) {
            $sortBy = 'id';
        }
        $sortType  = strtoupper(sanitize_text_field((string) ($params['sort_type'] ?? 'DESC')));
        $sortType  = in_array($sortType, ['ASC', 'DESC'], true) ? $sortType : 'DESC';
        $withStats = !isset($params['include_stats']) ? true : (bool) $params['include_stats'];

        $query = Sequence::orderBy($sortBy, $sortType);
        if ($search !== '') {
            global $wpdb;
            $like = '%' . $wpdb->esc_like($search) . '%';
            $query->where('title', 'LIKE', $like);
        }

        $paginated = $query->paginate();

        $sequenceList = $paginated->items();

        // Batch stats: 2 queries for the whole page instead of 2+N per row.
        $emailCountMap     = [];
        $subscriberCountMap = [];
        if ($withStats && $sequenceList) {
            $pageIds = array_map(function ($s) { return (int) $s->id; }, $sequenceList);

            $emailCountMap = SequenceMail::selectRaw('parent_id, COUNT(*) as cnt')
                ->whereIn('parent_id', $pageIds)
                ->groupBy('parent_id')
                ->pluck('cnt', 'parent_id')
                ->toArray();

            $subscriberCountMap = SequenceTracker::selectRaw('campaign_id, COUNT(*) as cnt')
                ->whereIn('campaign_id', $pageIds)
                ->groupBy('campaign_id')
                ->pluck('cnt', 'campaign_id')
                ->toArray();
        }

        $items = [];
        foreach ($sequenceList as $sequence) {
            $row = [
                'id'         => (int) $sequence->id,
                'title'      => $sequence->title,
                'status'     => $sequence->status,
                'created_at' => MCPHelper::toIso8601($sequence->created_at),
                'updated_at' => MCPHelper::toIso8601($sequence->updated_at),
            ];
            if ($withStats) {
                $row['stats'] = [
                    'emails'      => (int) ($emailCountMap[$sequence->id] ?? 0),
                    'subscribers' => (int) ($subscriberCountMap[$sequence->id] ?? 0),
                    'revenue'     => null,
                ];
            }
            $items[] = $row;
        }

        return [
            'items'    => $items,
            'total'    => (int) $paginated->total(),
            'page'     => (int) $paginated->currentPage(),
            'per_page' => (int) $paginated->perPage(),
            'pages'    => (int) $paginated->lastPage(),
        ];
    }

    // -----------------------------------------------------------------
    // get-sequence
    // -----------------------------------------------------------------

    public static function getSequence($params)
    {
        $params = (array) $params;
        $sequenceId = (int) ($params['sequence_id'] ?? 0);
        if (!$sequenceId) {
            return MCPHelper::error('invalid_param', __('sequence_id is required', 'fluentcampaign-pro'));
        }

        $sequence = Sequence::find($sequenceId);
        if (!$sequence) {
            return MCPHelper::error('not_found', __('Sequence not found', 'fluentcampaign-pro'), ['sequence_id' => $sequenceId]);
        }

        $includeEmailStats = !isset($params['include_email_stats']) ? true : (bool) $params['include_email_stats'];

        // Bodies are opt-in (review #7) — a 5-email sequence with full
        // HTML+text bodies easily blows past 10K tokens of agent context
        // for a request that probably just needed titles + delays. Accept
        // either the boolean param OR an `include=["bodies"]` array form
        // because round-2 review noted agents intuitively try the latter.
        $includeArr = (array) ($params['include'] ?? []);
        $includeBodies = !empty($params['include_bodies']) || in_array('bodies', $includeArr, true);

        $emails = SequenceMail::where('parent_id', $sequenceId)
            ->orderBy('delay', 'ASC')
            ->get();

        // Batch email stats: 5 grouped queries for all emails at once instead
        // of 5 queries × N emails (100 queries for a 20-email sequence).
        $emailStatsMap = [];
        if ($includeEmailStats && $emails->isNotEmpty()) {
            $emailIds = $emails->pluck('id')->toArray();

            $totals = CampaignEmail::selectRaw('campaign_id, COUNT(*) as cnt')
                ->whereIn('campaign_id', $emailIds)
                ->groupBy('campaign_id')
                ->pluck('cnt', 'campaign_id')->toArray();

            $sent = CampaignEmail::selectRaw('campaign_id, COUNT(*) as cnt')
                ->whereIn('campaign_id', $emailIds)
                ->where('status', 'sent')
                ->groupBy('campaign_id')
                ->pluck('cnt', 'campaign_id')->toArray();

            $clicks = CampaignUrlMetric::selectRaw('campaign_id, COUNT(*) as cnt')
                ->whereIn('campaign_id', $emailIds)
                ->where('type', 'click')
                ->groupBy('campaign_id')
                ->pluck('cnt', 'campaign_id')->toArray();

            $opens = CampaignEmail::selectRaw('campaign_id, COUNT(*) as cnt')
                ->whereIn('campaign_id', $emailIds)
                ->where('is_open', 1)
                ->groupBy('campaign_id')
                ->pluck('cnt', 'campaign_id')->toArray();

            $unsubs = CampaignUrlMetric::selectRaw('campaign_id, COUNT(DISTINCT subscriber_id) as cnt')
                ->whereIn('campaign_id', $emailIds)
                ->where('type', 'unsubscribe')
                ->groupBy('campaign_id')
                ->pluck('cnt', 'campaign_id')->toArray();

            foreach ($emailIds as $eid) {
                $emailStatsMap[$eid] = [
                    'total'         => (int) ($totals[$eid] ?? 0),
                    'sent'          => (int) ($sent[$eid] ?? 0),
                    'clicks'        => (int) ($clicks[$eid] ?? 0),
                    'views'         => (int) ($opens[$eid] ?? 0),
                    'unsubscribers' => (int) ($unsubs[$eid] ?? 0),
                    'revenue'       => false,
                ];
            }
        }

        $emailsOut = [];
        foreach ($emails as $email) {
            // settings.timings carries the human-friendly unit; the model's
            // `delay` column is stored in seconds (strtotime("X unit")).
            $settings = is_array($email->settings) ? $email->settings : (array) maybe_unserialize($email->settings);
            $timings  = $settings['timings'] ?? [];
            $unit     = isset($timings['delay_unit']) && $timings['delay_unit'] !== ''
                ? sanitize_key((string) $timings['delay_unit'])
                : self::secondsToUnit((int) $email->delay);

            $row = [
                'id'              => (int) $email->id,
                'title'           => $email->title,
                'delay_seconds'   => (int) $email->delay,
                'delay'           => isset($timings['delay']) && $timings['delay'] !== ''
                    ? (int) $timings['delay']
                    : self::secondsToValue((int) $email->delay, $unit),
                'delay_unit'      => $unit,
                'email_subject'   => $email->email_subject,
                'email_pre_header' => $email->email_pre_header,
                'design_template' => $email->design_template,
                'status'          => $email->status,
            ];
            if ($includeBodies) {
                $body = (string) $email->email_body;
                $row['body_html'] = $body;
                $row['body_text'] = MCPHelper::htmlToText($body);
            }
            if ($includeEmailStats && isset($emailStatsMap[$email->id])) {
                $row['stats'] = $emailStatsMap[$email->id];
            }
            $emailsOut[] = $row;
        }

        $subscribersCount = SequenceTracker::where('campaign_id', $sequenceId)->count();

        return [
            'id'                => (int) $sequence->id,
            'title'             => $sequence->title,
            'status'            => $sequence->status,
            'settings'          => is_array($sequence->settings) ? $sequence->settings : [],
            'subscribers_count' => (int) $subscribersCount,
            'emails'            => $emailsOut,
            'created_at'        => MCPHelper::toIso8601($sequence->created_at),
            'updated_at'        => MCPHelper::toIso8601($sequence->updated_at),
        ];
    }

    /**
     * Pick the best human unit for a delay measured in seconds.
     */
    private static function secondsToUnit($seconds)
    {
        if ($seconds <= 0) return 'minutes';
        if ($seconds % 86400 === 0) return 'days';
        if ($seconds % 3600  === 0) return 'hours';
        return 'minutes';
    }

    private static function secondsToValue($seconds, $unit)
    {
        switch ($unit) {
            case 'days':    return (int) round($seconds / 86400);
            case 'hours':   return (int) round($seconds / 3600);
            case 'minutes':
            default:        return (int) round($seconds / 60);
        }
    }

    // -----------------------------------------------------------------
    // manage-sequence-subscribers
    // -----------------------------------------------------------------

    public static function manageSubscribers($params)
    {
        $params = (array) $params;
        $sequenceId = (int) ($params['sequence_id'] ?? 0);
        $action     = sanitize_key((string) ($params['action'] ?? ''));

        if (!$sequenceId) {
            return MCPHelper::error('invalid_param', __('sequence_id is required', 'fluentcampaign-pro'));
        }
        if (!in_array($action, ['subscribe', 'unsubscribe'], true)) {
            return MCPHelper::error('invalid_param', __('Invalid action', 'fluentcampaign-pro'));
        }

        $sequence = Sequence::find($sequenceId);
        if (!$sequence) {
            return MCPHelper::error('not_found', __('Sequence not found', 'fluentcampaign-pro'), ['sequence_id' => $sequenceId]);
        }

        $cap = (int) apply_filters('fluent_crm/mcp_bulk_cap', 5000, 'manage-sequence-subscribers');

        $contactIds = isset($params['contact_ids']) ? array_filter(array_map('intval', (array) $params['contact_ids'])) : [];
        $filter     = $params['filter'] ?? null;
        $dryRun     = !empty($params['dry_run']);

        if (!$contactIds && !$filter) {
            return MCPHelper::error('invalid_param', __('Provide contact_ids or filter', 'fluentcampaign-pro'));
        }

        $matchedTotal = 0;
        if (!$contactIds) {
            $validation = MCPHelper::validateUniversalFilter((array) $filter);
            if (is_wp_error($validation)) {
                return $validation;
            }
            $args = MCPHelper::buildContactsQueryArgs((array) $filter);
            $args['with'] = [];
            $cq = new ContactsQuery($args);
            MCPHelper::applyDateFilters($cq, (array) $filter);
            $query = $cq->getModel();
            $matchedTotal = (int) $query->count();
            // Dry runs surface the matched count even when over cap so
            // the agent can decide how to batch — same convention as
            // apply-segments-to-contacts.
            if ($matchedTotal > $cap && !$dryRun) {
                return MCPHelper::error('cap_reached', __('Too many contacts match the filter', 'fluentcampaign-pro'), [
                    'max'     => $cap,
                    'matched' => $matchedTotal,
                ]);
            }
            $contactIds = array_map('intval', $query->limit($cap)->pluck('id')->toArray());
        } else {
            $matchedTotal = count($contactIds);
            if ($matchedTotal > $cap && !$dryRun) {
                return MCPHelper::error('cap_reached', __('Too many contact_ids in a single call', 'fluentcampaign-pro'), [
                    'max'     => $cap,
                    'matched' => $matchedTotal,
                ]);
            }
        }

        $excludeIds = isset($params['exclude_contact_ids'])
            ? array_filter(array_map('intval', (array) $params['exclude_contact_ids']))
            : [];
        if ($excludeIds) {
            $contactIds = array_diff($contactIds, $excludeIds);
        }

        $exceedsCap = $matchedTotal > $cap;

        // When over cap in dry_run, skip the SequenceTracker lookup entirely:
        // the contactIds set is a capped sample (filter path) or the full
        // uncapped array (contact_ids path), so would_enroll/already_enrolled
        // would be misleading. Return null to signal "unavailable".
        if ($dryRun && $exceedsCap) {
            return [
                'ok'                  => true,
                'dry_run'             => true,
                'action'              => $action,
                'matched_contacts'    => $matchedTotal,
                'cap'                 => $cap,
                'exceeds_cap'         => true,
                'batches_required'    => (int) ceil($matchedTotal / max(1, $cap)),
                'would_enroll'        => null,
                'already_enrolled'    => null,
                'would_unsubscribe'   => null,
                'note'                => __('Dry run — match exceeds the per-call cap. Enroll estimates unavailable for over-cap previews. Apply by passing contact_ids in batches.', 'fluentcampaign-pro'),
            ];
        }

        // Cap the lookup to $cap items — contact_ids path with dry_run=true
        // could carry an uncapped array that would produce a MySQL crash on
        // a large IN() list.
        $contactIdsForLookup = array_slice(array_values($contactIds), 0, $cap);

        // Resolve the already-enrolled / not-yet-enrolled split once so
        // both the dry-run and live paths report the same numbers.
        $alreadyEnrolled = SequenceTracker::where('campaign_id', $sequenceId)
            ->whereIn('subscriber_id', $contactIdsForLookup)
            ->pluck('subscriber_id')
            ->toArray();
        $alreadyEnrolledIds = array_map('intval', $alreadyEnrolled);
        $newIds             = array_values(array_diff($contactIdsForLookup, $alreadyEnrolledIds));

        if ($dryRun) {
            return [
                'ok'                  => true,
                'dry_run'             => true,
                'action'              => $action,
                'matched_contacts'    => $matchedTotal,
                'cap'                 => $cap,
                'exceeds_cap'         => false,
                'batches_required'    => 1,
                'would_enroll'        => $action === 'subscribe' ? count($newIds) : 0,
                'already_enrolled'    => count($alreadyEnrolledIds),
                'would_unsubscribe'   => $action === 'unsubscribe' ? count($alreadyEnrolledIds) : 0,
                'note'                => __('Dry run — nothing was changed. Re-run without dry_run=true to commit.', 'fluentcampaign-pro'),
            ];
        }

        $contactIds = $contactIdsForLookup;

        if ($action === 'subscribe') {
            if (!$newIds) {
                return [
                    'ok'                => true,
                    'action'            => 'subscribe',
                    'matched_contacts'  => count($contactIds),
                    'enrolled_now'      => 0,
                    'already_enrolled'  => count($alreadyEnrolledIds),
                ];
            }

            $subscribers = Subscriber::whereIn('id', array_values($newIds))->get();
            $sequence->subscribe($subscribers);

            return [
                'ok'               => true,
                'action'           => 'subscribe',
                'matched_contacts' => count($contactIds),
                'enrolled_now'     => count($subscribers),
                'already_enrolled' => count($alreadyEnrolledIds),
            ];
        }

        // unsubscribe — delegates to Sequence::unsubscribe() so scheduled
        // fc_campaign_emails rows are cancelled alongside the tracker entries.
        $sequence->unsubscribe($contactIds);

        return [
            'ok'      => true,
            'action'  => 'unsubscribe',
            'removed' => count($contactIds),
        ];
    }
}
