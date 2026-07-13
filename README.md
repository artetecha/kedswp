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
├── scripts/           Deploy hook, premium update + content-migration tooling
├── migrations/        One-time runtime migrations for `wp upsun migrate` (see its README)
└── wordpress/         Build output — gitignored, never edit by hand
tests/e2e/             Playwright smoke tests run against Upsun preview environments
```

## How the build works

`.upsun/config.yaml` runs Composer with the `composer` build flavor from `keds/`:

- **Public packages** come from [wpackagist](https://wpackagist.org) (pinned versions).
- The **Upsun mu-plugin** ([artetecha/upsun-wp](https://github.com/artetecha/upsun-wp)) comes from Packagist. It installs into `composer-mu-plugins/` and `postbuild` copies it into the build — Composer's alphabetical install order would otherwise let the WordPress core extraction delete it from `wordpress/`.
- **Premium packages** (Eduma theme, Thim/LearnPress add-ons, Fluent pro plugins, Slider Revolution, Paid Memberships Pro, …) are vendored as full source under `keds/private-packages/` and installed as Composer path repositories. `keds/package-source-manifest.json` records which slug comes from which source.
- The `postbuild` Composer script copies `wp-config.php` and `mu-plugins/` into the WordPress tree and installs the redis-cache object-cache drop-in.

The production filesystem is read-only. The only writable mounts are `wp-content/uploads` and `wp-content/cache`; all code changes go through Git. WP-Cron runs from a platform cron every 5 minutes.

Services: MariaDB 11.8 and Redis 8.0 (object cache via the redis-cache drop-in). The Upsun router caches anonymous traffic, with a cookie allowlist for WordPress/WooCommerce sessions.

## Deploys

The deploy hook ([keds/scripts/deploy.sh](keds/scripts/deploy.sh)):

1. Runs `wp core update-db`.
2. Runs `wp upsun migrate` — ordered PHP migrations under [keds/migrations/](keds/migrations/README.md) for one-time runtime state changes (activating plugins, switching themes). Each runs once per database, tracked in an `upsun_migration_*` option; a failure aborts the deploy.
3. Ensures the Redis object cache is enabled and runs due cron events. Set `KEDS_FLUSH_CACHE_ON_DEPLOY=1` to flush the object cache on deploy.

## Updating plugins and themes

- **Public packages**: bump the pin in `keds/composer.json` (Dependabot also raises PRs).
- **Thim-distributed premium packages** (Eduma, thim-core, LearnPress add-ons): `keds/scripts/thim-update.sh` — checks/downloads updates through ThimPress's API *on the production container*, so requests use the site's activated license. Also run on a schedule by CI, which opens PRs.
- **Other premium packages** (Fluent pro, Paid Memberships Pro, …): `keds/scripts/premium-update.sh` — same trust model, driven through WordPress's own update transients on the container.

Both scripts vendor the new source into `private-packages/` and update the Composer pin; nothing is committed automatically — review `git diff` and push. They require an authenticated `upsun` CLI, `composer`, `python3`, and `unzip`.

## Content migration from Pantheon

Until cutover, Pantheon remains the content source of truth and the Upsun database is a periodically refreshed copy.

**Importing a fresh Pantheon dump** — stage the dump in the `db-import` mount (outside the web root; dumps must never be web-accessible) and redeploy:

```bash
cat pantheon-backup.sql.gz | upsun ssh -p idpo3r4eqatcu -e <env> 'cat > db-import/pantheon.sql.gz'
upsun operation:run content-import -p idpo3r4eqatcu -e <env>
```

The `content-import` runtime operation exists because nothing else reliably runs the deploy hook on demand: `upsun environment:redeploy` only re-provisions the deployment, and an **empty commit is not enough either** — the tree ID is unchanged, so the build is reused and hooks are skipped. Deploy hooks only run when a deploy ships a new build. Any real code push therefore also triggers a staged import; the operation is for importing without one. Two differences from a push-triggered import: crons are not paused while the operation runs, and the environment keeps serving during it — anything hitting the site mid-import (including the PR E2E job, which starts as soon as the environment deploys) sees a databaseless WordPress and fails. Wait for CI to finish before running the operation on a PR environment, and prefer coupling the final production import to a real push, where the platform closes the environment during the deploy hook.

The deploy hook runs [keds/scripts/db-import.sh](keds/scripts/db-import.sh) *before* `wp core update-db` and the deploy migrations: it takes a pre-import safety dump (kept in `db-import/backups/`), drops the existing WordPress tables, imports the staged dump, restores the pre-import `active_plugins` option (the plugin list is code state owned by this build, not content), and flushes the object cache. Because a Pantheon dump carries no `keds_deploy_migration_*` options, every deploy migration then re-runs against the imported data in the same deployment — so migrations must stay safe against current Pantheon production data until cutover. After a successful import the dump is renamed `*.imported-<timestamp>`, so each staged dump imports exactly once and ordinary deploys are unaffected.

**Verifying a sync** — [keds/scripts/db-compare.py](keds/scripts/db-compare.py) compares two SQL dumps (plain or gzipped) without needing a local database: table inventory, per-table row counts, latest content activity, key options, and recorded deploy-migration state.

```bash
keds/scripts/db-compare.py --labels pantheon,upsun pantheon-backup.sql.gz upsun-dump.sql.gz
```

Use it to measure content drift between syncs and to sanity-check an import (expected differences after a sync: `wp_pantheon_sessions` dropped, the `keds_deploy_migration_*` options present, `active_plugins` kept as the pre-import Upsun list, Pantheon-only cron hooks removed). With `--options` it instead reports the full `wp_options` diff (volatile options filtered out) — run it before cutover to confirm no unaccounted-for configuration exists only on the Upsun side.

**Verifying learner progress** — [keds/scripts/lp-progress-check.py](keds/scripts/lp-progress-check.py) is the cutover gate for the content that matters most: it extracts every LearnPress progress row with recent activity from the imported dump and field-compares each one (plus attached grade rows) against the live environment, printing the freshest completions with student names. Non-zero exit on any missing or differing row.

```bash
keds/scripts/lp-progress-check.py pantheon-backup.sql.gz pr-34 --since 2026-06-20
```

## CI

GitHub Actions on every PR:

- **PR checks** ([pr-checks.yml](.github/workflows/pr-checks.yml)) — hermetic tier: validates the Composer manifests, runs the full build, lints `wp-content` under PHP 8.4, installs WordPress with the production plugin set, and smoke-tests it under a local server.
- **PR E2E** ([pr-e2e.yml](.github/workflows/pr-e2e.yml)) — waits for the Upsun preview environment the GitHub integration builds for the PR, then runs the Playwright smoke tests from [tests/e2e/](tests/e2e/) against it.
- **Auto-merge** ([automerge.yml](.github/workflows/automerge.yml)) — enables GitHub native auto-merge on eligible update PRs once checks pass.

Scheduled:

- **Thim premium updates** ([thim-update.yml](.github/workflows/thim-update.yml)) — weekdays 05:43 UTC, raises PRs for available premium updates.
- **Daily PR report** ([daily-pr-report.yml](.github/workflows/daily-pr-report.yml)) — 06:17 UTC digest email of open update PRs.

## Local E2E runs

```bash
cd tests/e2e
npm install
npx playwright test
```

See [playwright.config.ts](tests/e2e/playwright.config.ts) for the target URL configuration.

## History

This repository replaced a Pantheon-hosted build where the full WordPress tree was committed. Pantheon-specific artifacts (mu-plugin, `wp-config-pantheon.php`, Object Cache Pro, Native PHP Sessions) were removed during the migration; `extra.distro.removed` in `keds/composer.json` tracks the intentionally dropped packages.
