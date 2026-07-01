# Upsun Migration Readiness Report

Date: 2026-07-01

Project root scanned: `/Users/vinnierusso/Repositories/keds-pantheon`

Target: migrate the current Pantheon-hosted WordPress project to Upsun.

## Executive Summary

This project is not ready to deploy to Upsun as-is. The main blockers are:

1. WordPress bootstrapping is still Pantheon-specific. `wp-config.php` only loads real database/secrets configuration when `$_ENV['PANTHEON_ENVIRONMENT']` is present, and otherwise falls back to placeholder credentials.
2. Pantheon runtime artifacts are committed: `pantheon.yml`, `pantheon.upstream.yml`, `wp-config-pantheon.php`, the Pantheon mu-plugin, and a Pantheon-specific Cloudflare cache directory.
3. Several plugins and theme/admin flows assume writable code directories for updates, drop-ins, mu-plugin installation, language files, or add-on installers. These need to be disabled in production or shifted into the build/deploy process.
4. Runtime write paths need explicit Upsun writable mounts. The strongest candidates are `wp-content/uploads`, `wp-content/cache`, and specific upload subdirectories used by Fluent Forms, WooCommerce, Slider Revolution, Autoptimize, Thim/Eduma, and local font tooling.
5. Premium/commercial plugin licensing and update workflows need review before cutover. The repository contains Object Cache Pro, Eduma/Thim, Slider Revolution, FluentCRM Pro, Fluent Forms Pro, and many LearnPress premium add-ons.

Recommended migration posture: create a new Upsun application configuration, keep production code read-only, mount only required runtime data paths, move plugin/theme/core updates into Git/build workflow, and test with production-like `DISALLOW_FILE_MODS` enabled before DNS cutover.

## Scan Scope And Limits

Scanned:

- WordPress core tree and version file.
- Root config files: `wp-config*.php`, `pantheon.yml`, `pantheon.upstream.yml`, `.gitignore`, `.htaccess`.
- `wp-content/mu-plugins`, `wp-content/object-cache.php`, `wp-content/plugins`, `wp-content/themes`, and committed generated/cache directories.
- Plugin and theme headers for inventory and version detection.
- Source searches for Pantheon coupling, writable path assumptions, drop-in writes, plugin/theme installers, cache writes, uploads, logs, and filesystem APIs.

Not available in this repository:

- A database dump or live database connection, so active plugin/theme status could not be confirmed.
- Runtime option values such as `active_plugins`, `template`, `stylesheet`, plugin licenses, cron schedules, upload volume, or cache state.
- Existing Upsun project/config files. No `.upsun` or `.platform` configuration was present.

## Current Stack Inventory

WordPress:

- Version: `7.0` from `wp-includes/version.php`.
- Required PHP: `7.4`.
- Required MySQL: `5.5.5`.
- Pantheon override sets PHP `8.4` in `pantheon.yml`.
- Pantheon upstream default sets MariaDB `10.4` in `pantheon.upstream.yml`.

Repository shape:

- Full WordPress core is committed.
- 49 plugin directories are committed.
- 3 theme directories are committed.
- Approximate repository size: `1.2G`.
- Approximate `wp-content` size: `532M`.
- Approximate plugin size: `500M`.
- Approximate theme size: `23M`.

Themes detected:

| Theme | Version | Notes |
| --- | ---: | --- |
| Eduma | 5.9.0 | ThimPress commercial theme; likely active but DB confirmation needed. |
| Twenty Twenty-Five | 1.5 | Core bundled theme. |
| Twenty Twenty-Four | 1.5 | Core bundled theme. |

Plugins detected:

| Plugin | Version | Migration relevance |
| --- | ---: | --- |
| Akismet Anti-spam | 5.7 | Standard plugin. |
| Enhanced Responsive Images | 1.7.0 | Performance Lab module style plugin. |
| The SEO Framework | 5.1.4 | Standard plugin. |
| Autoptimize | 3.1.15.1 | Writes optimized assets/cache under `wp-content/cache/autoptimize` and critical CSS under uploads. |
| Contact Form 7 | 6.1.6 | Uses temporary upload directories. |
| Image Placeholders | 1.2.1 | Performance Lab module style plugin. |
| Elementor | 4.1.1 | Has admin flows that can copy a safe-mode mu-plugin into `wp-content/mu-plugins`. |
| Embed Optimizer | 1.0.0-beta5 | Performance Lab module style plugin. |
| FluentCRM | 3.1.0 | CRM/email automation; likely cron and DB heavy. |
| FluentCRM Pro | 3.1.0 | Premium/commercial; plugin installer/update paths present. |
| Fluent Forms | 6.2.4 | Uses form upload/temp paths. |
| Fluent Forms Pro | 6.2.4 | Premium/commercial; writes chained-select CSVs and upload protection files. |
| Google for WooCommerce | 3.7.0 | WooCommerce integration. |
| Site Kit by Google | 1.179.0 | External service integration. |
| WP Armour | 2.3.04 | Standard plugin. |
| OMGF | 6.3.5 | Writes local fonts to uploads and can install a Cloudflare mu-plugin. |
| Image Prioritizer | 1.0.0-beta3 | Performance Lab module style plugin. |
| LearnPress | 4.3.7 | LMS core; add-on manager can install to plugin directory. |
| LearnPress paid add-ons | multiple | Premium/commercial ecosystem; validate licenses and active add-ons. |
| MC4WP | 4.12.6 | Standard plugin. |
| Object Cache Pro | 1.25.5 | Proprietary Redis drop-in; config currently uses Pantheon-style env vars. |
| Optimization Detective | 1.0.0-beta5 | Performance Lab module style plugin. |
| Paid Memberships Pro | 3.7.4 | Membership/ecommerce adjacent; may use scheduled jobs. |
| Performance Lab | 4.1.0 | Performance modules. |
| Redirection | 5.7.5 | Standard plugin; check redirects against Pantheon domain assumptions in DB. |
| Slider Revolution | 7.0.9 | Premium/commercial; writes templates, optimized files, fonts, add-ons to uploads and plugin dir. |
| Speculative Loading | 1.6.0 | Performance Lab module style plugin. |
| Thim Core | 2.4.8 | Eduma support plugin; importers/installers and writable upload/code assumptions. |
| Thim Elementor Kit | 1.4.1 | Eduma/Elementor support plugin; filesystem use. |
| Thim Portfolio | 2.1 | Eduma ecosystem plugin. |
| Web Worker Offloading | 0.2.1 | Performance Lab module style plugin. |
| Modern Image Formats | 2.6.1 | Image generation/upload impact. |
| WooCommerce Stripe Gateway | 10.7.0 | Payment integration; requires webhook/secrets check. |
| WooCommerce | 10.8.1 | Logs, exports, transient files, uploads, cron/action scheduler. |
| WP Events Manager | 2.2.4 | ThimPress ecosystem plugin. |
| WP Mail SMTP | 4.8.0 | Mail provider/secrets check. |
| Native PHP Sessions | 1.4.5 | Pantheon-origin session plugin; review necessity on Upsun. |

## Pantheon-Specific Findings

### P1 - Boot configuration is Pantheon-only

Evidence:

- `wp-config.php` loads `wp-config-pantheon.php` only when `$_ENV['PANTHEON_ENVIRONMENT']` is present: `wp-config.php:14`.
- The non-Pantheon fallback contains placeholder DB credentials and salts: `wp-config.php:34-48`.
- `wp-config-pantheon.php` reads database and salts directly from Pantheon env vars: `wp-config-pantheon.php:15-49`.

Impact:

On Upsun, unless `PANTHEON_ENVIRONMENT` is deliberately spoofed, the site will use placeholder DB values and fail to boot. Spoofing Pantheon env vars would carry forward the wrong abstraction and should be avoided.

Recommendation:

Replace the Pantheon bootstrap with an Upsun-aware config layer that reads Upsun relationships/environment variables for database, Redis, secrets, `WP_HOME`, `WP_SITEURL`, and environment type. Keep local config support, but make the production path platform-neutral or explicitly Upsun-specific.

### P1 - Pantheon mu-plugin is loaded as a must-use plugin

Evidence:

- `wp-content/mu-plugins/loader.php` defines and requires `pantheon-mu-plugin/pantheon.php`: `wp-content/mu-plugins/loader.php:14-20`.
- The Pantheon mu-plugin itself conditionally loads most functionality only when `$_ENV['PANTHEON_ENVIRONMENT']` is set, but the loader remains a Pantheon artifact.

Impact:

Most Pantheon functionality will be inert on Upsun if `PANTHEON_ENVIRONMENT` is absent. However, it is dead platform code in production and can mislead admins, site health, compatibility checks, or future maintenance.

Recommendation:

Remove the Pantheon mu-plugin and loader from the Upsun branch. Preserve the custom KEDS mu-plugin separately:

- Keep: `wp-content/mu-plugins/keds-disable-learnpress-guest-session.php`.
- Remove or replace: `wp-content/mu-plugins/loader.php` and `wp-content/mu-plugins/pantheon-mu-plugin/`.

### P1 - Pantheon platform config has no Upsun equivalent yet

Evidence:

- `pantheon.yml` sets Pantheon object cache version and PHP `8.4`: `pantheon.yml:5-8`.
- `pantheon.upstream.yml` sets Pantheon upstream defaults including MariaDB `10.4`, HTTPS behavior, and protected paths: `pantheon.upstream.yml:5-25`.
- No `.upsun` or `.platform` configuration files were found.

Impact:

Upsun will need explicit application, services, routes, mounts, PHP runtime, cron, and environment variable configuration. Nothing in the current repo defines that yet.

Recommendation:

Create Upsun configuration in a migration branch. Include:

- PHP runtime matching the desired version, currently likely `8.4` because Pantheon uses it.
- Database service compatible with the live database export.
- Redis service if retaining Object Cache Pro.
- Writable mounts for uploads/cache/runtime data.
- A scheduled job for WordPress cron if `DISABLE_WP_CRON` remains true.
- Routes and redirects that replace Pantheon's `enforce_https` behavior and protected web paths.

### P2 - Pantheon cron behavior must be replaced

Evidence:

- `wp-config-pantheon.php` disables WP cron on normal page loads for non-network WordPress: `wp-config-pantheon.php:110-114`.

Impact:

Removing the Pantheon config may silently re-enable page-load cron, or keeping the constant without a replacement scheduled job may stop scheduled work. This matters for WooCommerce Action Scheduler, FluentCRM automations, LearnPress, memberships, and email workflows.

Recommendation:

Define the desired cron strategy in the Upsun app config. Prefer `DISABLE_WP_CRON=true` plus a scheduled `wp cron event run --due-now` job.

## Read-Only Filesystem And Writable Mount Findings

Upsun writable directories need to be declared as mounts. Treat the application codebase as read-only in production and only mount runtime data paths that must persist across deploys.

Recommended first-pass mounts:

| Path | Reason | Risk if not writable |
| --- | --- | --- |
| `wp-content/uploads` | WordPress media, imports, form uploads, WooCommerce files, generated images. | Media upload failures; broken imports; plugin runtime errors. |
| `wp-content/cache` | Autoptimize writes optimized CSS/JS cache under `wp-content/cache/autoptimize`. | Performance plugin failure or repeated regeneration errors. |
| `wp-content/uploads/fluentform` | Fluent Forms Pro defines `FLUENTFORM_UPLOAD_DIR` as `/fluentform`. | Form file uploads/temp files/chained-select CSVs fail. |
| `wp-content/uploads/ao_ccss` | Autoptimize critical CSS files, logs, locks, settings exports. | Critical CSS generation and queue processing fail. |
| `wp-content/uploads/omgf` | OMGF local font files. | Locally hosted font generation fails. |
| `wp-content/uploads/wc-logs` | WooCommerce file logger default path. | File logging fails unless switched to DB/remote logging. |
| `wp-content/uploads/woocommerce_uploads` | Protected WooCommerce downloadable/private uploads. | Digital product/download workflows fail if used. |
| `wp-content/uploads/woocommerce_transient_files` | WooCommerce transient/export/import files. | WooCommerce transient file flows fail. |
| `wp-content/uploads/revslider` | Slider Revolution templates, optimized assets, fonts, objects, add-ons. | Slider imports/optimization/template assets fail. |
| `wp-content/uploads/thim-*` or `wp-content/uploads/thim-data-demos` | Thim demo/import/cache workflows. | Demo/import tools fail; should probably be admin-only, not production traffic. |

Do not mount these by default:

| Path | Reason |
| --- | --- |
| `wp-content/plugins` | Code should come from Git/build, not production admin writes. |
| `wp-content/themes` | Same as plugins. |
| `wp-content/mu-plugins` | Must-use code should be deployed, not generated in production. |
| `wp-content/languages` | Prefer deploy-time language packs or a deliberate language-pack mount only if admin translation updates are required. |
| WordPress root | Keep immutable; avoid admin/core updates in production. |

### P1 - Autoptimize expects writable cache paths

Evidence:

- Cache directory base is `WP_CONTENT_DIR . AUTOPTIMIZE_CACHE_CHILD_DIR`: `wp-content/plugins/autoptimize/classes/autoptimizeCache.php:369-372`.
- `AUTOPTIMIZE_CACHE_CHILD_DIR` defaults to `/cache/autoptimize/`: `wp-content/plugins/autoptimize/classes/autoptimizeMain.php:101-109`.
- It writes `.htaccess`, fallback files, and index files: `wp-content/plugins/autoptimize/classes/autoptimizeCache.php:626`, `wp-content/plugins/autoptimize/classes/autoptimizeCache.php:647-658`, `wp-content/plugins/autoptimize/classes/autoptimizeCache.php:747-763`.
- Critical CSS defaults to `wp-content/uploads/ao_ccss/`: `wp-content/plugins/autoptimize/classes/autoptimizeCriticalCSSBase.php:57-63`.

Recommendation:

Either mount `wp-content/cache` and `wp-content/uploads/ao_ccss`, or disable Autoptimize CSS/JS/critical-CSS generation on Upsun and replace with build-time optimization/CDN strategy.

### P1 - Fluent Forms Pro writes to uploads

Evidence:

- `FLUENTFORM_UPLOAD_DIR` is defined as `/fluentform`: `wp-content/plugins/fluentformpro/fluentformpro.php:19`.
- Submissions delete and manage files under `wp_upload_dir()['basedir'] . FLUENTFORM_UPLOAD_DIR`: `wp-content/plugins/fluentform/app/Services/Submission/SubmissionService.php:431-445`.
- Chained Select writes CSV files and protection files under the same directory: `wp-content/plugins/fluentformpro/src/Components/ChainedSelect/ChainedSelectDataSourceManager.php:83-95`, `wp-content/plugins/fluentformpro/src/Components/ChainedSelect/ChainedSelectDataSourceManager.php:199-216`.

Recommendation:

Mount `wp-content/uploads`. Verify Fluent Forms file upload settings and test upload/delete/temp cleanup flows after migration.

### P1 - WooCommerce writes logs and transient/download files under uploads

Evidence:

- WooCommerce defaults log directory to `wp_upload_dir()['basedir'] . '/wc-logs/'`: `wp-content/plugins/woocommerce/src/Internal/Admin/Logging/Settings.php:58-71`.
- WooCommerce robots exclusions reference `wp-content/uploads/wc-logs`, `wp-content/uploads/woocommerce_transient_files`, and `wp-content/uploads/woocommerce_uploads`: `wp-content/plugins/woocommerce/includes/class-woocommerce.php:1225-1227`.
- The legacy file logger opens and writes log files: `wp-content/plugins/woocommerce/includes/log-handlers/class-wc-log-handler-file.php:145-163`, `wp-content/plugins/woocommerce/includes/log-handlers/class-wc-log-handler-file.php:212-214`.

Recommendation:

Mount `wp-content/uploads`. Decide whether WooCommerce logs should stay file-based, move to database logging, or go to Upsun/platform logs. Test payment webhooks, refunds, scheduled actions, product exports, and any downloadable products.

### P1 - Slider Revolution writes to uploads and can install add-ons

Evidence:

- Exports/imports use upload paths and zip files: `wp-content/plugins/revslider/admin/includes/export.class.php:30-36`.
- Templates require uploads write access and create files under uploads: `wp-content/plugins/revslider/admin/includes/template.class.php:27-63`.
- Google font downloads write under `uploads/themepunch/gfonts`: `wp-content/plugins/revslider/includes/fonts.class.php:408-440`.
- Add-ons write template zips under `uploads/revslider/templates` and reference plugin installation paths: `wp-content/plugins/revslider/admin/includes/addons.class.php:238-277`.

Recommendation:

Mount `wp-content/uploads`. Disable or operationally restrict add-on installation/update flows in production; install/update add-ons through Git/build.

### P2 - OMGF writes local fonts and may try to create a mu-plugin

Evidence:

- `OMGF_UPLOAD_DIR` defaults to `WP_CONTENT_DIR . '/uploads/omgf'`: `wp-content/plugins/host-webfonts-local/src/Plugin.php:47`.
- Cloudflare compatibility can copy a generated mu-plugin into `WPMU_PLUGIN_DIR`: `wp-content/plugins/host-webfonts-local/src/Compatibility/Cloudflare.php:43-61`.
- It can unlink the generated mu-plugin on uninstall: `wp-content/plugins/host-webfonts-local/src/Compatibility/Cloudflare.php:75-83`.

Recommendation:

Mount uploads for generated fonts. Disable Cloudflare mu-plugin generation in production or deploy that mu-plugin explicitly if needed.

### P2 - Eduma/Thim tooling assumes writable uploads and sometimes root/code writes

Evidence:

- Thim Core customizer writes stylesheets under uploads: `wp-content/plugins/thim-core/inc/class-thim-core-customizer.php:34-41`.
- Thim importer uses `wp-content/uploads/thim-data-demos`: `wp-content/plugins/thim-core/admin/class-thim-importer.php:123-124`, `wp-content/plugins/thim-core/admin/class-thim-importer.php:256`.
- Thim importer contains a write to `ABSPATH . '/' . $file`: `wp-content/plugins/thim-core/admin/services/class-thim-wp-import-service.php:1556`.
- Eduma language update flow targets `wp-content/languages/themes`: `wp-content/themes/eduma/inc/custom-functions.php:2118-2134`.
- Eduma/Thim installer checks plugin directory writability: `wp-content/themes/eduma/inc/admin/thim-core-installer/installer.php:261-263`.

Recommendation:

Mount uploads. Keep theme/plugin/language updates out of production admin UI unless a deliberate writable language mount is accepted. Do not rely on demo importers in production.

## Premium And Commercial Plugin/Theme Risk

High-priority license/update review:

| Package | Evidence | Risk |
| --- | --- | --- |
| Object Cache Pro | Proprietary license in plugin header; drop-in committed. | Requires valid license token and Redis config; drop-in management writes `wp-content/object-cache.php`. |
| Eduma | Split License, ThimPress commercial theme. | Theme update and bundled plugin installer workflows expect writable code dirs. |
| Thim Core / Thim Elementor Kit / Thim Portfolio | ThimPress ecosystem. | Plugin install/update/demo import flows need admin/code write discipline. |
| Slider Revolution | ThemePunch premium plugin. | Add-ons/templates/fonts/cache write behavior; license/domain activation may change on Upsun domain. |
| FluentCRM Pro | Premium plugin. | License/domain activation and plugin updater/installer behavior. |
| Fluent Forms Pro | Premium plugin. | License/domain activation; uploads must be writable. |
| LearnPress add-ons | Multiple ThimPress LearnPress add-ons. | License/domain activation; version compatibility with LearnPress 4.3.7 and PHP 8.4 should be tested. |

Production policy recommendation:

- Set `DISALLOW_FILE_MODS` true in production.
- Manage plugin/theme/core updates in Git/build/deploy, not via wp-admin.
- Disable or hide plugin installers/add-on installers where practical.
- Validate premium license domains after moving from Pantheon dev/test/live hostnames to Upsun/custom domains.

## Object Cache / Redis Findings

Evidence:

- `wp-content/object-cache.php` is Object Cache Pro drop-in version `1.25.5`.
- `wp-config.php` includes `wp-config-ocp.php`: `wp-config.php:75`.
- `wp-config-ocp.php` defines `WP_REDIS_CONFIG` with env vars `OCP_LICENSE`, `CACHE_HOST`, `CACHE_PORT`, `CACHE_DB`, and `CACHE_PASSWORD`: `wp-config-ocp.php:13-18`.
- The Redis prefix is `ocppantheon`: `wp-config-ocp.php:11`, `wp-config-ocp.php:32`.
- Serializer/compression are set to `igbinary` and `zstd`: `wp-config-ocp.php:33-34`.

Impact:

Upsun Redis service connection values will not automatically match the current Pantheon-style env names unless explicitly mapped. PHP extensions for `igbinary` and `zstd` must be present, or Object Cache Pro config must be adjusted.

Recommendation:

In the Upsun config pass:

1. Add a Redis service if Object Cache Pro is retained.
2. Map Upsun relationship credentials into `WP_REDIS_CONFIG`.
3. Rename the cache prefix away from `ocppantheon`.
4. Confirm `igbinary` and `zstd` extension availability in the target PHP runtime or use supported serializer/compression settings.
5. Keep `wp-content/object-cache.php` deployed from Git; do not let production regenerate it.

## Native PHP Sessions Finding

Evidence:

- `wp-content/plugins/wp-native-php-sessions/pantheon-sessions.php` is installed as "Native PHP Sessions" version `1.4.5`.
- It creates and uses `pantheon_sessions` database tables and exposes WP-CLI commands under `wp pantheon session`.

Impact:

This is a Pantheon-origin plugin designed to move PHP session storage into the database. It may still work off Pantheon, but the naming, CLI, and operational assumptions should be reviewed. It also increases database write load.

Recommendation:

Confirm why it is installed and which active plugin needs PHP sessions. If it is only present because Pantheon recommended it, test without it on Upsun. If it is required, keep it and verify the session table exists after DB migration.

## Generated / Environment-Specific Artifacts

Observed:

- `wp-content/wp-cloudflare-super-page-cache/dev-artetecha.pantheon.io/`.
- Contains only tiny placeholder/index files and a zero-byte `nginx.conf`.

Impact:

This appears to be generated cache/plugin state keyed to a Pantheon dev hostname. It should not be treated as deployable production state for Upsun.

Recommendation:

Remove generated Pantheon-domain cache directories from the Upsun branch and regenerate platform/CDN cache config on the target environment if still needed.

## Security And Routing Notes

Pantheon upstream protected paths:

- `/private/`
- `/wp-content/uploads/private/`
- `/xmlrpc.php`

Recommendation:

Recreate these protections in Upsun route/web server configuration or application-level controls. Confirm whether XML-RPC is intentionally blocked, because WordPress mobile apps, Jetpack-like integrations, or legacy services may depend on it.

Additional sensitive paths to verify after migration:

- WooCommerce protected downloads.
- Fluent Forms uploaded files.
- LearnPress assignment/certificate uploads.
- Private membership/course content.
- Any `/wp-content/uploads/private/` content in the live environment.

## Proposed Migration Work Plan

### Phase 1 - Prepare an Upsun branch

1. Add Upsun app/services/routes configuration.
2. Replace Pantheon config bootstrap with Upsun-aware `wp-config.php` logic.
3. Remove Pantheon mu-plugin artifacts from the branch.
4. Keep the custom KEDS LearnPress session mu-plugin.
5. Configure Redis/Object Cache Pro for Upsun or disable it for first boot.
6. Define production constants:
   - `WP_ENVIRONMENT_TYPE=production`
   - `DISALLOW_FILE_MODS=true`
   - `DISABLE_WP_CRON=true` if scheduled cron is configured
   - `WP_DEBUG=false`
7. Add writable mounts for uploads and cache.

### Phase 2 - Data migration rehearsal

1. Export Pantheon DB and files.
2. Import DB into Upsun.
3. Sync uploads into the `wp-content/uploads` mount.
4. Run search-replace for domains:
   - Pantheon dev/test/live domains.
   - Final custom production domain.
   - Any serialized URLs in Elementor, Eduma, Slider Revolution, LearnPress, WooCommerce, and FluentCRM data.
5. Regenerate caches/permalinks.
6. Run WP-CLI checks:
   - `wp core version`
   - `wp plugin list`
   - `wp theme list`
   - `wp cron event list`
   - `wp option get active_plugins`
   - Object cache health command if Object Cache Pro remains active.

### Phase 3 - Production-like validation

Test with the code filesystem read-only and only declared mounts writable:

- Login and admin dashboard.
- Course browsing, enrollment, checkout, certificates, assignments.
- WooCommerce checkout, Stripe webhook, refunds, emails.
- Fluent Forms uploads and submissions.
- FluentCRM automations and scheduled email.
- Elementor frontend rendering and CSS generation.
- Eduma/Thim customizer-generated CSS.
- Slider Revolution frontend, imports disabled/controlled.
- Autoptimize cache generation or disabled replacement.
- Media uploads and image generation/WebP behavior.
- WP Mail SMTP delivery.
- Cron/action scheduler processing.
- Cache purge behavior and CDN behavior.

### Phase 4 - Cutover readiness

1. Freeze admin writes on Pantheon.
2. Final DB/files sync.
3. Run final search-replace.
4. Warm caches.
5. Validate payment/email/webhook endpoints.
6. Switch DNS.
7. Monitor PHP logs, web logs, cron, WooCommerce logs, and action scheduler queue.

## Open Questions

1. Which plugins are active in the live Pantheon database?
2. Is Eduma the active theme?
3. Are WooCommerce downloadable products used?
4. Are LearnPress assignments/certificates storing generated files in uploads?
5. Is Native PHP Sessions still required?
6. Is Object Cache Pro licensed independently, or bundled through Pantheon?
7. Which CDN/page-cache plugin is actually active in production?
8. Are admins currently updating plugins/themes in Pantheon dev via SFTP/wp-admin?
9. What final domain(s) will be used on Upsun?
10. Are any private files stored outside `wp-content/uploads` on Pantheon?

## References Consulted

- Upsun app configuration reference: `https://docs.upsun.com/create-apps/app-reference.html`
- Upsun mounts documentation: `https://developer.upsun.com/docs/troubleshoot/mounts`
- Upsun PHP documentation: `https://developer.upsun.com/docs/languages/php`
- Upsun Redis documentation: `https://developer.upsun.com/docs/add-services/redis`
- Pantheon WordPress known issues: `https://docs.pantheon.io/wordpress-known-issues`
