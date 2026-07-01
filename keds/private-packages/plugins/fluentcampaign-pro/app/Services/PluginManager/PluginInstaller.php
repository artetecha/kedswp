<?php

namespace FluentCampaign\App\Services\PluginManager;

class PluginInstaller
{
    /**
     * Download and install a plugin zip from a direct URL.
     *
     * @param string $zipUrl Direct URL to a plugin zip package.
     * @return true|\WP_Error
     */
    public function installFromZipUrl($zipUrl)
    {
        $zipUrl = esc_url_raw((string) $zipUrl);

        if (!$zipUrl || !filter_var($zipUrl, FILTER_VALIDATE_URL)) {
            return new \WP_Error('invalid_plugin_zip_url', __('Invalid plugin zip URL.', 'fluentcampaign-pro'));
        }

        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
        require_once ABSPATH . 'wp-admin/includes/plugin.php';

        \WP_Filesystem();

        $skin = new \Automatic_Upgrader_Skin();
        $upgrader = new \WP_Upgrader($skin);

        ob_start();

        try {
            $download = $upgrader->download_package($zipUrl);
            if (is_wp_error($download)) {
                throw new \Exception($download->get_error_message());
            }

            $workingDir = $upgrader->unpack_package($download, true);
            if (is_wp_error($workingDir)) {
                throw new \Exception($workingDir->get_error_message());
            }

            $result = $upgrader->install_package([
                'source'                      => $workingDir,
                'destination'                 => WP_PLUGIN_DIR,
                'clear_destination'           => false,
                'abort_if_destination_exists' => false,
                'clear_working'               => true,
                'hook_extra'                  => [
                    'type'   => 'plugin',
                    'action' => 'install',
                ],
            ]);

            if (!$result || is_wp_error($result)) {
                $message = is_wp_error($result) ? $result->get_error_message() : __('Plugin package installation failed.', 'fluentcampaign-pro');
                throw new \Exception($message);
            }
        } catch (\Exception $e) {
            ob_end_clean();
            return new \WP_Error('plugin_zip_install_failed', $e->getMessage());
        }

        ob_end_clean();

        wp_clean_plugins_cache();

        return true;
    }
}
