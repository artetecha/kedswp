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
# Run from keds/wordpress/ (mirrors prod's /app/wordpress cwd — some plugins
# resolve bundled autoloaders relative to the cwd via include_path):
#   bash ../../.github/ci/activate-plugins.sh ../package-source-manifest.json
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
# optimization-detective is a `Requires Plugins:` dependency of
# image-prioritizer — WP core refuses to activate a dependent first.
parents = ["learnpress", "woocommerce", "elementor", "fluent-crm",
           "fluentform", "paid-memberships-pro", "thim-core",
           "optimization-detective"]
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

# timeout: a single wedged activation (e.g. a plugin retrying blocked
# network calls forever) must become a failed slug with a visible error,
# not a job that stalls until the workflow-level timeout reaps it.
failed=()
for slug in "${desired[@]}"; do
	echo "==> activating $slug"
	timeout 180 wp plugin activate "$slug" || failed+=("$slug")

	if [ "$slug" = "learnpress" ]; then
		# LearnPress writes its learnpress_version option (and runs its
		# install routine) only under is_admin(); several premium add-ons
		# (stripe, students-list, woo-payment) read that option and
		# self-deactivate when it's missing. One admin-context command
		# triggers the same sync a wp-admin pageview would.
		echo "==> priming LearnPress install state (admin context)"
		timeout 120 wp --context=admin option get learnpress_version || true
	fi
done

# Some plugins self-deactivate from their activation hook; check final state.
# stderr is left visible on purpose: if WordPress is wedged (an activation
# fatal), the real error must reach the log, not /dev/null.
#
# wp-cli's contract is that `--field=status` prints ONLY the status value on
# stdout; diagnostics belong on stderr. A misbehaving plugin can break that —
# e.g. Elementor 4.2.0's Logger\Manager dumps a captured-error report to raw
# stdout at PHP shutdown, gluing it onto the value and (since it fires on
# every bootstrap) failing every plugin at once. So extract the known status
# token wherever it sits rather than exact-matching the whole capture, and
# warn loudly when there was extra output — that's an upstream stdout-contract
# violation we want visible in the log, not silently swallowed.
for slug in "${desired[@]}"; do
	raw=$(timeout 60 wp plugin get "$slug" --field=status || echo unknown)
	status=$(printf '%s\n' "$raw" \
		| grep -xE 'active|active-network|inactive|must-use|dropin' \
		| head -n1)
	status=${status:-$raw}

	if [ "$raw" != "$status" ]; then
		printf 'WARNING: %s status read produced stdout beyond %q — a plugin is\n' "$slug" "$status" >&2
		printf '         writing to stdout during `wp plugin get` (WP-CLI stdout-\n' >&2
		printf '         contract violation). First lines of the raw capture:\n' >&2
		printf '%s\n' "$raw" | head -n5 | sed 's/^/         | /' >&2
	fi

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
