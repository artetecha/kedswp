<?php

namespace FluentCampaign\App\Modules\MCP;

/**
 * Bootstrap for FluentCampaign Pro's MCP additions.
 *
 * Pro abilities live under the same `fluent-crm/` namespace as the core tools
 * — agents do not need to know which plugin owns which ability. We register
 * lazily, only after free FluentCRM has fired `fluent_crm/mcp_loaded` (which
 * itself is gated behind the lazy-register guard in the core plugin).
 */
class MCPInit
{
    public function init()
    {
        (new ToolkitInstaller())->init();

        add_action('fluent_crm/mcp_loaded', [$this, 'registerProAbilities']);
        add_filter('fluent_crm/mcp_ability_names', [$this, 'addAbilityNames']);
    }

    public function registerProAbilities()
    {
        if (!function_exists('wp_register_ability')) {
            return;
        }

        ProAbilitiesRegistrar::register();
    }

    public function addAbilityNames($names)
    {
        $names = is_array($names) ? $names : [];
        return array_merge($names, array_keys(ProAbilitiesRegistrar::getDefinitions()));
    }
}
