<?php

namespace FluentFormPro\classes\SharePage;

use FluentForm\App\Models\Form;
use FluentForm\App\Models\FormMeta;

class FormPrettyUrlService
{
    protected static $rewriteTag = 'ff_form_slug';

    public static function getRewriteTag()
    {
        return static::$rewriteTag;
    }

    public static function getBaseSlug()
    {
        if (defined('FLUENTFORM_PRETTY_URL_SLUG') && FLUENTFORM_PRETTY_URL_SLUG) {
            $slug = sanitize_title(FLUENTFORM_PRETTY_URL_SLUG);
            return $slug ?: 'form';
        }

        $settings = get_option('_fluentform_global_form_settings', []);
        $slug = '';
        if (is_array($settings) && !empty($settings['misc']['pretty_url_base_slug'])) {
            $slug = sanitize_title($settings['misc']['pretty_url_base_slug']);
        }

        $slug = $slug ?: 'form';

        return apply_filters('fluentform/pretty_url_base_slug', $slug);
    }

    public static function registerRewriteRules()
    {
        $baseSlug = static::getBaseSlug();

        // Keep this above WordPress' broad page rules so /form/{slug}/ reaches Fluent Forms.
        add_rewrite_rule(
            '^' . preg_quote($baseSlug, '/') . '/([^/]+)/?$',
            'index.php?' . static::$rewriteTag . '=$matches[1]',
            'top'
        );
    }

    public static function registerQueryVars($vars)
    {
        $vars[] = static::$rewriteTag;
        return $vars;
    }

    public static function getFormBySlug($slug)
    {
        $slug = sanitize_title($slug);

        if (empty($slug)) {
            return null;
        }

        $meta = FormMeta::where('meta_key', '_form_slug')
            ->where('value', $slug)
            ->first();

        if (!$meta) {
            return null;
        }

        // Check if pretty URL is enabled for this form
        $enabledMeta = FormMeta::where('form_id', $meta->form_id)
            ->where('meta_key', '_pretty_url_enabled')
            ->first();

        if (!$enabledMeta || $enabledMeta->value !== 'yes') {
            return null;
        }

        $form = Form::where('id', $meta->form_id)
            ->where('status', 'published')
            ->first();

        return $form;
    }

    public static function generateSlug($title, $formId)
    {
        $slug = sanitize_title($title);

        if (empty($slug)) {
            $slug = 'form-' . $formId;
        }

        $slug = static::ensureUniqueSlug($slug, $formId);

        return $slug;
    }

    public static function ensureUniqueSlug($slug, $formId)
    {
        $candidate = $slug;
        $i = 0;

        while (true) {
            $existing = FormMeta::where('meta_key', '_form_slug')
                ->where('value', $candidate)
                ->where('form_id', '!=', $formId)
                ->first();

            if (!$existing) {
                return $candidate;
            }

            if ($i === 0) {
                $candidate = $slug . '-' . $formId;
            } else {
                $candidate = $slug . '-' . $formId . '-' . $i;
            }
            $i++;
        }
    }

    public static function getSlug($formId)
    {
        $meta = FormMeta::where('form_id', $formId)
            ->where('meta_key', '_form_slug')
            ->first();

        return $meta ? $meta->value : '';
    }

    public static function isEnabled($formId)
    {
        $meta = FormMeta::where('form_id', $formId)
            ->where('meta_key', '_pretty_url_enabled')
            ->first();

        return $meta && $meta->value === 'yes';
    }

    public static function saveSlug($formId, $slug, $enabled = true)
    {
        $slug = sanitize_title($slug);

        if (empty($slug)) {
            $form = Form::find($formId);
            $slug = static::generateSlug($form ? $form->title : '', $formId);
        }

        $lockName = static::getSlugLockName($slug);
        static::acquireSlugLock($lockName);

        try {
            $existing = FormMeta::where('meta_key', '_form_slug')
                ->where('value', $slug)
                ->where('form_id', '!=', $formId)
                ->first();

            if ($existing) {
                throw new \Exception(
                    esc_html__('This slug is already in use. Please choose a different one.', 'fluentformpro')
                );
            }

            FormMeta::persist($formId, '_form_slug', $slug);
            FormMeta::persist($formId, '_pretty_url_enabled', $enabled ? 'yes' : 'no');

            // Update the global flag so rewrite rules are only registered when needed
            static::updatePrettyUrlFlag();
        } finally {
            static::releaseSlugLock($lockName);
        }

        return $slug;
    }

    private static function getSlugLockName($slug)
    {
        return 'fluentform_pretty_slug_' . md5($slug);
    }

    private static function acquireSlugLock($lockName)
    {
        global $wpdb;

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- MySQL named lock serializes slug check/write race.
        $locked = $wpdb->get_var($wpdb->prepare('SELECT GET_LOCK(%s, 5)', $lockName));

        if ('1' !== (string) $locked) {
            throw new \Exception(
                esc_html__('Could not reserve this slug. Please try again.', 'fluentformpro')
            );
        }
    }

    private static function releaseSlugLock($lockName)
    {
        global $wpdb;

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Releases the named lock acquired for slug save serialization.
        $wpdb->get_var($wpdb->prepare('SELECT RELEASE_LOCK(%s)', $lockName));
    }

    public static function deleteSlug($formId)
    {
        FormMeta::where('form_id', $formId)
            ->where('meta_key', '_form_slug')
            ->delete();
        FormMeta::where('form_id', $formId)
            ->where('meta_key', '_pretty_url_enabled')
            ->delete();

        static::updatePrettyUrlFlag();
    }

    /**
     * Update the global flag indicating whether any form has pretty URLs enabled.
     * This avoids registering rewrite rules on every page load when no form uses them.
     */
    public static function updatePrettyUrlFlag()
    {
        $hasAny = FormMeta::where('meta_key', '_pretty_url_enabled')
            ->where('value', 'yes')
            ->exists();

        if ($hasAny) {
            update_option('_fluentform_has_pretty_urls', 'yes', true);
        } else {
            delete_option('_fluentform_has_pretty_urls');
        }
    }

    public static function getFormPrettyUrl($formId)
    {
        $slug = static::getSlug($formId);

        if (empty($slug) || !static::isEnabled($formId)) {
            return '';
        }

        return home_url(static::getBaseSlug() . '/' . $slug . '/');
    }
}
