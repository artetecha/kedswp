<?php

namespace FluentCampaign\App\Http\Controllers;

use FluentCampaign\App\Hooks\Handlers\DataExporter;
use FluentCrm\App\Services\ContactsQuery;
use FluentCrm\App\Services\Helper;
use FluentCrm\Framework\Http\Request\Request;

class ExportController extends Controller
{
    /** Hard upper bound on a single export run (matches user-facing limit). */
    const MAX_EXPORT_ROWS = 100000;

    /** Rows per paginated request. */
    const PER_PAGE = 500;

    public function getContactsPage(Request $request)
    {
        $columns = $request->get('columns');

        if (empty($columns) || !is_array($columns)) {
            return $this->sendError([
                'message' => __('Please select at least one column to export.', 'fluentcampaign-pro')
            ], 400);
        }

        $customFields = $request->get('custom_fields', []);
        if (!is_array($customFields)) {
            $customFields = [];
        }

        $commerceColumns = $request->get('commerce_columns', []);
        if (!is_array($commerceColumns)) {
            $commerceColumns = [];
        }

        $companyIds = $request->get('company_ids', []);
        if (!is_array($companyIds)) {
            $companyIds = [];
        }

        $perPage = self::PER_PAGE;
        $maxPage = (int) ceil(self::MAX_EXPORT_ROWS / $perPage);
        $page = max(1, intval($request->get('page', 1)));

        if ($page > $maxPage) {
            return $this->sendError([
                'message' => sprintf(
                    /* translators: %d: maximum allowed page number */
                    __('Export page out of range. Maximum allowed page is %d.', 'fluentcampaign-pro'),
                    $maxPage
                )
            ], 400);
        }

        // Build eager-load relationships based on requested columns
        $with = [];
        if (in_array('tags', $columns)) {
            $with[] = 'tags';
        }
        if (in_array('lists', $columns)) {
            $with[] = 'lists';
        }
        if (in_array('companies', $columns)) {
            $with[] = 'companies';
        }
        if (in_array('primary_company', $columns)) {
            $with[] = 'company';
        }

        $filterType = sanitize_text_field($request->get('filter_type', 'simple'));

        $queryArgs = [
            'search'        => trim(sanitize_text_field($request->get('search', ''))),
            'sort_by'       => sanitize_sql_orderby($request->get('sort_by', 'id')),
            'sort_type'     => sanitize_sql_orderby($request->get('sort_type', 'DESC')),
            'custom_fields' => $customFields,
            'company_ids'   => $companyIds,
            'has_commerce'  => $request->get('has_commerce'),
            'with'          => $with,
        ];

        if (!empty($commerceColumns)) {
            $queryArgs['has_commerce'] = true;
        }

        // Handle selected contacts export
        $contactIds = $request->get('contact_ids', []);
        if (!empty($contactIds) && is_array($contactIds)) {
            $contactIds = array_map('intval', $contactIds);
            $contactIds = array_filter($contactIds, function ($id) {
                return $id > 0;
            });
            if (!empty($contactIds)) {
                $queryArgs['contact_ids'] = $contactIds;
            }
        }

        // Filter type
        if ($filterType == 'advanced') {
            $queryArgs['filter_type'] = 'advanced';
            $queryArgs['filters_groups_raw'] = Helper::parseArrayOrJson($request->get('advanced_filters'));
        } else {
            $queryArgs['filter_type'] = 'simple';
            $queryArgs['tags'] = $request->get('tags', []);
            $queryArgs['statuses'] = $request->get('statuses', []);
            $queryArgs['lists'] = $request->get('lists', []);
        }

        // User-facing limit and offset
        $userLimit = 0;
        if ($limit = $request->get('limit')) {
            $limit = intval($limit);
            if ($limit > 0 && $limit <= self::MAX_EXPORT_ROWS) {
                $userLimit = $limit;
            }
        }

        $userOffset = 0;
        if ($offset = $request->get('offset')) {
            $offset = intval($offset);
            if ($offset >= 0) {
                $userOffset = $offset;
            }
        }

        // On page 1: get total count and headers
        $response = [
            'page' => $page,
        ];

        if ($page === 1) {
            $countArgs = $queryArgs;
            if ($userOffset) {
                $countArgs['offset'] = $userOffset;
            }
            if ($userLimit) {
                $countArgs['limit'] = $userLimit;
            }

            $total = $userLimit ?: (new ContactsQuery($countArgs))->getModel()->count();

            if ($total === 0) {
                return $this->sendError([
                    'message' => __('No contacts found to export.', 'fluentcampaign-pro')
                ], 404);
            }

            $headerData = DataExporter::buildExportHeaders($columns, $customFields, $commerceColumns);
            $response['total'] = $total;
            $response['headers'] = $headerData['labels'];
        }

        // Compute this page's offset and limit
        $pageOffset = (($page - 1) * $perPage) + $userOffset;
        $pageLimit = $perPage;

        if ($userLimit) {
            $consumed = ($page - 1) * $perPage;
            $remaining = $userLimit - $consumed;
            if ($remaining <= 0) {
                $response['rows'] = [];
                $response['has_more'] = false;
                return $response;
            }
            $pageLimit = min($perPage, $remaining);
        }

        $queryArgs['offset'] = $pageOffset;
        $queryArgs['limit'] = $pageLimit;

        $subscribers = (new ContactsQuery($queryArgs))->get();

        // Build header info for row flattening
        $headerData = $headerData ?? DataExporter::buildExportHeaders($columns, $customFields, $commerceColumns);
        $headerKeys = $headerData['header_keys'];
        $customHeaderKeys = $headerData['custom_headers'];

        $rows = [];
        foreach ($subscribers as $subscriber) {
            $rows[] = DataExporter::flattenSubscriberRow($subscriber, $headerKeys, $customHeaderKeys, $commerceColumns);
        }

        $response['rows'] = $rows;

        // Determine if there are more pages
        $fetchedSoFar = (($page - 1) * $perPage) + count($subscribers);
        if ($userLimit) {
            $response['has_more'] = $fetchedSoFar < $userLimit && count($subscribers) === $pageLimit;
        } else {
            $response['has_more'] = count($subscribers) === $perPage;
        }

        return $response;
    }
}
