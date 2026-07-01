<?php

namespace FluentCampaign\App\Modules\MCP;

use FluentCampaign\App\Modules\MCP\Tools\DynamicSegmentTools;
use FluentCampaign\App\Modules\MCP\Tools\SequenceTools;
use FluentCrm\App\Services\PermissionManager;

/**
 * Pro ability definitions. Registered after `fluent_crm/mcp_loaded` so they
 * land in the same `fluent-crm/` namespace as core tools.
 */
class ProAbilitiesRegistrar
{
    public static function getDefinitions()
    {
        return [
            'fluent-crm/list-sequences' => [
                'label'       => __('List Sequences', 'fluentcampaign-pro'),
                'description' => __('List email sequences (drip campaigns) with stats inline (emails, subscribers, revenue).', 'fluentcampaign-pro'),
                'input_schema' => [
                    'type'       => 'object',
                    'properties' => [
                        'search'        => ['type' => 'string', 'description' => 'Matches sequence title.'],
                        'sort_by'       => ['type' => 'string', 'enum' => ['id', 'title', 'created_at', 'updated_at'], 'default' => 'id'],
                        'sort_type'     => ['type' => 'string', 'enum' => ['ASC', 'DESC'], 'default' => 'DESC'],
                        'include_stats' => ['type' => 'boolean', 'default' => true],
                        'page'          => ['type' => 'integer', 'default' => 1],
                        'per_page'      => ['type' => 'integer', 'default' => 15, 'description' => 'Max 100.'],
                    ],
                ],
                'execute_callback'    => [SequenceTools::class, 'listSequences'],
                'permission_callback' => function () {
                    return PermissionManager::currentUserCan('fcrm_read_emails');
                },
                'annotations' => ['readonly' => true],
            ],

            'fluent-crm/get-sequence' => [
                'label'       => __('Get Sequence', 'fluentcampaign-pro'),
                'description' => __('Sequence details with emails inline. Bodies are opt-in (set include_bodies=true) — large sequences burn agent context fast.', 'fluentcampaign-pro'),
                'input_schema' => [
                    'type'       => 'object',
                    'properties' => [
                        'sequence_id'           => ['type' => 'integer'],
                        'include'               => [
                            'type'  => 'array',
                            'items' => ['type' => 'string', 'enum' => ['bodies']],
                            'description' => 'Pass ["bodies"] to include full email body_html/body_text (alias for include_bodies).',
                        ],
                        'include_bodies'        => ['type' => 'boolean', 'default' => false, 'description' => 'Include each email\'s body_html + body_text. Off by default to save tokens.'],
                        'include_email_stats'   => ['type' => 'boolean', 'default' => true],
                    ],
                    'required' => ['sequence_id'],
                ],
                'execute_callback'    => [SequenceTools::class, 'getSequence'],
                'permission_callback' => function () {
                    return PermissionManager::currentUserCan('fcrm_read_emails');
                },
                'annotations' => ['readonly' => true],
            ],

            'fluent-crm/manage-sequence-subscribers' => [
                'label'       => __('Manage Sequence Subscribers', 'fluentcampaign-pro'),
                'description' => __('Subscribe or unsubscribe contacts to/from a sequence. Provide contact_ids OR filter, not both. subscribe is idempotent — already-enrolled contacts are reported as already_enrolled. Pass dry_run=true to preview matched/would-enroll counts without writing. Cap 5000.', 'fluentcampaign-pro'),
                'input_schema' => [
                    'type'       => 'object',
                    'properties' => [
                        'sequence_id'         => ['type' => 'integer'],
                        'action'              => ['type' => 'string', 'enum' => ['subscribe', 'unsubscribe']],
                        'contact_ids'         => ['type' => 'array', 'items' => ['type' => 'integer'], 'description' => 'Explicit ids. Use OR filter, not both.'],
                        'filter'              => ['type' => 'object', 'description' => 'Universal filter — see get-crm-context.guidelines.'],
                        'exclude_contact_ids' => ['type' => 'array', 'items' => ['type' => 'integer'], 'description' => 'subscribe only — ids to skip from the resolved set.'],
                        'dry_run'             => ['type' => 'boolean', 'default' => false, 'description' => 'Preview only. Returns matched_contacts, would_enroll / would_unsubscribe, already_enrolled — never writes.'],
                    ],
                    'required' => ['sequence_id', 'action'],
                ],
                'execute_callback'    => [SequenceTools::class, 'manageSubscribers'],
                'permission_callback' => function () {
                    return PermissionManager::currentUserCan('fcrm_manage_emails');
                },
                'annotations' => ['bulk' => true],
            ],

            'fluent-crm/estimate-dynamic-segment' => [
                'label'       => __('Estimate Dynamic Segment', 'fluentcampaign-pro'),
                'description' => __('Count contacts matching a filter (no rows returned). For "who will receive a campaign" pass statuses=["subscribed"] — campaigns only send to subscribed. Or use upsert-campaign.estimated_recipients which scopes to subscribed automatically.', 'fluentcampaign-pro'),
                'input_schema' => [
                    'type'       => 'object',
                    'properties' => [
                        'filter' => [
                            'type'        => 'object',
                            'description' => 'Universal filter — {tags, lists, statuses, contact_type, search, created_after, created_before}. See get-crm-context.guidelines.',
                        ],
                    ],
                    'required' => ['filter'],
                ],
                'execute_callback'    => [DynamicSegmentTools::class, 'estimate'],
                'permission_callback' => function () {
                    return PermissionManager::currentUserCan('fcrm_read_contacts');
                },
                'annotations' => ['readonly' => true],
            ],
        ];
    }

    public static function register()
    {
        foreach (self::getDefinitions() as $name => $definition) {
            $args = [
                'label'               => $definition['label'],
                'description'         => $definition['description'],
                'category'            => 'fluent-crm',
                'execute_callback'    => $definition['execute_callback'],
                'permission_callback' => $definition['permission_callback'],
                'meta'                => [
                    'show_in_rest' => true,
                    'mcp'          => [
                        'public' => true,
                    ],
                ],
            ];

            if (!empty($definition['input_schema'])) {
                $args['input_schema'] = $definition['input_schema'];
            }

            if (!empty($definition['annotations'])) {
                $args['meta']['annotations'] = $definition['annotations'];
            }

            wp_register_ability($name, $args);
        }
    }
}
