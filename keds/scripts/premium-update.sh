#!/usr/bin/env bash
#
# Update vendored premium packages that are NOT Thim-distributed (Fluent
# pro, Paid Memberships Pro, ...) via WordPress's own update transients on
# the production container: licensed updaters inject authenticated download
# URLs there, exactly as wp-admin sees them. Sibling of thim-update.sh —
# same trust model (tokens never leave the container), same vendoring flow.
#
# Usage:
#   scripts/premium-update.sh check                  # show available updates
#   scripts/premium-update.sh check --porcelain      # "slug local remote" lines
#   scripts/premium-update.sh coverage               # private packages visible to the WP update system
#   scripts/premium-update.sh update <slug> [...]    # vendor new versions
#
# Requires: upsun CLI (authenticated), composer, python3, unzip.

set -euo pipefail

PROJECT="${UPSUN_PROJECT:-idpo3r4eqatcu}"
ENVIRONMENT="${UPSUN_ENV:-main}"
ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
HELPER="${ROOT_DIR}/scripts/premium-update-helper.php"
PKG_DIR="${ROOT_DIR}/private-packages"

# write_package_composer <pkg_dir> <name> <type> <version>
# Preserve the upstream composer.json (some plugins read it at runtime —
# fluentcampaign-pro needs extra.wpfluent.namespace), overriding only our
# pin fields and stripping anything that could leak into project dependency
# resolution or app-wide autoloading.
write_package_composer() {
	python3 - "$1" "$2" "$3" "$4" <<'PYEOF'
import json, pathlib, sys
pkg, name, ptype, version = pathlib.Path(sys.argv[1]), sys.argv[2], sys.argv[3], sys.argv[4]
cj = pkg / "composer.json"
data = {}
if cj.is_file():
    try:
        loaded = json.load(cj.open())
        if isinstance(loaded, dict):
            data = loaded
    except ValueError:
        pass
for key in ("require", "require-dev", "repositories", "scripts", "config",
            "autoload", "autoload-dev", "minimum-stability", "prefer-stable",
            "provide", "replace", "conflict", "suggest", "bin"):
    data.pop(key, None)
data["name"] = name
data["type"] = ptype
data["version"] = version
cj.write_text(json.dumps(data, indent=1, ensure_ascii=False) + "\n")
PYEOF
}

remote_wp() { # remote_wp <action> [slug]
	upsun ssh -p "$PROJECT" -e "$ENVIRONMENT" --app keds --no-interaction \
		"cd /app/wordpress && wp eval-file - $*" < "$HELPER"
}

cmd_check() { # cmd_check [table|porcelain]
	local format="${1:-table}" listing
	listing=$(remote_wp list)
	python3 - "$PKG_DIR" "$listing" "$format" <<'PY'
import json, sys, pathlib
pkg_dir = pathlib.Path(sys.argv[1])
data = json.loads(sys.argv[2])
fmt = sys.argv[3]
latest = {}
for kind in ("plugins", "themes"):
    entries = data.get(kind) or {}
    if isinstance(entries, list):  # empty PHP array serializes as []
        entries = {}
    for slug, info in entries.items():
        # No package URL means no licensed download — nothing we can vendor.
        if info.get("package"):
            latest[slug] = info["version"]
rows = []
for sub in ("plugins", "themes"):
    for pkg in sorted((pkg_dir / sub).iterdir()):
        cj = pkg / "composer.json"
        if not cj.is_file():
            continue
        slug = pkg.name
        local = json.load(cj.open())["version"]
        remote = latest.get(slug)

        def parse(v):
            return tuple(int(p) for p in v.split(".") if p.isdigit())

        if remote and parse(remote) > parse(local):
            rows.append((slug, local, remote))
if fmt == "porcelain":
    for slug, local, remote in rows:
        print(slug, local, remote)
elif not rows:
    print("Everything up to date (WP-update-channel packages only).")
else:
    print(f"{'PACKAGE':40} {'LOCAL':10} AVAILABLE")
    for slug, local, remote in rows:
        print(f"{slug:40} {local:10} {remote}")
PY
}

cmd_coverage() {
	local listing
	listing=$(remote_wp coverage)
	python3 - "$PKG_DIR" "$listing" <<'PY'
import sys, pathlib
pkg_dir = pathlib.Path(sys.argv[1])
visible = set(sys.argv[2].split())
for sub in ("plugins", "themes"):
    for pkg in sorted((pkg_dir / sub).iterdir()):
        if (pkg / "composer.json").is_file() and pkg.name in visible:
            print(pkg.name)
PY
}

cmd_update() {
	local slug="$1"
	local kind="plugins" comp_type="wordpress-plugin" comp_ns="keds-plugin"
	if [ -d "$PKG_DIR/themes/$slug" ]; then
		kind="themes"; comp_type="wordpress-theme"; comp_ns="keds-theme"
	elif [ ! -d "$PKG_DIR/plugins/$slug" ]; then
		echo "ERROR: $slug is not in private-packages/" >&2; return 1
	fi

	echo "==> $slug: fetching download link (license token stays on the wire, not in logs)"
	local url
	url=$(remote_wp link "$slug")

	local tmp; tmp=$(mktemp -d)
	trap 'rm -rf "$tmp"' RETURN

	echo "==> $slug: downloading on the container"
	upsun ssh -p "$PROJECT" -e "$ENVIRONMENT" --app keds --no-interaction \
		"curl -sfL --max-time 600 '$url' | base64" | base64 -d > "$tmp/pkg.zip"
	[ -s "$tmp/pkg.zip" ] || { echo "ERROR: empty download for $slug" >&2; return 1; }

	unzip -qq "$tmp/pkg.zip" -d "$tmp/unzipped"
	# The zip normally contains a single <slug>/ directory.
	local src="$tmp/unzipped/$slug"
	[ -d "$src" ] || src=$(find "$tmp/unzipped" -mindepth 1 -maxdepth 1 -type d | head -1)
	[ -n "$src" ] || { echo "ERROR: could not find package dir in zip" >&2; return 1; }

	# Read the new version from the plugin header / style.css.
	local version
	if [ "$kind" = "themes" ]; then
		version=$(grep -m1 -Ei "^[[:space:]*]*Version:" "$src/style.css" | sed -E 's/.*Version:[[:space:]]*//;s/[[:space:]]*$//')
	else
		version=$(grep -m1 -REi "^[[:space:]*]*Version:" "$src"/*.php | head -1 | sed -E 's/.*Version:[[:space:]]*//;s/[[:space:]]*$//')
	fi
	[ -n "$version" ] || { echo "ERROR: could not detect version for $slug" >&2; return 1; }

	echo "==> $slug: vendoring $version"
	rm -rf "${PKG_DIR:?}/$kind/$slug"
	mkdir -p "$PKG_DIR/$kind/$slug"
	cp -R "$src/." "$PKG_DIR/$kind/$slug/"
	write_package_composer "$PKG_DIR/$kind/$slug" "$comp_ns/$slug" "$comp_type" "$version"

	# Root composer.json pins path packages as "*" (the vendored
	# composer.json is the version authority), so only this package's own
	# lock entry changes — sibling update PRs no longer conflict.
	( cd "$ROOT_DIR" && composer update --no-install --no-scripts --quiet "$comp_ns/$slug" )
	echo "==> $slug: done ($version). Review with git diff, then commit & push."
}

case "${1:-}" in
	check)
		shift
		format="table"
		if [ "${1:-}" = "--porcelain" ]; then format="porcelain"; fi
		cmd_check "$format"
		;;
	coverage) cmd_coverage ;;
	update)
		shift
		[ $# -ge 1 ] || { echo "usage: $0 update <slug> [...]" >&2; exit 1; }
		for slug in "$@"; do cmd_update "$slug"; done
		;;
	*) echo "usage: $0 check [--porcelain] | coverage | update <slug> [...]" >&2; exit 1 ;;
esac
