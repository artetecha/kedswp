# Deploy Migrations

Deploy migrations are ordered shell scripts that run once per WordPress database during deployment.

Use these for one-time runtime changes such as activating a newly installed plugin or switching themes. Composer still installs plugin and theme code; migrations only change WordPress state.

Migration filenames must use this format:

```text
YYYYMMDD_NNNN_short_name.sh
```

Examples:

```bash
#!/usr/bin/env bash
set -euo pipefail

wp plugin activate redis-cache
```

```bash
#!/usr/bin/env bash
set -euo pipefail

wp theme activate eduma
```

The migration runner records successful migrations in WordPress options named `keds_deploy_migration_<migration_id>`, with `autoload=no`. Keep migration scripts idempotent where practical, but assume each one runs only once per database.

**Until the Pantheon cutover**: every content sync imports a fresh Pantheon database, which carries none of the tracking options — so *all* migrations re-run after *every* import. Until cutover, each migration must be safe to run repeatedly against current Pantheon production data, not just the state it was written for.
