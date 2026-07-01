<?php

namespace FluentCampaign\App\Modules\MCP\Tools;

use FluentCrm\App\Modules\MCP\Helpers\MCPHelper;
use FluentCrm\App\Services\ContactsQuery;

/**
 * Pro `estimate-dynamic-segment` — count-only preview of a filter shape.
 *
 * Reuses the universal-filter translator from MCPHelper so segments expressed
 * in the agent-friendly shape evaluate identically here and in
 * `apply-segments-to-contacts` / `upsert-campaign.recipients`.
 */
class DynamicSegmentTools
{
    public static function estimate($params)
    {
        $params = (array) $params;
        $filter = $params['filter'] ?? null;

        if (!is_array($filter) || empty($filter)) {
            return MCPHelper::error('invalid_param', __('filter is required', 'fluentcampaign-pro'));
        }
        $validation = MCPHelper::validateUniversalFilter($filter);
        if (is_wp_error($validation)) {
            return $validation;
        }

        $start = microtime(true);
        $args  = MCPHelper::buildContactsQueryArgs($filter);
        $args['with'] = [];

        $cq = new ContactsQuery($args);
        MCPHelper::applyDateFilters($cq, $filter);
        $query = $cq->getModel();
        $count = (int) $query->count();

        return [
            'count'             => $count,
            'execution_time_ms' => (int) round((microtime(true) - $start) * 1000),
        ];
    }
}
