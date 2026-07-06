#!/usr/bin/env bash
#
# Compose the daily open-PR report (markdown on stdout) for the KEDS repo.
# Used by .github/workflows/daily-pr-report.yml; also runnable locally with
# an authenticated gh. Writes `count=<open PRs>` to $GITHUB_OUTPUT when set.
#
# Sections: ready to merge (with suggested order), auto-merge armed,
# failing checks, conflicted, checks in progress.

set -euo pipefail

REPO="${REPO:-artetecha/kedswp}"

prs=$(gh pr list --repo "$REPO" --state open \
	--json number,title,headRefName,labels,autoMergeRequest,mergeStateStatus,statusCheckRollup,isDraft)

count=$(jq 'length' <<<"$prs")
if [ -n "${GITHUB_OUTPUT:-}" ]; then
	echo "count=$count" >> "$GITHUB_OUTPUT"
fi
[ "$count" -gt 0 ] || { echo "No open PRs."; exit 0; }

jq -r --arg repo "$REPO" '
# Merge-order priority: thim-core before eduma before LP add-ons before the
# rest — the Thim ecosystem is happiest when the framework leads.
def prio:
	.headRefName as $h
	| if $h == "thim/thim-core" then 0
	elif $h == "thim/eduma" then 1
	elif ($h | startswith("thim/learnpress-")) then 2
	elif ($h | startswith("thim/")) then 3
	elif ($h | startswith("premium/")) then 3
	elif ($h | startswith("dependabot/")) then 4
	else 5 end;

def failing_checks:
	[ .statusCheckRollup[]?
	  | select((.conclusion? // .state? // "") | ascii_upcase | IN("FAILURE", "ERROR", "TIMED_OUT"))
	  | (.name? // .context? // "check") ];

def pr_line: "- #\(.number) \(.title)";

def bucket:
	if .isDraft then "draft"
	elif .mergeStateStatus == "DIRTY" then "conflicted"
	elif (failing_checks | length) > 0 then "failing"
	elif .autoMergeRequest != null then "automerge"
	elif .mergeStateStatus == "CLEAN" then "ready"
	else "pending" end;

sort_by(prio, .number) | group_by(bucket) | map({(.[0] | bucket): .}) | add as $b |

"# KEDS — open pull requests\n",

(if $b.ready then
	"## ✅ Ready to merge (all checks green) — suggested order\n",
	($b.ready | to_entries[] | "\(.key + 1). #\(.value.number) \(.value.title)"),

	# Copy-paste commands. Path packages are pinned "*" so thim/premium
	# PRs touch disjoint composer.lock regions and merge back-to-back.
	# Only dependabot composer PRs rewrite root pins (content-hash);
	# dependabot self-rebases them after other merges land.
	([$b.ready[] | select(.headRefName | startswith("dependabot/composer/"))]) as $coupled |
	([$b.ready[] | select(.headRefName | startswith("dependabot/composer/") | not)]) as $indep |
	"\n### Copy-paste merge commands\n\n```bash",
	(if ($indep | length) > 0 then
		"# Independent PRs — safe to run back-to-back:",
		($indep[] | "gh pr merge \(.number) --repo \($repo) --squash   # \(.title)")
	else empty end),
	(if ($coupled | length) > 0 then
		"",
		"# Dependabot composer PRs rewrite root pins — merge last; dependabot",
		"# rebases them automatically if the merges above conflicted them.",
		($coupled[0] | "gh pr merge \(.number) --repo \($repo) --squash   # \(.title)"),
		(if ($coupled | length) > 1 then
			($coupled[1:][] | "#   then: #\(.number) \(.title)")
		else empty end)
	else empty end),
	"```\n"
else "## ✅ Ready to merge\n\n_None._\n" end),

(if $b.automerge then
	"## 🤖 Auto-merge armed (will merge when green — no action needed)\n",
	($b.automerge[] | pr_line), ""
else empty end),

(if $b.failing then
	"## ❌ Failing checks (investigate before merging)\n",
	($b.failing[] | pr_line + "  — failing: " + (failing_checks | join(", "))), ""
else empty end),

(if $b.conflicted then
	"## ⚠️ Merge conflicts (stale update branches rebuild on the next thim-update run)\n",
	($b.conflicted[] | pr_line), ""
else empty end),

(if $b.pending then
	"## ⏳ Checks in progress\n",
	($b.pending[] | pr_line), ""
else empty end),

(if $b.draft then
	"## 📝 Drafts\n",
	($b.draft[] | pr_line), ""
else empty end),

"—\nhttps://github.com/\($repo)/pulls"
' <<<"$prs"
