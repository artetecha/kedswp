#!/usr/bin/env bash
#
# Update vendored premium packages in private-packages/ by driving the
# upsun-wp vendoring engine (`wp upsun vendor`, upsun-wp >= 0.5) ON the
# production container, so every request carries the site's activated
# license and the license token never leaves the container.
#
# One engine now covers both former channels — a fetcher is auto-selected
# per package:
#   - ThimPress catalog (Eduma, thim-core, LearnPress add-ons, revslider):
#     the Keds_ThimPress_Fetcher mu-plugin (keds/mu-plugins).
#   - WordPress update transients (Fluent pro, Paid Memberships Pro, any
#     future licensed plugin): the engine's built-in TransientFetcher.
# Which one resolved a given update is reported in the "fetcher" column
# (thimpress | transient), so the PR pipeline can keep its per-channel
# branch prefixes and labels.
#
# The engine downloads, extracts and re-vendors ON the container (writing to
# the container's /tmp); the finished Composer-ready package tree is streamed
# back here (tar over base64, same transport the old scripts used) and dropped
# into private-packages/. Nothing is committed — review `git diff` and push.
#
# Usage:
#   scripts/thim-update.sh check                 # show available updates
#   scripts/thim-update.sh check --porcelain     # "slug local remote fetcher" lines
#   scripts/thim-update.sh update <slug> [...]   # vendor new versions
#
# Requires: upsun CLI (authenticated), composer, php. (No python3/unzip: the
# engine does the extraction and composer.json rewrite on the container.)

set -euo pipefail

PROJECT="${UPSUN_PROJECT:-idpo3r4eqatcu}"
ENVIRONMENT="${UPSUN_ENV:-main}"
ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
PKG_DIR="${ROOT_DIR}/private-packages"

# Slugs are interpolated into shell commands executed on the credential-bearing
# production container, so every one MUST match this before it goes near SSH.
SLUG_RE='^[A-Za-z0-9._-]+$'

# Download/vendor timeout on the container (seconds). The engine's own
# wp_remote_get already caps the download at 120s; this is a belt-and-suspenders
# bound on the whole vendor step, run container-side (Linux `timeout`) so it
# needs no `timeout` binary locally (macOS lacks one).
REMOTE_TIMEOUT=600

# Run an inline bash snippet on the production container over SSH.
remote() { # remote <inline-bash>
	upsun ssh -p "$PROJECT" -e "$ENVIRONMENT" --app keds --no-interaction "$1"
}

# Every vendored package as "slug type" (type is plugin|theme). Slugs that
# fail SLUG_RE are skipped loudly rather than interpolated into a remote shell.
vendored_entries() {
	local sub type pkg slug
	for sub in plugins themes; do
		[ -d "$PKG_DIR/$sub" ] || continue
		type=plugin; [ "$sub" = themes ] && type=theme
		for pkg in "$PKG_DIR/$sub"/*/; do
			[ -f "${pkg}composer.json" ] || continue
			slug="$(basename "$pkg")"
			if [[ ! "$slug" =~ $SLUG_RE ]]; then
				echo "WARN: skipping unsafe package name '$slug'" >&2
				continue
			fi
			echo "$slug $type"
		done
	done
}

# Resolve a slug to "kind type vendor-namespace" from its location.
slug_layout() { # slug_layout <slug> -> "themes theme keds-theme" | "plugins plugin keds-plugin"
	if [ -d "$PKG_DIR/themes/$1" ]; then
		echo "themes theme keds-theme"
	elif [ -d "$PKG_DIR/plugins/$1" ]; then
		echo "plugins plugin keds-plugin"
	else
		return 1
	fi
}

cmd_check() { # cmd_check [table|porcelain]
	local format="${1:-table}"
	local entries args raw parsed slug type
	entries="$(vendored_entries)"
	if [ -z "$entries" ]; then
		[ "$format" = table ] && echo "No vendored packages found."
		return 0
	fi

	# "slug:type" tokens for the remote loop (both halves already SLUG_RE /
	# fixed-value safe). --type is passed on both the check and the apply
	# paths so the fetcher/version the check reports is the one apply vendors.
	args=""
	while read -r slug type; do
		[ -n "$slug" ] && args+=" ${slug}:${type}"
	done <<<"$entries"

	# One SSH round-trip. The `wp cli has-command` preflight runs WITHOUT
	# `|| true`: a missing engine (bad deploy) fails the remote block under
	# `set -e`, so a real outage propagates as a non-zero exit that `retry`
	# in the PR pipeline re-attempts — instead of an empty stdout that reads
	# as "everything up to date". Each dry-run prints, per pending update,
	# "Would update <slug>: <from> → <to> via <fetcher>."
	raw="$(remote "
		set -e
		cd /app/wordpress
		wp cli has-command 'upsun vendor'
		wp eval 'wp_update_plugins(); wp_update_themes();' >/dev/null 2>&1 || true
		for pair in ${args}; do
			s=\"\${pair%%:*}\"; t=\"\${pair##*:}\"
			timeout ${REMOTE_TIMEOUT} wp upsun vendor \"\$s\" --update --type=\"\$t\" --dry-run 2>/dev/null || true
		done
	")"

	# Tolerate a CRLF from the transport and either arrow glyph; emit
	# "slug local remote fetcher" per pending update.
	parsed="$(printf '%s\n' "$raw" | tr -d '\r' \
		| sed -nE 's/^Would update ([^:]+): ([^ ]+) (→|->) ([^ ]+) via ([^.]+)\.?$/\1 \2 \4 \5/p')"

	if [ "$format" = porcelain ]; then
		printf '%s\n' "$parsed" | sed '/^$/d'
		return 0
	fi

	if [ -z "${parsed//[$'\n\t ']/}" ]; then
		echo "Everything up to date."
		return 0
	fi
	printf '%-40s %-12s %-12s %s\n' PACKAGE LOCAL AVAILABLE FETCHER
	printf '%s\n' "$parsed" | while read -r slug cur new fetcher; do
		[ -n "$slug" ] && printf '%-40s %-12s %-12s %s\n' "$slug" "$cur" "$new" "$fetcher"
	done
}

cmd_update() { # cmd_update <slug>
	local slug="$1" layout kind type ns tmp version
	if [[ ! "$slug" =~ $SLUG_RE ]]; then
		echo "ERROR: refusing unsafe package name '$slug'" >&2; return 1
	fi
	layout="$(slug_layout "$slug")" || { echo "ERROR: $slug is not in private-packages/" >&2; return 1; }
	read -r kind type ns <<<"$layout"

	tmp="$(mktemp -d)"
	# shellcheck disable=SC2064
	trap "rm -rf '$tmp'" RETURN

	echo "==> $slug: resolving + downloading on the container (license token stays on the container)"
	# Prime the transient, re-vendor into the container's /tmp, then stream
	# the finished package back. wp's own output is redirected to stderr
	# (surfaced on this terminal) so only the tarball reaches the pipe.
	remote "
		set -e
		cd /app/wordpress
		wp eval 'wp_update_plugins(); wp_update_themes();' >/dev/null 2>&1 || true
		rm -rf /tmp/keds-vendor && mkdir -p /tmp/keds-vendor
		timeout ${REMOTE_TIMEOUT} wp upsun vendor '$slug' --update --type='$type' --to=/tmp/keds-vendor --vendor='$ns' 1>&2
		if [ -d /tmp/keds-vendor/'$slug' ]; then
			tar -C /tmp/keds-vendor -cf - '$slug' | base64
		fi
		rm -rf /tmp/keds-vendor
	" | base64 -d > "$tmp/pkg.tar"

	if [ ! -s "$tmp/pkg.tar" ]; then
		echo "==> $slug: up to date; nothing vendored."
		return 0
	fi

	echo "==> $slug: vendoring the new version"
	rm -rf "${PKG_DIR:?}/$kind/$slug"
	mkdir -p "$PKG_DIR/$kind"
	tar -C "$PKG_DIR/$kind" -xf "$tmp/pkg.tar"

	version="$(php -r '$c = json_decode(file_get_contents($argv[1]), true); echo is_array($c) ? ($c["version"] ?? "") : "";' "$PKG_DIR/$kind/$slug/composer.json")"

	# Root composer.json pins path packages as "*" (the vendored
	# composer.json is the version authority), so only this package's own
	# lock entry changes — sibling update PRs no longer conflict.
	( cd "$ROOT_DIR" && composer update --no-install --no-scripts --quiet "$ns/$slug" )
	echo "==> $slug: done (${version:-unknown}). Review with git diff, then commit & push."
}

case "${1:-}" in
	check)
		shift
		format="table"
		[ "${1:-}" = "--porcelain" ] && format="porcelain"
		cmd_check "$format"
		;;
	update)
		shift
		[ $# -ge 1 ] || { echo "usage: $0 update <slug> [...]" >&2; exit 1; }
		for slug in "$@"; do cmd_update "$slug"; done
		;;
	*) echo "usage: $0 check [--porcelain] | update <slug> [...]" >&2; exit 1 ;;
esac
