#!/usr/bin/env bash

set -euo pipefail

cd wordpress

if ! wp core is-installed; then
	echo "WordPress is not installed yet; skipping post-deploy tasks."
	exit 0
fi

# Refresh the environment stamp (production) or fire the preview sanitize
# actions when the data was just cloned or synced (previews). post_deploy is
# the only hook that runs on every redeploy, including data syncs, which is
# why this lives here and not in deploy.sh.
wp upsun sanitize --if-needed
