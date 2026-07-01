<?php

namespace FluentCampaign\App\Http\Controllers;

use FluentCrm\Framework\Http\Request\Request;

class ProReportController extends Controller
{
    public function getTopCampaigns(Request $request)
    {
        $sortBy = sanitize_text_field($request->get('sort_by', 'open_rate'));
        $limit = intval($request->get('per_page', 10));

        $allowedSorts = ['open_rate', 'click_rate', 'total_sent'];
        $sortBy = in_array($sortBy, $allowedSorts) ? $sortBy : 'open_rate';

        $campaigns = fluentCrmDb()->table('fc_campaigns as c')
            ->select([
                'c.id',
                'c.title',
                'c.updated_at',
            ])
            ->selectRaw('COUNT(ce.id) as total_sent')
            ->selectRaw('SUM(CASE WHEN ce.is_open = 1 THEN 1 ELSE 0 END) / COUNT(ce.id) * 100 as open_rate')
            ->selectRaw('SUM(CASE WHEN ce.click_counter > 0 THEN 1 ELSE 0 END) / COUNT(ce.id) * 100 as click_rate')
            ->join('fc_campaign_emails as ce', 'c.id', '=', 'ce.campaign_id')
            ->where('c.status', 'archived')
            ->where('c.type', 'campaign')
            ->where('ce.status', 'sent')
            ->groupBy('c.id', 'c.title', 'c.updated_at')
            ->orderBy($sortBy, 'DESC')
            ->limit($limit)
            ->get();

        return $this->sendSuccess([
            'campaigns' => $campaigns,
            'available' => true,
        ]);
    }
}
