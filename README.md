# KEDS on Upsun

Composer-managed WordPress build for the KEDS site, hosted on [Upsun](https://upsun.com). WordPress core, plugins, and themes are installed by Composer at build time; only configuration, custom code, and vendored premium packages live in Git. Pushes to `main` deploy to production.

## Repository layout

```
.upsun/config.yaml     Upsun app, services, and routes configuration
.github/               CI workflows and helper scripts
keds/                  Application root (Upsun source.root)
├── composer.json      Full site manifest: WP core + all plugins/themes
├── wp-config.php      Upsun-aware config (reads relationships via platformsh/config-reader)
├── mu-plugins/        Custom KEDS mu-plugins (copied into the build)
├── private-packages/  Vendored premium plugins/themes (Composer path repositories)
├── scripts/           Deploy hook + premium update tooling
├── deploy-migrations/ One-time runtime migrations (see its README)
└── wordpress/         Build output — gitignored, never edit by hand
tests/e2e/             Playwright smoke tests run against Upsun preview environments
```

## How the build works

`.upsun/config.yaml` runs Composer with the `composer` build flavor from `keds/`:

- **Public packages** come from [wpackagist](https://wpackagist.org) (pinned versions).
- **Premium packages** (Eduma theme, Thim/LearnPress add-ons, Fluent pro plugins, Slider Revolution, Paid Memberships Pro, …) are vendored as full source under `keds/private-packages/` and installed as Composer path repositories. `keds/package-source-manifest.json` records which slug comes from which source.
- The `postbuild` Composer script copies `wp-config.php` and `mu-plugins/` into the WordPress tree and installs the redis-cache object-cache drop-in.

The production filesystem is read-only. The only writable mounts are `wp-content/uploads` and `wp-content/cache`; all code changes go through Git. WP-Cron runs from a platform cron every 5 minutes.

Services: MariaDB 11.8 and Redis 8.0 (object cache via the redis-cache drop-in). The Upsun router caches anonymous traffic, with a cookie allowlist for WordPress/WooCommerce sessions.

## Deploys

The deploy hook ([keds/scripts/deploy.sh](keds/scripts/deploy.sh)):

1. Runs `wp core update-db`.
2. Runs pending **deploy migrations** — ordered shell scripts under [keds/deploy-migrations/](keds/deploy-migrations/README.md) for one-time runtime state changes (activating plugins, switching themes). Each runs once per database, tracked in a `keds_deploy_migration_*` option.
3. Ensures the Redis object cache is enabled and runs due cron events. Set `KEDS_FLUSH_CACHE_ON_DEPLOY=1` to flush the object cache on deploy.

## Updating plugins and themes

- **Public packages**: bump the pin in `keds/composer.json` (Dependabot also raises PRs).
- **Thim-distributed premium packages** (Eduma, thim-core, LearnPress add-ons): `keds/scripts/thim-update.sh` — checks/downloads updates through ThimPress's API *on the production container*, so requests use the site's activated license. Also run on a schedule by CI, which opens PRs.
- **Other premium packages** (Fluent pro, Paid Memberships Pro, …): `keds/scripts/premium-update.sh` — same trust model, driven through WordPress's own update transients on the container.

Both scripts vendor the new source into `private-packages/` and update the Composer pin; nothing is committed automatically — review `git diff` and push. They require an authenticated `upsun` CLI, `composer`, `python3`, and `unzip`.

## CI

GitHub Actions on every PR:

- **PR checks** ([pr-checks.yml](.github/workflows/pr-checks.yml)) — hermetic tier: validates the Composer manifests, runs the full build, lints `wp-content` under PHP 8.4, installs WordPress with the production plugin set, and smoke-tests it under a local server.
- **PR E2E** ([pr-e2e.yml](.github/workflows/pr-e2e.yml)) — waits for the Upsun preview environment the GitHub integration builds for the PR, then runs the Playwright smoke tests from [tests/e2e/](tests/e2e/) against it.
- **Auto-merge** ([automerge.yml](.github/workflows/automerge.yml)) — enables GitHub native auto-merge on eligible update PRs once checks pass.

Scheduled:

- **Thim premium updates** ([thim-update.yml](.github/workflows/thim-update.yml)) — weekdays 05:30 UTC, raises PRs for available premium updates.
- **Daily PR report** ([daily-pr-report.yml](.github/workflows/daily-pr-report.yml)) — 06:30 UTC digest email of open update PRs.

## Local E2E runs

```bash
cd tests/e2e
npm install
npx playwright test
```

See [playwright.config.ts](tests/e2e/playwright.config.ts) for the target URL configuration.

## History

This repository replaced a Pantheon-hosted build where the full WordPress tree was committed. Pantheon-specific artifacts (mu-plugin, `wp-config-pantheon.php`, Object Cache Pro, Native PHP Sessions) were removed during the migration; `extra.distro.removed` in `keds/composer.json` tracks the intentionally dropped packages.
