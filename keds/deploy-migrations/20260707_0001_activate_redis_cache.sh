#!/usr/bin/env bash
set -euo pipefail

# The Pantheon active_plugins list (imported before db-import.sh learned to
# preserve the local list) lacks redis-cache: Pantheon ran Object Cache Pro,
# which this build removed. The object-cache drop-in works either way, but
# the plugin provides the `wp redis` command deploy.sh relies on and the
# admin UI. Idempotent; also re-runs after every content import as insurance.
wp plugin activate redis-cache
