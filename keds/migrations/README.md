# Deploy Migrations

Deploy migrations are ordered PHP files that run once per WordPress database
during deployment, applied by `wp upsun migrate` (the upsun-mu-plugin) from
the deploy hook. The directory is wired via `UPSUN_MIGRATIONS_DIR` in
`wp-config.php`.

Use these for one-time runtime changes such as activating a newly installed
plugin or switching themes. Composer still installs plugin and theme code;
migrations only change WordPress state.

Migration filenames must use this format (anything else fails the deploy):

```text
YYYYMMDD_NNNN_short_name.php
```

Each file returns a callable that performs the migration; throwing or
returning `false` marks it failed, aborts the deploy before traffic, and
leaves it pending:

```php
<?php

return static function () {
	update_option( 'some_option', 'value' );
};
```

The runner records successful migrations in non-autoloaded WordPress options
named `upsun_migration_<migration_id>`. Keep migrations idempotent where
practical, but assume each one runs only once per database. Pending
migrations are also surfaced by `wp upsun doctor` and Site Health.

**Until the Pantheon cutover**: every content sync imports a fresh Pantheon
database, which carries none of the tracking options — so *all* migrations
re-run after *every* import. Until cutover, each migration must be safe to
run repeatedly against current Pantheon production data, not just the state
it was written for.

**History**: migrations up to `20260707_0002` were originally shell scripts
under `keds/deploy-migrations/` (framework retired in favor of the plugin's
`wp upsun migrate`); they were converted 1:1 to this format. Their old
`keds_deploy_migration_*` markers remain in databases that applied them but
are no longer consulted — the converted files re-ran once under the new
tracking, which is safe by the idempotency contract above.
