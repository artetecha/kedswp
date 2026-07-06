#!/usr/bin/env bash

set -euo pipefail

# Import a staged database dump, if any (see scripts/db-import.sh). Runs
# before update-db and the migrations so both apply to the imported data.
bash scripts/db-import.sh

cd wordpress

if ! wp core is-installed; then
	echo "WordPress is not installed yet. Import the Pantheon database before runtime activation."
	exit 0
fi

wp core update-db

bash ../scripts/run-deploy-migrations.sh

wp redis enable || true
if [ "${KEDS_FLUSH_CACHE_ON_DEPLOY:-0}" = "1" ]; then
	wp cache flush || true
else
	wp redis status || true
fi
wp cron event run --due-now || true
