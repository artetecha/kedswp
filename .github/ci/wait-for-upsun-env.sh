#!/usr/bin/env bash
#
# Wait for the Upsun GitHub integration to finish deploying this PR's
# environment, then emit its URL as the GitHub Actions output "url".
#
# Expects: GITHUB_REPOSITORY, HEAD_SHA, GH_TOKEN (statuses:read), GITHUB_OUTPUT.
# Optional:
#   UPSUN_STATUS_CONTEXT  exact commit-status context to watch (defaults to a
#                         case-insensitive upsun|platform match — pin the real
#                         value after the first PR reveals it)
#   UPSUN_CLI_TOKEN       enables the CLI fallback for URL discovery when the
#                         status target_url is not the environment URL
#   PR_NUMBER             required for the CLI fallback (env name is pr-N)
#   TIMEOUT_MIN           default 30

set -euo pipefail

TIMEOUT_MIN="${TIMEOUT_MIN:-30}"
export CONTEXT_RE="${UPSUN_STATUS_CONTEXT:-upsun|platform}"
deadline=$(( $(date +%s) + TIMEOUT_MIN * 60 ))

state="" target=""
while :; do
	# The combined-status endpoint caps at 100 statuses; fine at this scale.
	# Regex comes in via env.CONTEXT_RE, not string interpolation.
	read -r state target < <(
		gh api "repos/${GITHUB_REPOSITORY}/commits/${HEAD_SHA}/status" \
			--jq '[.statuses[] | select(.context | test(env.CONTEXT_RE; "i"))] | first // {} | "\(.state // "absent") \(.target_url // "")"'
	) || true

	echo "Upsun status: ${state:-unknown}"
	case "${state:-}" in
		success) break ;;
		failure|error)
			echo "ERROR: Upsun deployment reported ${state} for ${HEAD_SHA}" >&2
			exit 1
			;;
	esac

	if [ "$(date +%s)" -ge "$deadline" ]; then
		echo "ERROR: timed out after ${TIMEOUT_MIN}m waiting for the Upsun commit status" >&2
		echo "       (context regex: ${CONTEXT_RE}; statuses seen:)" >&2
		gh api "repos/${GITHUB_REPOSITORY}/commits/${HEAD_SHA}/status" --jq '.statuses[].context' >&2 || true
		exit 1
	fi
	sleep 30
done

url=""
if [[ "$target" == *platformsh.site* || "$target" == *upsunapp.com* ]]; then
	url="$target"
elif [ -n "${UPSUN_CLI_TOKEN:-}" ] && command -v upsun >/dev/null; then
	url=$(upsun environment:url -p "${UPSUN_PROJECT:-idpo3r4eqatcu}" -e "pr-${PR_NUMBER}" --pipe --no-interaction | grep '^https://' | head -1)
else
	echo "ERROR: Upsun status succeeded but target_url is not the environment URL: '${target}'" >&2
	echo "       Set UPSUN_STATUS_CONTEXT/UPSUN_CLI_TOKEN or adjust this script (see plan notes)." >&2
	exit 1
fi

[ -n "$url" ] || { echo "ERROR: could not determine environment URL" >&2; exit 1; }
url="${url%/}"
echo "Environment URL: $url"
echo "url=$url" >> "$GITHUB_OUTPUT"
