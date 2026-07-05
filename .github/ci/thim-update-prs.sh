#!/usr/bin/env bash
#
# Raise one PR per Thim-distributed package that has an update available.
# Driven by .github/workflows/thim-update.yml; runs from the repo root.
#
# Each branch (thim/<slug>) is rebuilt from origin/main on every run, so
# sibling PRs that went stale when one merged (composer.json/lock overlap)
# self-heal on the next run. A marker in the PR body encodes the proposed
# version so unchanged PRs are skipped instead of force-pushed.
#
# Expects: GH_TOKEN (a PAT, NOT the workflow GITHUB_TOKEN — PRs created by
# GITHUB_TOKEN never trigger the pull_request test workflows), an
# authenticated upsun CLI, composer, python3, unzip, git author configured.
#
# Usage: thim-update-prs.sh [slugs...]   (no args = all available updates)

set -euo pipefail

BASE_BRANCH="main"
REQUESTED=("$@")

git fetch origin "$BASE_BRANCH"

echo "==> checking the ThimPress channel for updates"
updates=$(keds/scripts/thim-update.sh check --porcelain)

if [ -z "$updates" ]; then
	echo "Everything up to date."
	exit 0
fi
echo "$updates"

# Label used by the PRs; --force makes this idempotent.
gh label create thim-update --color 5319e7 \
	--description "Automated ThimPress premium package update" --force >/dev/null 2>&1 || true

failures=()
while read -r slug local_ver remote_ver; do
	[ -n "$slug" ] || continue

	if [ "${#REQUESTED[@]}" -gt 0 ]; then
		wanted=false
		for r in "${REQUESTED[@]}"; do [ "$r" = "$slug" ] && wanted=true; done
		$wanted || { echo "==> $slug: not in requested set, skipping"; continue; }
	fi

	branch="thim/$slug"
	marker="<!-- thim: ${slug}@${remote_ver} -->"

	pr_json=$(gh pr list --head "$branch" --base "$BASE_BRANCH" --state open --json number,body --jq '.[0] // empty')
	if [ -n "$pr_json" ] && grep -qF "$marker" <<<"$pr_json"; then
		echo "==> $slug: open PR already proposes $remote_ver, skipping"
		continue
	fi

	echo "==> $slug: $local_ver -> $remote_ver"
	# </dev/null: commands inside (upsun ssh in particular) must not slurp
	# the porcelain lines this loop is reading from stdin.
	if ! (
		set -euo pipefail
		git checkout -B "$branch" "origin/$BASE_BRANCH"
		git reset --hard "origin/$BASE_BRANCH"
		git clean -fd keds/private-packages

		keds/scripts/thim-update.sh update "$slug"

		git add -A keds/private-packages keds/composer.json keds/composer.lock
		git commit -m "Update ${slug} ${local_ver} -> ${remote_ver} (ThimPress licensed channel)"
		git push --force origin "$branch"

		body=$(printf '%s\n\nAutomated update of `%s` from **%s** to **%s** via the ThimPress licensed channel (`scripts/thim-update.sh`).\n\nMerging deploys to production — review the CI results and the preview environment first. This PR is human-merged by design.\n' \
			"$marker" "$slug" "$local_ver" "$remote_ver")

		if [ -n "$pr_json" ]; then
			pr_number=$(python3 -c 'import json,sys; print(json.loads(sys.argv[1])["number"])' "$pr_json")
			gh pr edit "$pr_number" \
				--title "Update ${slug} ${local_ver} → ${remote_ver}" --body "$body"
		else
			gh pr create --head "$branch" --base "$BASE_BRANCH" \
				--title "Update ${slug} ${local_ver} → ${remote_ver}" \
				--label thim-update --body "$body"
		fi
	) </dev/null; then
		echo "ERROR: failed to raise PR for $slug, continuing with the rest" >&2
		failures+=("$slug")
	fi
done <<<"$updates"

git checkout --detach "origin/$BASE_BRANCH" >/dev/null 2>&1 || true

if [ "${#failures[@]}" -gt 0 ]; then
	echo "ERROR: failed packages: ${failures[*]}" >&2
	exit 1
fi
