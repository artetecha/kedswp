<?php

namespace FluentCampaign\App\Http\Controllers;

use FluentCampaign\App\Migration\SmartLinksMigrator;
use FluentCampaign\App\Models\SmartLink;
use FluentCampaign\App\Http\Controllers\Controller;
use FluentCampaign\App\Services\ProHelper;
use FluentCrm\Framework\Http\Request\Request;
use FluentCrm\Framework\Support\Arr;

class SmartLinksController extends Controller
{
    /**
     * Validate smart link payload while allowing FluentCRM smartcodes inside target_url.
     *
     * We validate against a temporary URL where smartcodes are replaced with a
     * benign token. The original target_url is preserved for runtime parsing.
     *
     * @param array $link
     * @return void
     */
    private function validateLinkPayload(array $link)
    {
        $url = Arr::get($link, 'target_url', '');

        // Smartlinks support placeholders like {{contact.email}} in query values.
        $validationUrl = preg_replace('/\{\{[^{}]+\}\}/', 'smartcode_token', (string)$url);

        // Keep scheme restriction inside validator rules so failures throw and stop execution.
        $this->validate([
            'title'      => Arr::get($link, 'title'),
            'target_url' => $validationUrl,
        ], [
            'title'      => 'required',
            'target_url' => [
                'required',
                'url',
                // Smart Links should only redirect to web URLs.
                'regex:/^https?:\\/\\//i'
            ]
        ]);
    }

    public function getLinks(Request $request)
    {
        if (!ProHelper::hasSmartLink()) {
            return $this->send([
                'status' => 'disabled'
            ]);
        }

        // fc_smart_links columns. Required because the framework rewrite made
        // orderBy() throw on names that don't match ^[a-zA-Z0-9_\.]+$.
        $allowedOrderBy = [
            'id', 'title', 'short', 'target_url', 'actions', 'notes',
            'contact_clicks', 'all_clicks', 'created_by', 'created_at', 'updated_at',
        ];
        $order = $request->get('order') ?: 'desc';
        $orderBy = sanitize_key((string) ($request->get('orderBy') ?: 'id'));
        if (!in_array($orderBy, $allowedOrderBy, true)) {
            $orderBy = 'id';
        }
        $search = $request->get('search');

        $actionLinks = SmartLink::orderBy($orderBy, ($order == 'ascending' ? 'asc' : 'desc'))
            ->when($search, function ($query) use ($search) {
                $query->where('title', 'LIKE', "%$search%");
                $query->orWhere('target_url', 'LIKE', "%$search%");
                $query->orWhere('notes', 'LIKE', "%$search%");
                return $query;
            })
            ->paginate();

        foreach ($actionLinks as $actionLink) {
            $actionLink->detach_actions = [
                'tags'  => isset($actionLink->actions['remove_tags']) ? $actionLink->actions['remove_tags'] : [],
                'lists' => isset($actionLink->actions['remove_lists']) ? $actionLink->actions['remove_lists'] : [],
            ];

            $actionLink->auto_login = isset($actionLink->actions['auto_login']) ? $actionLink->actions['auto_login'] : 'no';
        }

        return [
            'action_links' => $actionLinks
        ];
    }

    public function activate()
    {
        SmartLinksMigrator::migrate(true);

        return [
            'message' => __('SmartLinks module has been successfully activated', 'fluentcampaign-pro')
        ];
    }

    public function createLink(Request $request)
    {
        $link = $request->get('link');
        $this->validateLinkPayload((array)$link);

        $link['actions']['remove_tags'] = Arr::get($link, 'detach_actions.tags', []);
        $link['actions']['remove_lists'] = Arr::get($link, 'detach_actions.lists', []);
        $link['actions']['auto_login'] = Arr::get($link, 'auto_login', 'no');

        $createdLink = SmartLink::create($link);

        return [
            'link'    => $createdLink,
            'message' => __('SmartLink has be created', 'fluentcampaign-pro')
        ];

    }

    public function update(Request $request, $id)
    {
        $link = $request->get('link');
        $this->validateLinkPayload((array)$link);

        $link['actions']['remove_tags'] = Arr::get($link, 'detach_actions.tags', []);
        $link['actions']['remove_lists'] = Arr::get($link, 'detach_actions.lists', []);
        $link['actions']['auto_login'] = Arr::get($link, 'auto_login', 'no');

        $existing = SmartLink::findOrFail($id);

        $existing->fill($link)->save();

        return [
            'link'    => $existing,
            'message' => __('SmartLink has be updated', 'fluentcampaign-pro')
        ];
    }

    public function delete(Request $request, $id)
    {
        SmartLink::where('id', $id)->delete();

        return [
            'message' => __('Selected Smart Link has been deleted', 'fluentcampaign-pro')
        ];
    }

}
