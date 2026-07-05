<?php

namespace FluentCampaign\App\Services\PluginManager;

class PluginInstaller
{
    const CORE_BASENAME = 'fluent-crm/fluent-crm.php';
    const CORE_SLUG = 'fluent-crm';

    /**
     * Register the core update pusher (see maybePushCoreUpdate).
     *
     * @return void
     */
    public function registerCoreUpdater()
    {
        add_filter('site_transient_update_plugins', [$this, 'maybePushCoreUpdate'], 20);
    }

    /**
     * Push the required FluentCRM core update directly from wordpress.org during
     * the WP.org "cool-down" window.
     *
     * After a release, wp.org delays the update-check API (~24h) so the new
     * version is not offered on the Plugins screen, even though the build zip is
     * already published at downloads.wordpress.org. When the installed core is
     * older than FLUENTCAMPAIGN_CORE_MIN_VERSION we inject an update entry
     * pointing straight at that zip so users can update (and auto-update)
     * without waiting out the window.
     *
     * @param object $transient The update_plugins site transient.
     * @return object
     */
    public function maybePushCoreUpdate($transient)
    {
        if (!is_object($transient)) {
            return $transient;
        }

        $minVersion = FLUENTCAMPAIGN_CORE_MIN_VERSION;

        // Core must be installed and below the required minimum.
        if (!defined('FLUENTCRM_PLUGIN_VERSION') || version_compare(FLUENTCRM_PLUGIN_VERSION, $minVersion, '>=')) {
            return $transient;
        }

        $existing = null;
        if (isset($transient->response[self::CORE_BASENAME]) && is_object($transient->response[self::CORE_BASENAME])) {
            $existing = $transient->response[self::CORE_BASENAME];
        } elseif (isset($transient->no_update[self::CORE_BASENAME]) && is_object($transient->no_update[self::CORE_BASENAME])) {
            $existing = $transient->no_update[self::CORE_BASENAME];
        }

        // If WP already surfaced an update at or above the minimum, let it handle it.
        if ($existing && isset($existing->new_version) && version_compare($existing->new_version, $minVersion, '>=')) {
            return $transient;
        }

        $package = 'https://downloads.wordpress.org/plugin/' . self::CORE_SLUG . '.' . $minVersion . '.zip';

        // Reuse the entry WP already built (it carries id/slug/icons/banners/
        // tested/requires metadata the update UI renders); during the cool-down
        // core sits in no_update. Only fall back to a fresh object if WP hasn't
        // populated the transient yet.
        if ($existing) {
            $update = clone $existing;
        } else {
            $update = (object) [
                'id'     => 'w.org/plugins/' . self::CORE_SLUG,
                'slug'   => self::CORE_SLUG,
                'plugin' => self::CORE_BASENAME,
                'url'    => 'https://wordpress.org/plugins/' . self::CORE_SLUG . '/',
            ];
        }

        $update->new_version = $minVersion;
        $update->package = $package;

        $transient->response[self::CORE_BASENAME] = $update;
        unset($transient->no_update[self::CORE_BASENAME]);

        return $transient;
    }

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
