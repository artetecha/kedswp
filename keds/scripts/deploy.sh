#!/usr/bin/env bash

set -euo pipefail

cd wordpress

if ! wp core is-installed; then
	echo "WordPress is not installed yet. Import the Pantheon database before runtime activation."
	exit 0
fi

wp core update-db

DEFAULT_THEME=$(jq -r '.[ "extra" ][ "distro" ][ "default-theme" ]' ../composer.json)
if ! wp theme is-installed "${DEFAULT_THEME}"; then
	echo "Required theme ${DEFAULT_THEME} is not installed."
	exit 1
fi
wp theme activate "${DEFAULT_THEME}"

jq -r '.[ "extra" ][ "distro" ][ "enable-plugins" ][]' ../composer.json |
while read -r PLUGIN; do
	wp plugin activate "${PLUGIN}"
done

wp redis enable || true
if [ "${KEDS_FLUSH_CACHE_ON_DEPLOY:-0}" = "1" ]; then
	wp cache flush || true
else
	wp redis status || true
fi
wp cron event run --due-now || true
