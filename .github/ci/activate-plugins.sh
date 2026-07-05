#!/usr/bin/env bash
#
# Activate the production plugin set inside the CI WordPress install.
#
# The list is derived from package-source-manifest.json: the live-database
# active plugins, with the replacement map applied and removed packages
# dropped, ordered parents-before-add-ons so dependent plugins (LearnPress
# add-ons, Fluent pro, thim-elementor-kit, ...) activate after the plugin
# they extend.
#
# Run from keds/ (alongside wp-cli.local.yml): bash ../.github/ci/activate-plugins.sh
#
# Deliberately no `set -e`: activation failures are collected and reported
# together, then the script exits non-zero.

set -uo pipefail

MANIFEST="${1:-package-source-manifest.json}"

mapfile -t desired < <(python3 - "$MANIFEST" <<'PY'
import json, sys

manifest = json.load(open(sys.argv[1]))
db = manifest["live-database"]
removed = set(manifest.get("removed", []))
replacement = db.get("replacement", {})

plugins = []
for slug in db["active-plugins"]:
    slug = replacement.get(slug, slug)
    if slug in removed or slug in plugins:
        continue
    plugins.append(slug)

# Parents whose add-ons misbehave (or self-deactivate) when activated first.
parents = ["learnpress", "woocommerce", "elementor", "fluent-crm",
           "fluentform", "paid-memberships-pro", "thim-core"]
ordered = [p for p in parents if p in plugins] + [p for p in plugins if p not in parents]
print("\n".join(ordered))
PY
)

if [ "${#desired[@]}" -eq 0 ]; then
	echo "ERROR: derived an empty plugin list from $MANIFEST" >&2
	exit 1
fi

installed=$(wp plugin list --field=name)

missing=()
for slug in "${desired[@]}"; do
	grep -qx "$slug" <<<"$installed" || missing+=("$slug")
done
if [ "${#missing[@]}" -gt 0 ]; then
	echo "ERROR: plugins expected active in production are missing from the build: ${missing[*]}" >&2
	echo "       (composer.json / package-source-manifest.json out of sync?)" >&2
	exit 1
fi

failed=()
for slug in "${desired[@]}"; do
	echo "==> activating $slug"
	wp plugin activate "$slug" || failed+=("$slug")
done

# Some plugins self-deactivate from their activation hook; check final state.
for slug in "${desired[@]}"; do
	status=$(wp plugin get "$slug" --field=status 2>/dev/null || echo unknown)
	case "$status" in
		active|active-network) ;;
		*) failed+=("$slug[$status]") ;;
	esac
done

if [ "${#failed[@]}" -gt 0 ]; then
	printf 'ERROR: plugin activation failures:\n' >&2
	printf '  - %s\n' "${failed[@]}" | sort -u >&2
	exit 1
fi

echo "OK: ${#desired[@]} plugins active"
