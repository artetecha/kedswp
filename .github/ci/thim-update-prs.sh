#!/usr/bin/env bash
#
# Raise one PR per vendored premium package that has an update available,
# across two licensed channels:
#   - thim:    ThimPress catalog via thim-core (scripts/thim-update.sh),
#              branches thim/<slug>
#   - premium: WordPress update transients — Fluent pro, PMP, any future
#              licensed plugin (scripts/premium-update.sh), branches
#              premium/<slug>
# A slug the Thim catalog knows is handled by the thim channel exclusively.
# Ends with a coverage audit: vendored packages NO channel can see are
# reported (and fail nothing — they just must not rot silently).
#
# Driven by .github/workflows/thim-update.yml; runs from the repo root.
#
# Each branch is rebuilt from origin/main on every run, so sibling PRs that
# went stale when one merged (composer.json/lock overlap) self-heal on the
# next run. A marker in the PR body encodes the proposed version so
# unchanged PRs are skipped instead of force-pushed.
#
# Expects: GH_TOKEN (a PAT, NOT the workflow GITHUB_TOKEN — PRs created by
# GITHUB_TOKEN never trigger the pull_request test workflows), an
# authenticated upsun CLI, composer, python3, unzip, git author configured.
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

git fetch origin "$BASE_BRANCH"

echo "==> checking the ThimPress channel"
thim_coverage=$(retry 3 keds/scripts/thim-update.sh coverage)
thim_updates=$(retry 3 keds/scripts/thim-update.sh check --porcelain)

echo "==> checking the WP-transient premium channel"
premium_coverage=$(retry 3 keds/scripts/premium-update.sh coverage)
premium_updates=$(retry 3 keds/scripts/premium-update.sh check --porcelain)

# Thim wins for any slug its catalog covers (both channels would serve the
# same bits, but one source of truth per package keeps PRs deterministic).
premium_updates=$(while read -r slug rest; do
	[ -n "$slug" ] || continue
	grep -qx "$slug" <<<"$thim_coverage" || echo "$slug $rest"
done <<<"$premium_updates")

echo "--- thim updates ---";    echo "${thim_updates:-none}"
echo "--- premium updates ---"; echo "${premium_updates:-none}"

failures=()

# process_updates <channel> <script> <branch_prefix> <label> <channel_desc> <<<"$updates"
process_updates() {
	local channel="$1" script="$2" prefix="$3" label="$4" desc="$5"
	local slug local_ver remote_ver branch marker pr_json body pr_number

	gh label create "$label" --color 5319e7 \
		--description "Automated premium package update ($channel channel)" --force >/dev/null 2>&1 || true

	while read -r slug local_ver remote_ver; do
		[ -n "$slug" ] || continue

		if [ "${#REQUESTED[@]}" -gt 0 ]; then
			local wanted=false r
			for r in "${REQUESTED[@]}"; do [ "$r" = "$slug" ] && wanted=true; done
			$wanted || { echo "==> $slug: not in requested set, skipping"; continue; }
		fi

		branch="$prefix/$slug"
		marker="<!-- $channel: ${slug}@${remote_ver} -->"

		pr_json=$(gh pr list --head "$branch" --base "$BASE_BRANCH" --state open --json number,body --jq '.[0] // empty')
		if [ -n "$pr_json" ] && grep -qF "$marker" <<<"$pr_json"; then
			echo "==> $slug: open PR already proposes $remote_ver, skipping"
			continue
		fi

		echo "==> $slug: $local_ver -> $remote_ver ($channel)"
		# </dev/null: commands inside (upsun ssh in particular) must not
		# slurp the porcelain lines this loop is reading from stdin.
		if ! (
			set -euo pipefail
			git checkout -B "$branch" "origin/$BASE_BRANCH"
			git reset --hard "origin/$BASE_BRANCH"
			git clean -fd keds/private-packages

			"$script" update "$slug"

			git add -A keds/private-packages keds/composer.json keds/composer.lock
			git commit -m "Update ${slug} ${local_ver} -> ${remote_ver} (${desc})"
			git push --force origin "$branch"

			body=$(printf '%s\n\nAutomated update of `%s` from **%s** to **%s** via the %s (`%s`).\n\nMerging deploys to production — review the CI results and the preview environment first. This PR is human-merged by design.\n' \
				"$marker" "$slug" "$local_ver" "$remote_ver" "$desc" "${script#keds/}")

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
		fi
	done
}

process_updates thim    keds/scripts/thim-update.sh    thim    thim-update    "ThimPress licensed channel"      <<<"$thim_updates"
process_updates premium keds/scripts/premium-update.sh premium premium-update "WP licensed-update channel"      <<<"$premium_updates"

git checkout --detach "origin/$BASE_BRANCH" >/dev/null 2>&1 || true

# Coverage audit: vendored packages that neither channel can see. These
# would silently never get update PRs — surface them loudly (but don't fail
# the run: an unlicensed package is an ops issue, not a pipeline bug).
uncovered=$(
	for dir in keds/private-packages/plugins/*/ keds/private-packages/themes/*/; do
		slug=$(basename "$dir")
		grep -qx "$slug" <<<"$thim_coverage" && continue
		grep -qx "$slug" <<<"$premium_coverage" && continue
		echo "$slug"
	done
)
if [ -n "$uncovered" ]; then
	echo "WARNING: no update channel covers these vendored packages:" >&2
	echo "$uncovered" | sed 's/^/  - /' >&2
	if [ -n "${GITHUB_STEP_SUMMARY:-}" ]; then
		{
			echo "## ⚠️ Uncovered vendored packages"
			echo
			echo "No licensed update channel can see these (license inactive, or updater not wired):"
			echo
			echo "$uncovered" | sed 's/^/- /'
		} >> "$GITHUB_STEP_SUMMARY"
	fi
else
	echo "==> coverage audit: every vendored package is covered by a channel"
fi

if [ "${#failures[@]}" -gt 0 ]; then
	echo "ERROR: failed packages: ${failures[*]}" >&2
	exit 1
fi
