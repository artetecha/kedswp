#!/usr/bin/env bash
#
# Import a staged database dump during deploy (Pantheon -> Upsun content sync).
#
# Called from deploy.sh BEFORE `wp core update-db` and the deploy-migration
# runner: because a Pantheon dump carries no keds_deploy_migration_* options,
# every deploy migration re-runs against the freshly imported data later in
# the same deployment — no manual post-import steps.
#
# Looks for exactly one *.sql or *.sql.gz staged in the db-import mount.
# If none is staged this script is a no-op, so it is safe on every deploy.
# On success the dump is renamed *.imported-<timestamp> so it runs exactly
# once; a pre-import safety dump is written to db-import/backups/ first.
#
# To sync content from Pantheon:
#   cat pantheon-backup.sql.gz | upsun ssh -p idpo3r4eqatcu -e <env> 'cat > db-import/pantheon.sql.gz'
#   ...then trigger a real deploy (e.g. push a commit). NOTE: `upsun
#   environment:redeploy` does NOT run deploy hooks; on a dev environment
#   `upsun ssh -e <env> 'bash scripts/deploy.sh'` runs the same code path.
#
# Then verify with scripts/db-compare.py against a fresh `upsun db:dump`.

set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
WP_DIR="${ROOT_DIR}/wordpress"
IMPORT_DIR="${ROOT_DIR}/db-import"
BACKUP_DIR="${IMPORT_DIR}/backups"

if [ ! -d "${IMPORT_DIR}" ]; then
	echo "No db-import mount found at ${IMPORT_DIR}; skipping database import."
	exit 0
fi

shopt -s nullglob
DUMPS=("${IMPORT_DIR}"/*.sql "${IMPORT_DIR}"/*.sql.gz)
shopt -u nullglob

if [ "${#DUMPS[@]}" -eq 0 ]; then
	echo "No staged database dump in ${IMPORT_DIR}; skipping database import."
	exit 0
fi

if [ "${#DUMPS[@]}" -gt 1 ]; then
	echo "Refusing to import: ${#DUMPS[@]} dumps staged in ${IMPORT_DIR}; expected exactly one:" >&2
	printf '  %s\n' "${DUMPS[@]}" >&2
	exit 1
fi

DUMP="${DUMPS[0]}"
STAMP="$(date -u +%Y%m%dT%H%M%SZ)"

echo "=== Database import: $(basename "${DUMP}") ==="

# Safety net in addition to any external backup: keep a dump of the database
# as it was just before the import. backups/ is outside the *.sql glob above,
# so it can never be mistaken for a staged dump on a later deploy.
mkdir -p "${BACKUP_DIR}"
BACKUP="${BACKUP_DIR}/pre-import-${STAMP}.sql.gz"
echo "Writing pre-import backup to ${BACKUP}"
wp --path="${WP_DIR}" db export - | gzip > "${BACKUP}"

# Drop all prefixed tables first: the dump only drops tables it contains, so
# without this an Upsun-only table would silently survive the import.
echo "Dropping existing WordPress tables."
wp --path="${WP_DIR}" db clean --yes

echo "Importing $(basename "${DUMP}")."
case "${DUMP}" in
	*.gz) gunzip -c "${DUMP}" | wp --path="${WP_DIR}" db import - ;;
	*) wp --path="${WP_DIR}" db import "${DUMP}" ;;
esac

# The object cache still holds rows from the replaced database; flushing is
# not optional. Failure here must fail the deploy — a stale cache over a new
# database serves wrong data.
echo "Flushing the object cache."
wp --path="${WP_DIR}" cache flush

mv "${DUMP}" "${DUMP}.imported-${STAMP}"
echo "=== Database import complete; staged dump renamed to $(basename "${DUMP}").imported-${STAMP} ==="
echo "Deploy migrations will now re-run against the imported data."
