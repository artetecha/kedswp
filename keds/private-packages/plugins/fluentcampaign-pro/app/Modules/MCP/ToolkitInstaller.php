<?php

namespace FluentCampaign\App\Modules\MCP;

use FluentCampaign\App\Services\PluginManager\PluginInstaller;

class ToolkitInstaller
{
    const TOOLKIT_PLUGIN_FILE = 'fluent-toolkit/fluent-toolkit.php';
    const TOOLKIT_DOWNLOAD_URL = 'https://static.wpmanageninja.com/fluent-toolkit.zip';

    public function init()
    {
        add_filter('fluent_toolkit/can_auto_install', [$this, 'canAutoInstall']);
        add_action('fluent_toolkit/do_auto_install', [$this, 'autoInstall']);
    }

    public function canAutoInstall($canAutoInstall)
    {
        return current_user_can('install_plugins') ? true : $canAutoInstall;
    }

    public function autoInstall()
    {
        if (!current_user_can('install_plugins')) {
            return;
        }

        if (did_action('fluent_toolkit/toolkit_installing')) {
            return;
        }

        do_action('fluent_toolkit/toolkit_installing');

        if ($this->activateToolkit() && $this->isToolkitAdapterAvailable()) {
            return;
        }

        $downloadUrl = $this->getToolkitDownloadUrl();
        if (is_wp_error($downloadUrl)) {
            return;
        }

        $installed = (new PluginInstaller())->installFromZipUrl($downloadUrl);
        if (is_wp_error($installed)) {
            return;
        }

        $this->activateToolkit();
    }

    private function activateToolkit()
    {
        if (!function_exists('is_plugin_active')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }

        if (!file_exists(WP_PLUGIN_DIR . '/' . self::TOOLKIT_PLUGIN_FILE)) {
            return false;
        }

        if (is_plugin_active(self::TOOLKIT_PLUGIN_FILE)) {
            $this->bootToolkitAdapter();
            return true;
        }

        $result = activate_plugin(self::TOOLKIT_PLUGIN_FILE);

        if (is_wp_error($result)) {
            return false;
        }

        $this->bootToolkitAdapter();

        return true;
    }

    private function bootToolkitAdapter()
    {
        if (class_exists('\FluentToolkit\Mcp\AdapterBootstrap')) {
            \FluentToolkit\Mcp\AdapterBootstrap::boot();
        }
    }

    private function isToolkitAdapterAvailable()
    {
        if (!class_exists('\FluentToolkit\Mcp\AdapterBootstrap')) {
            return false;
        }

        if (method_exists('\FluentToolkit\Mcp\AdapterBootstrap', 'available')) {
            return (bool) \FluentToolkit\Mcp\AdapterBootstrap::available();
        }

        return class_exists('\WP\MCP\Core\McpAdapter') && function_exists('wp_register_ability');
    }

    private function getToolkitDownloadUrl()
    {
        return self::TOOLKIT_DOWNLOAD_URL;
    }
}
