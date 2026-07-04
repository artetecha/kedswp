#!/usr/bin/env bash

set -euo pipefail

# The wp-native-php-sessions plugin was deactivated in migration
# 20260704_0001 and its package has been removed from the build.
# PHP sessions now use file storage; this table held only stale
# Pantheon-era session rows (4.8k rows, newest 2026-06-30).
wp db query "DROP TABLE IF EXISTS wp_pantheon_sessions"
