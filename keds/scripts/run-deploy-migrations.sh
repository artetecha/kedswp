#!/usr/bin/env bash

set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
WP_DIR="${ROOT_DIR}/wordpress"
MIGRATIONS_DIR="${ROOT_DIR}/deploy-migrations"
STATE_PREFIX="keds_deploy_migration_"

if [ ! -d "${MIGRATIONS_DIR}" ]; then
	echo "No deploy migrations directory found at ${MIGRATIONS_DIR}; skipping."
	exit 0
fi

shopt -s nullglob
MIGRATIONS=("${MIGRATIONS_DIR}"/*.sh)
shopt -u nullglob

if [ "${#MIGRATIONS[@]}" -eq 0 ]; then
	echo "No deploy migrations found; skipping."
	exit 0
fi

for MIGRATION in "${MIGRATIONS[@]}"; do
	MIGRATION_ID="$(basename "${MIGRATION}" .sh)"

	if [[ ! "${MIGRATION_ID}" =~ ^[0-9]{8}_[0-9]{4}_[a-z0-9_]+$ ]]; then
		echo "Invalid deploy migration filename: ${MIGRATION_ID}. Expected YYYYMMDD_NNNN_short_name.sh."
		exit 1
	fi

	OPTION_NAME="${STATE_PREFIX}${MIGRATION_ID}"

	if wp --path="${WP_DIR}" option get "${OPTION_NAME}" >/dev/null 2>&1; then
		echo "Skipping deploy migration ${MIGRATION_ID}; already applied."
		continue
	fi

	echo "Applying deploy migration ${MIGRATION_ID}."
	(
		cd "${WP_DIR}"
		KEDS_ROOT="${ROOT_DIR}" KEDS_WP_PATH="${WP_DIR}" bash "${MIGRATION}"
	)

	APPLIED_AT="$(date -u +%Y-%m-%dT%H:%M:%SZ)"
	wp --path="${WP_DIR}" option add "${OPTION_NAME}" "${APPLIED_AT}" --autoload=no >/dev/null
	echo "Applied deploy migration ${MIGRATION_ID}."
done
