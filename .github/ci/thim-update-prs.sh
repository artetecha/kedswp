#!/usr/bin/env bash
#
# Raise one PR per vendored premium package that has an update available.
#
# Discovery and vendoring are now delegated to the upsun-wp vendoring engine
# (`wp upsun vendor`, upsun-wp >= 0.5) driven on the production container by
# keds/scripts/thim-update.sh — see that script for the trust model (license
# tokens never leave the container). A single `check --porcelain` call reports
# every pending update as "slug local remote fetcher"; the fetcher id maps
# back to the two historical channels so branch prefixes and labels are
# unchanged:
#   - thimpress -> thim/<slug>    label thim-update    (Eduma, thim-*, LP add-ons)
#   - transient -> premium/<slug> label premium-update (Fluent pro, PMPro, ...)
#
# Driven by .github/workflows/thim-update.yml; runs from the repo root.
#
# Each branch is rebuilt from origin/main on every run, so sibling PRs that
# went stale when one merged (composer.json/lock overlap) self-heal on the
# next run. A marker in the PR body encodes the proposed version so unchanged
# PRs are skipped instead of force-pushed.
#
# NOTE: the old per-channel "uncovered packages" audit was dropped in the
# engine migration. Discovery is now per-package over private-packages/ (every
# vendored package is dry-run every run); a package whose licensed updater is
# silently unwired simply yields no update, indistinguishable from up-to-date.
# The job summary reports scanned/raised counts instead.
#
# Expects: GH_TOKEN (a PAT, NOT the workflow GITHUB_TOKEN — PRs created by
# GITHUB_TOKEN never trigger the pull_request test workflows), an
# authenticated upsun CLI, composer, php, python3, git author configured.
#
# Usage: thim-update-prs.sh [slugs...]   (no args = all available updates)

set -euo pipefail

BASE_BRANCH="main"
REQUESTED=("$@")

# retry <attempts> <cmd...>: exponential-ish backoff (30s, 60s, 120s...).
# The Upsun API occasionally answers 503 (seen 2026-07-05); one transient
# error must not kill a whole scheduled run.
retry() {
	local attempts="$1" delay=30 i
	shift
	for (( i = 1; i <= attempts; i++ )); do
		"$@" && return 0
		if (( i < attempts )); then
			echo "==> attempt $i/$attempts failed, retrying in ${delay}s" >&2
			sleep "$delay"
			delay=$(( delay * 2 ))
		fi
	done
	echo "ERROR: all $attempts attempts failed: $*" >&2
	return 1
}

# Map a fetcher id to its historical channel presentation.
channel_prefix() { case "$1" in thimpress) echo thim;;    transient) echo premium;;        *) echo vendor;; esac; }
channel_label()  { case "$1" in thimpress) echo thim-update;; transient) echo premium-update;; *) echo vendored-update;; esac; }
channel_desc()   { case "$1" in thimpress) echo "ThimPress licensed channel";; transient) echo "WP licensed-update channel";; *) echo "vendoring engine";; esac; }

git fetch origin "$BASE_BRANCH"

echo "==> checking every vendored package via the vendoring engine"
updates=$(retry 3 keds/scripts/thim-update.sh check --porcelain)

echo "--- updates ---"; echo "${updates:-none}"

# Labels are created up front (idempotent); PRs carry the channel label.
for lbl in thim-update premium-update; do
	gh label create "$lbl" --color 5319e7 \
		--description "Automated premium package update" --force >/dev/null 2>&1 || true
done

failures=()
raised=0

while read -r slug local_ver remote_ver fetcher; do
	[ -n "$slug" ] || continue

	if [ "${#REQUESTED[@]}" -gt 0 ]; then
		wanted=false
		for r in "${REQUESTED[@]}"; do [ "$r" = "$slug" ] && wanted=true; done
		$wanted || { echo "==> $slug: not in requested set, skipping"; continue; }
	fi

	prefix="$(channel_prefix "$fetcher")"
	label="$(channel_label "$fetcher")"
	desc="$(channel_desc "$fetcher")"
	branch="$prefix/$slug"
	# Marker uses the channel prefix (thim|premium) for continuity with PRs
	# raised by the pre-migration pipeline.
	marker="<!-- $prefix: ${slug}@${remote_ver} -->"

	pr_json=$(gh pr list --head "$branch" --base "$BASE_BRANCH" --state open --json number,body,mergeable --jq '.[0] // empty')
	if [ -n "$pr_json" ] && grep -qF "$marker" <<<"$pr_json"; then
		# Same version already proposed — but only skip if the PR is still
		# mergeable. Conflicted branches (composer.lock overlap after a
		# sibling merged) MUST rebuild or they stay stale forever: the whole
		# self-heal design hinges on this.
		mergeable=$(python3 -c 'import json,sys; print(json.loads(sys.argv[1]).get("mergeable",""))' "$pr_json")
		if [ "$mergeable" != "CONFLICTING" ]; then
			echo "==> $slug: open PR already proposes $remote_ver, skipping"
			continue
		fi
		echo "==> $slug: open PR is conflicted, rebuilding from $BASE_BRANCH"
	fi

	echo "==> $slug: $local_ver -> $remote_ver ($fetcher)"
	# </dev/null: commands inside (upsun ssh in particular) must not slurp
	# the porcelain lines this loop is reading from stdin.
	if ! (
		set -euo pipefail
		git checkout -B "$branch" "origin/$BASE_BRANCH"
		git reset --hard "origin/$BASE_BRANCH"
		git clean -fd keds/private-packages

		keds/scripts/thim-update.sh update "$slug"

		git add -A keds/private-packages keds/composer.json keds/composer.lock
		git commit -m "Update ${slug} ${local_ver} -> ${remote_ver} (${desc})"
		git push --force origin "$branch"

		body=$(printf '%s\n\nAutomated update of `%s` from **%s** to **%s** via the %s (`wp upsun vendor`).\n\nMerging deploys to production — review the CI results and the preview environment first. This PR is human-merged by design.\n' \
			"$marker" "$slug" "$local_ver" "$remote_ver" "$desc")

		if [ -n "$pr_json" ]; then
			pr_number=$(python3 -c 'import json,sys; print(json.loads(sys.argv[1])["number"])' "$pr_json")
			gh pr edit "$pr_number" \
				--title "Update ${slug} ${local_ver} → ${remote_ver}" --body "$body"
		else
			gh pr create --head "$branch" --base "$BASE_BRANCH" \
				--title "Update ${slug} ${local_ver} → ${remote_ver}" \
				--label "$label" --body "$body"
		fi
	) </dev/null; then
		echo "ERROR: failed to raise PR for $slug, continuing with the rest" >&2
		failures+=("$slug")
	else
		raised=$(( raised + 1 ))
	fi
done <<<"$updates"

git checkout --detach "origin/$BASE_BRANCH" >/dev/null 2>&1 || true

scanned=$(find keds/private-packages/plugins keds/private-packages/themes \
	-mindepth 1 -maxdepth 1 -type d 2>/dev/null | wc -l | tr -d ' ')

echo "==> scanned ${scanned} vendored package(s); raised/refreshed ${raised} PR(s)"
if [ -n "${GITHUB_STEP_SUMMARY:-}" ]; then
	{
		echo "## Vendored premium updates"
		echo
		echo "- Packages scanned: ${scanned}"
		echo "- Update PRs raised/refreshed: ${raised}"
		echo
		echo "_Discovery is per-package via \`wp upsun vendor --update --dry-run\` on the production container. A package whose licensed updater is unwired yields no update (indistinguishable from up-to-date) and is no longer separately flagged._"
	} >> "$GITHUB_STEP_SUMMARY"
fi

if [ "${#failures[@]}" -gt 0 ]; then
	echo "ERROR: failed packages: ${failures[*]}" >&2
	exit 1
fi
