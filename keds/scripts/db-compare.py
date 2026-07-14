#!/usr/bin/env python3
"""Compare two WordPress SQL dumps without loading them into a database.

Streams two mysqldump/mariadb-dump files (plain or gzipped) and reports:
table inventory differences, per-table row-count differences, content
markers (latest post edit, user/comment/order activity, LearnPress
progress), post counts by type, key wp_options values, and recorded
keds_deploy_migration_* state.

Built for the Pantheon -> Upsun migration: run it against a fresh Pantheon
backup and an `upsun db:dump` to see how far the Upsun copy has drifted
from live content, and to sanity-check a content sync before/after import.

Usage:
  scripts/db-compare.py pantheon.sql.gz upsun.sql.gz
  scripts/db-compare.py --labels pantheon,upsun a.sql.gz b.sql.gz

Handles both extended single-line INSERTs (mysqldump, MariaDB <= 10.x)
and one-row-per-line INSERTs (mariadb-dump 11.x).
"""

import argparse
import gzip
import json
import re
import sys

PREFIX = "wp_"

STRING_RE = re.compile(r"'(?:[^'\\]|\\.|'')*'")
INSERT_RE = re.compile(r"^INSERT INTO `([^`]+)` VALUES\s?")
CREATE_RE = re.compile(r"^CREATE TABLE `([^`]+)`")
COLUMN_RE = re.compile(r"^\s+`([^`]+)`")
NON_COLUMN_LINE = ("PRIMARY", "KEY", "UNIQUE", "CONSTRAINT", "FULLTEXT", "SPATIAL")

DETAIL_TABLES = {
    PREFIX + t
    for t in (
        "posts",
        "users",
        "comments",
        "options",
        "learnpress_user_items",
        "pmpro_memberships_users",
    )
}

WANTED_OPTIONS = {
    "siteurl",
    "home",
    "blogname",
    "template",
    "stylesheet",
    "db_version",
    "active_plugins",
    "WPLANG",
    "blog_public",
}

MIGRATION_OPTION_PREFIX = "upsun_migration_"

# Options expected to differ constantly without carrying configuration:
# transients, locks, run timestamps, rotating counters. Excluded from the
# --options report so real configuration drift stands out.
VOLATILE_OPTION_RE = re.compile(
    r"^(_transient_|_site_transient_|cron$|action_scheduler_lock_|"
    r"wc_admin_dismissed|woocommerce_marketplace|.*_cache_validator$|"
    r"fluentcrm_.*_lock|_fluentcrm|_fc_|fluentform_scheduled|"
    r"wpmailsmtp_debug|.*_last_checked$|.*_last_run$|.*_last_send$|"
    r"finished_updating|db_upgraded$|recently_activated$|uninstall_plugins$|"
    r"auto_updater|adjacent_index)"
)


def open_dump(path):
    if path.endswith(".gz"):
        return gzip.open(path, "rt", encoding="utf-8", errors="replace")
    return open(path, "rt", encoding="utf-8", errors="replace")


def parse_insert_rows(payload):
    """Yield rows (lists of raw field strings) from an INSERT VALUES payload."""
    i, n = 0, len(payload)
    depth = 0
    field_start = None
    row = []
    while i < n:
        c = payload[i]
        if c == "'":
            m = STRING_RE.match(payload, i)
            i = m.end() if m else i + 1
            continue
        if c == "(":
            depth += 1
            if depth == 1:
                row = []
                field_start = i + 1
        elif c == ")":
            if depth == 1:
                row.append(payload[field_start:i])
                yield row
            depth -= 1
        elif c == "," and depth == 1:
            row.append(payload[field_start:i])
            field_start = i + 1
        i += 1


def unquote(value):
    value = value.strip()
    if value == "NULL":
        return None
    if value.startswith("'") and value.endswith("'"):
        body = value[1:-1]
        return (
            body.replace("\\'", "'")
            .replace('\\"', '"')
            .replace("\\n", "\n")
            .replace("\\r", "\r")
            .replace("\\\\", "\\")
        )
    return value


def extract_table(path, table):
    """Return (columns, rows) for one table in a dump; fields are unquoted."""
    columns = []
    rows = []
    current_create = None
    insert_active = False
    insert_buf = []

    def finish(payload):
        for row in parse_insert_rows(payload):
            rows.append([unquote(field) for field in row])

    with open_dump(path) as fh:
        for line in fh:
            if insert_active:
                stripped = line.rstrip()
                insert_buf.append(stripped)
                if stripped.endswith(";"):
                    finish("\n".join(insert_buf).rstrip(";"))
                    insert_active = False
                    insert_buf = []
                continue
            if current_create:
                if line.startswith(")"):
                    current_create = None
                else:
                    m = COLUMN_RE.match(line)
                    if m and not line.lstrip().startswith(NON_COLUMN_LINE):
                        columns.append(m.group(1))
                continue
            m = CREATE_RE.match(line)
            if m:
                if m.group(1) == table:
                    current_create = True
                    columns = []
                continue
            m = INSERT_RE.match(line)
            if m and m.group(1) == table:
                rest = line[m.end():].rstrip()
                if rest.endswith(";"):
                    finish(rest.rstrip(";"))
                else:
                    insert_active = True
                    insert_buf = [rest] if rest else []

    return columns, rows


def analyze(path):
    tables = {}  # table name -> row count
    columns = {}  # table name -> ordered column names
    detail = {
        "posts_by_type": {},
        "posts_max_modified_gmt": "",
        "posts_max_id": 0,
        "users_count": 0,
        "users_max_id": 0,
        "users_max_registered": "",
        "comments_count": 0,
        "comments_max_date_gmt": "",
        "options": {},
        "migration_options": [],
        "lp_user_items_count": 0,
        "lp_user_items_max_start": "",
        "pmpro_members_count": 0,
    }

    def handle_row(table, row, idx):
        if table == PREFIX + "posts":
            post_type = unquote(row[idx["post_type"]]) if "post_type" in idx else "?"
            detail["posts_by_type"][post_type] = detail["posts_by_type"].get(post_type, 0) + 1
            modified = unquote(row[idx["post_modified_gmt"]]) or ""
            if modified > detail["posts_max_modified_gmt"]:
                detail["posts_max_modified_gmt"] = modified
            try:
                detail["posts_max_id"] = max(detail["posts_max_id"], int(row[idx["ID"]]))
            except ValueError:
                pass
        elif table == PREFIX + "users":
            detail["users_count"] += 1
            registered = unquote(row[idx["user_registered"]]) or ""
            if registered > detail["users_max_registered"]:
                detail["users_max_registered"] = registered
            try:
                detail["users_max_id"] = max(detail["users_max_id"], int(row[idx["ID"]]))
            except ValueError:
                pass
        elif table == PREFIX + "comments":
            detail["comments_count"] += 1
            date = unquote(row[idx["comment_date_gmt"]]) or ""
            if date > detail["comments_max_date_gmt"]:
                detail["comments_max_date_gmt"] = date
        elif table == PREFIX + "options":
            name = unquote(row[idx["option_name"]])
            if name in WANTED_OPTIONS:
                detail["options"][name] = (unquote(row[idx["option_value"]]) or "")[:300]
            elif name and name.startswith(MIGRATION_OPTION_PREFIX):
                detail["migration_options"].append(name)
        elif table == PREFIX + "learnpress_user_items":
            detail["lp_user_items_count"] += 1
            if "start_time" in idx:
                start = unquote(row[idx["start_time"]]) or ""
                if start > detail["lp_user_items_max_start"]:
                    detail["lp_user_items_max_start"] = start
        elif table == PREFIX + "pmpro_memberships_users":
            detail["pmpro_members_count"] += 1

    def finish_insert(table, payload):
        tables.setdefault(table, 0)
        idx = {c: k for k, c in enumerate(columns.get(table, []))}
        want_detail = table in DETAIL_TABLES
        for row in parse_insert_rows(payload):
            tables[table] += 1
            if want_detail:
                handle_row(table, row, idx)

    current_create = None
    insert_table = None
    insert_buf = []

    with open_dump(path) as fh:
        for line in fh:
            if insert_table is not None:
                stripped = line.rstrip()
                insert_buf.append(stripped)
                if stripped.endswith(";"):
                    finish_insert(insert_table, "\n".join(insert_buf).rstrip(";"))
                    insert_table = None
                    insert_buf = []
                continue
            if current_create is not None:
                if line.startswith(")"):
                    current_create = None
                else:
                    m = COLUMN_RE.match(line)
                    if m and not line.lstrip().startswith(NON_COLUMN_LINE):
                        columns[current_create].append(m.group(1))
                continue
            m = CREATE_RE.match(line)
            if m:
                current_create = m.group(1)
                tables.setdefault(current_create, 0)
                columns[current_create] = []
                continue
            m = INSERT_RE.match(line)
            if m:
                rest = line[m.end():].rstrip()
                if rest.endswith(";"):
                    finish_insert(m.group(1), rest.rstrip(";"))
                else:
                    insert_table = m.group(1)
                    insert_buf = [rest] if rest else []

    if insert_table is not None:
        raise SystemExit(f"{path}: dump ends mid-INSERT into `{insert_table}` — truncated file?")
    return {"tables": tables, "detail": detail}


def print_comparison(a, b, label_a, label_b):
    tables_a, tables_b = a["tables"], b["tables"]
    set_a, set_b = set(tables_a), set(tables_b)
    width = max(len(label_a), len(label_b))

    print(f"== Tables only in {label_a} ==")
    for t in sorted(set_a - set_b):
        print(f"  {t} ({tables_a[t]} rows)")
    if not set_a - set_b:
        print("  (none)")
    print(f"== Tables only in {label_b} ==")
    for t in sorted(set_b - set_a):
        print(f"  {t} ({tables_b[t]} rows)")
    if not set_b - set_a:
        print("  (none)")

    print("\n== Row-count differences (common tables) ==")
    print(f"{'table':45} {label_a:>10} {label_b:>10} {'diff':>8}")
    identical = 0
    for t in sorted(set_a & set_b):
        ra, rb = tables_a[t], tables_b[t]
        if ra != rb:
            print(f"{t:45} {ra:>10} {rb:>10} {rb - ra:>+8}")
        else:
            identical += 1
    print(f"...and {identical} common tables with identical row counts")

    da, db = a["detail"], b["detail"]
    print("\n== Content markers ==")
    markers = [
        ("posts: latest post_modified_gmt", "posts_max_modified_gmt"),
        ("posts: max ID", "posts_max_id"),
        ("users: count", "users_count"),
        ("users: max ID", "users_max_id"),
        ("users: latest user_registered", "users_max_registered"),
        ("comments: count", "comments_count"),
        ("comments: latest comment_date_gmt", "comments_max_date_gmt"),
        ("LearnPress user items: count", "lp_user_items_count"),
        ("LearnPress user items: latest start", "lp_user_items_max_start"),
        ("PMPro membership rows", "pmpro_members_count"),
    ]
    for title, key in markers:
        va, vb = da[key], db[key]
        mark = "SAME" if va == vb else "DIFF"
        print(f"  [{mark}] {title}")
        if va != vb:
            print(f"      {label_a:>{width}}: {va}")
            print(f"      {label_b:>{width}}: {vb}")

    print("\n== Post-count differences by post_type ==")
    diffs = False
    for t in sorted(set(da["posts_by_type"]) | set(db["posts_by_type"])):
        ca, cb = da["posts_by_type"].get(t, 0), db["posts_by_type"].get(t, 0)
        if ca != cb:
            diffs = True
            print(f"  {t:35} {label_a}:{ca:>6}  {label_b}:{cb:>6}  {cb - ca:>+6}")
    if not diffs:
        print("  (none)")

    print("\n== Key options ==")
    for k in sorted(set(da["options"]) | set(db["options"])):
        va = da["options"].get(k, "<absent>")
        vb = db["options"].get(k, "<absent>")
        mark = "SAME" if va == vb else "DIFF"
        print(f"  [{mark}] {k}")
        if va != vb:
            print(f"      {label_a:>{width}}: {va[:140]}")
            print(f"      {label_b:>{width}}: {vb[:140]}")

    print(f"\n== {MIGRATION_OPTION_PREFIX}* options ==")
    print(f"  {label_a:>{width}}: {', '.join(sorted(da['migration_options'])) or '(none — all deploy migrations will run on import)'}")
    print(f"  {label_b:>{width}}: {', '.join(sorted(db['migration_options'])) or '(none — all deploy migrations will run on import)'}")


def print_options_diff(a_options, b_options, label_a, label_b):
    ka = {k for k in a_options if not VOLATILE_OPTION_RE.match(k)}
    kb = {k for k in b_options if not VOLATILE_OPTION_RE.match(k)}
    width = max(len(label_a), len(label_b))

    def preview(v):
        return (v or "")[:90].replace("\n", " ").replace("\r", " ")

    only_a = sorted(ka - kb)
    print(f"== Options only in {label_a} ({len(only_a)}) ==")
    for k in only_a:
        print(f"  {k}  = {preview(a_options[k])}")

    only_b = sorted(kb - ka)
    print(f"\n== Options only in {label_b} ({len(only_b)}) ==")
    for k in only_b:
        print(f"  {k}  = {preview(b_options[k])}")

    diff = sorted(k for k in ka & kb if a_options[k] != b_options[k])
    print(f"\n== Options with different values ({len(diff)}) ==")
    for k in diff:
        print(f"  {k}")
        print(f"      {label_a:>{width}}: {preview(a_options[k])}")
        print(f"      {label_b:>{width}}: {preview(b_options[k])}")


def load_options(path):
    columns, rows = extract_table(path, PREFIX + "options")
    idx = {c: k for k, c in enumerate(columns)}
    return {r[idx["option_name"]]: r[idx["option_value"]] for r in rows}


def main():
    parser = argparse.ArgumentParser(description=__doc__.splitlines()[0])
    parser.add_argument("dump_a", help="first SQL dump (.sql or .sql.gz)")
    parser.add_argument("dump_b", help="second SQL dump (.sql or .sql.gz)")
    parser.add_argument(
        "--labels",
        default=None,
        help="comma-separated labels for the two dumps (default: file basenames)",
    )
    parser.add_argument(
        "--json",
        action="store_true",
        help="emit the raw per-dump analyses as JSON instead of the report",
    )
    parser.add_argument(
        "--options",
        action="store_true",
        help="report the full wp_options diff (volatile options filtered out) "
        "instead of the standard comparison",
    )
    args = parser.parse_args()

    if args.labels:
        parts = args.labels.split(",")
        if len(parts) != 2:
            parser.error("--labels needs exactly two comma-separated values")
        label_a, label_b = (p.strip() for p in parts)
    else:
        label_a = args.dump_a.rsplit("/", 1)[-1]
        label_b = args.dump_b.rsplit("/", 1)[-1]

    if args.options:
        print_options_diff(
            load_options(args.dump_a), load_options(args.dump_b), label_a, label_b
        )
        return

    a = analyze(args.dump_a)
    b = analyze(args.dump_b)

    if args.json:
        json.dump({label_a: a, label_b: b}, sys.stdout, indent=1)
        print()
    else:
        print_comparison(a, b, label_a, label_b)


if __name__ == "__main__":
    main()
