<?php

namespace FluentCampaign\App\Hooks\Handlers;

use FluentCrm\App\Hooks\Handlers\AdminMenu;
use FluentCrm\App\Services\Helper;
use FluentCrm\App\Services\PermissionManager;
use FluentCrm\Framework\Support\Arr;

class FrontendPortalHandler
{
    protected static $stylesEnqueued = false;

    protected static $shortcodeRendered = false;

    protected $standAloneSlug = '';

    public function register()
    {
        $settings = Helper::getExperimentalSettings();
        if (Arr::get($settings, 'frontend_portal') !== 'yes') {
            return; // frontend portal is not enabled
        }

        $type = Arr::get($settings, 'frontend_portal_render_type', 'standalone');

        if ($type === 'standalone') {
            $this->standAloneSlug = Arr::get($settings, 'frontend_portal_slug');
            if ($this->standAloneSlug) {
                add_filter('fluent_crm/menu_url_base', function ($url) {
                    if (is_admin()) {
                        return $url;
                    }
                    return trailingslashit(home_url($this->standAloneSlug)) . '#/';
                });
                add_action('template_redirect', [$this, 'maybeRenderStandalonePortal'], 1);
            }
        } else {
            add_shortcode('fluent_crm', [$this, 'renderFrontendShortcode']);
        }

        add_action('admin_bar_menu', [$this, 'addFrontendPortalLink'], 81);
    }

    public function addFrontendPortalLink($adminBar)
    {
        $portalUrl = '';
        if ($this->standAloneSlug) {
            $portalUrl = trailingslashit(home_url($this->standAloneSlug)) . '#/';
        } else {
            $settings = Helper::getExperimentalSettings();
            $pageId = Arr::get($settings, 'frontend_portal_page_id');
            if ($pageId) {
                $baseUrl = get_permalink($pageId);
                if($baseUrl) {
                    $portalUrl = trim($baseUrl, '/') . '/#/';
                }
            }
        }

        if (!$portalUrl) {
            return;
        }

        $adminBar->add_node([
            'parent' => 'site-name',
            'id'     => 'fcrm_frontend_portal',
            'title'  => __('Visit CRM Front Portal', 'fluentcampaign-pro'),
            'href'   => $portalUrl,
            'meta'   => [
                'class' => 'fcrm-adminbar-frontend-portal'
            ]
        ]);
    }

    public function maybeRenderStandalonePortal()
    {
        global $wp;
        $requestPath = '';
        if (!empty($wp->request)) {
            $requestPath = trim($wp->request, '/');
        }

        if ($requestPath !== $this->standAloneSlug) {
            return;
        }

        add_filter('show_admin_bar', '__return_false');
        status_header(200);
        nocache_headers();

        global $wp_query;
        if ($wp_query) {
            $wp_query->is_404 = false;
        }

        $this->enqueueFrontendPortalStyles();
        $permissionContent = $this->getPermissionContent();

        if ($permissionContent) {
            $content = $permissionContent;
        } else {
            $adminMenu = new AdminMenu();
            $adminMenu->loadCssJs();
            ob_start();
            $adminMenu->render();
            $content = ob_get_clean();
        }

        add_action('fluent_crm/headless/content', function () use ($content) {
            echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        });

        $title = __('FluentCRM Portal', 'fluentcampaign-pro');
        $scope = 'crm_portal';
        $load_wp = true;

        require_once FLUENTCAMPAIGN_PLUGIN_PATH . 'app/Views/headless_page.php';
        exit;
    }

    public function renderFrontendShortcode()
    {
        if (self::$shortcodeRendered) {
            return '';
        }

        self::$shortcodeRendered = true;


        $baseUrl = get_permalink();

        if (!$baseUrl && get_queried_object_id()) {
            $baseUrl = get_permalink(get_queried_object_id());
        }

        if(!$baseUrl) {
            $baseUrl = fluentcrm_menu_url_base();
        } else {
            $baseUrl = trim($baseUrl, '/') . '/#/';
        }


        $permissionContent = $this->getPermissionContent($baseUrl);
        if ($permissionContent) {
            return $permissionContent;
        }

        add_filter('fluent_crm/menu_url_base', function ($url) use ($baseUrl) {
            if ($baseUrl) {
                return $baseUrl;
            }
            return $url;
        });

        add_filter('fluent_crm/skip_no_conflict', '__return_true');

        $adminMenu = new AdminMenu();
        $adminMenu->loadCssJs();
        $this->enqueueFrontendPortalStyles();

        ob_start();
        $adminMenu->render();
        $content = ob_get_clean();

        return sprintf(
            '<div class="fcrm_frontend_portal_scope">%s</div>',
            $content
        );
    }

    protected function enqueueFrontendPortalStyles()
    {
        if (self::$stylesEnqueued) {
            return;
        }

        self::$stylesEnqueued = true;

        wp_enqueue_style(
            'fluentcrm_frontend_portal_styles',
            FLUENTCAMPAIGN_PLUGIN_URL . 'assets/css/frontend-portal.css',
            [],
            FLUENTCAMPAIGN_PLUGIN_VERSION
        );
    }

    protected function getPermissionContent($baseUrl = '')
    {
        if (!$baseUrl) {
            $baseUrl = fluentcrm_menu_url_base();
        }

        $userId = get_current_user_id();

        if (!$userId) {
            $loginUrl = wp_login_url($baseUrl);
            $loginLink = '<a href="' . $loginUrl . '">' . __('Log in', 'fluentcampaign-pro') . '</a>';

            return sprintf(
                '<div class="fcrm_access_denied"><p>%1$s. %2$s</p></div>',
                __('You must be logged in to view portal', 'fluentcampaign-pro'),
                $loginLink
            );
        }

        if (!PermissionManager::getUserPermissions($userId)) {
            return sprintf(
                '<div class="fcrm_access_denied"><div class="fcrm_front_portal_state"><p>%s</p></div></div>',
                esc_html__('You do not have permission to view this portal', 'fluentcampaign-pro')
            );
        }

        return '';
    }


}
