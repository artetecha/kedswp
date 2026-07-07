#!/usr/bin/env python3
"""Verify LearnPress learner progress from a dump survived on an environment.

Extracts every wp_learnpress_user_items row with recent activity from a
source SQL dump (the Pantheon backup that was imported) and compares each
one field-by-field against the live database of an Upsun environment, via
`upsun ssh` + wp-cli. Also checks the quiz/assignment grade rows
(user_item_results) attached to them, and prints the freshest completions
with student names so the result is human-checkable.

This is a cutover gate: module completions and grades are the content most
at risk in a content sync, and the one thing students must never lose.

Usage:
  scripts/lp-progress-check.py pantheon-backup.sql.gz pr-34
  scripts/lp-progress-check.py pantheon-backup.sql.gz main --since 2026-06-01

Exits non-zero if any progress row is missing or differs.
Requires: upsun CLI (authenticated), python3.
"""

import argparse
import datetime
import importlib.util
import os
import subprocess
import sys

_SPEC = importlib.util.spec_from_file_location(
    "dbcompare", os.path.join(os.path.dirname(os.path.abspath(__file__)), "db-compare.py")
)
dbcompare = importlib.util.module_from_spec(_SPEC)
_SPEC.loader.exec_module(dbcompare)

DEFAULT_PROJECT = "idpo3r4eqatcu"
COMPARE_FIELDS = [
    "user_item_id",
    "user_id",
    "item_id",
    "start_time",
    "end_time",
    "item_type",
    "status",
    "graduation",
]


def wp_query(project, environment, sql):
    cmd = [
        "upsun",
        "ssh",
        "-p",
        project,
        "-e",
        environment,
        "--no-interaction",
        f"cd wordpress && wp db query {shell_quote(sql)} --skip-column-names",
    ]
    result = subprocess.run(cmd, capture_output=True, text=True)
    if result.returncode != 0:
        raise SystemExit(f"wp db query failed on {environment}:\n{result.stderr.strip()}")
    return [
        line.split("\t")
        for line in result.stdout.replace("\r", "").split("\n")
        if line.strip()
    ]


def shell_quote(s):
    return "'" + s.replace("'", "'\\''") + "'"


def main():
    parser = argparse.ArgumentParser(description=__doc__.splitlines()[0])
    parser.add_argument("dump", help="source SQL dump (.sql or .sql.gz) that was imported")
    parser.add_argument("environment", help="Upsun environment to verify (e.g. pr-34, main)")
    parser.add_argument("--project", default=DEFAULT_PROJECT)
    parser.add_argument(
        "--since",
        default=None,
        help="verify activity since this date (YYYY-MM-DD; default: 30 days ago)",
    )
    args = parser.parse_args()

    since = args.since or (
        datetime.date.today() - datetime.timedelta(days=30)
    ).isoformat()

    columns, rows = dbcompare.extract_table(args.dump, "wp_learnpress_user_items")
    if not columns:
        raise SystemExit(f"No wp_learnpress_user_items table found in {args.dump}")
    idx = {c: i for i, c in enumerate(columns)}

    recent = [
        r
        for r in rows
        if max(r[idx["start_time"]] or "", r[idx["end_time"]] or "") >= since
    ]
    print(f"Source dump: {len(rows)} user items, {len(recent)} with activity since {since}.")
    if not recent:
        print("Nothing to verify (widen with --since).")
        return

    expected = {}
    for r in recent:
        expected[int(r[idx["user_item_id"]])] = [
            "NULL" if r[idx[f]] is None else str(r[idx[f]]) for f in COMPARE_FIELDS
        ]
    ids = ",".join(str(i) for i in sorted(expected))

    got = {
        int(row[0]): row
        for row in wp_query(
            args.project,
            args.environment,
            f"SELECT {', '.join(COMPARE_FIELDS)} FROM wp_learnpress_user_items "
            f"WHERE user_item_id IN ({ids})",
        )
    }

    missing = sorted(set(expected) - set(got))
    mismatched = [
        (uid, expected[uid], got[uid])
        for uid in sorted(set(expected) & set(got))
        if expected[uid] != got[uid]
    ]

    print(f"Environment {args.environment}: {len(got)}/{len(expected)} rows found.")
    for uid in missing:
        print(f"  MISSING user_item_id {uid}: {expected[uid]}")
    for uid, exp, act in mismatched:
        print(f"  MISMATCH user_item_id {uid}:")
        print(f"    dump: {exp}")
        print(f"    env:  {act}")

    grade_mismatch = False
    r_columns, r_rows = dbcompare.extract_table(
        args.dump, "wp_learnpress_user_item_results"
    )
    if r_columns:
        r_idx = {c: i for i, c in enumerate(r_columns)}
        expected_grades = sum(
            1 for r in r_rows if int(r[r_idx["user_item_id"]]) in expected
        )
        (count_row,) = wp_query(
            args.project,
            args.environment,
            f"SELECT COUNT(*) FROM wp_learnpress_user_item_results "
            f"WHERE user_item_id IN ({ids})",
        )
        actual_grades = int(count_row[0])
        grade_mismatch = actual_grades != expected_grades
        print(
            f"Grade rows for the recent items: dump {expected_grades}, "
            f"{args.environment} {actual_grades}"
            + ("  <-- MISMATCH" if grade_mismatch else "")
        )

    print("\nFreshest completions on the environment:")
    for row in wp_query(
        args.project,
        args.environment,
        "SELECT u.user_login, LEFT(p.post_title, 45), i.item_type, i.status, "
        "i.graduation, i.end_time "
        "FROM wp_learnpress_user_items i "
        "JOIN wp_users u ON u.ID = i.user_id "
        "JOIN wp_posts p ON p.ID = i.item_id "
        f"WHERE i.user_item_id IN ({ids}) AND i.status IN ('completed', 'finished') "
        "ORDER BY i.end_time DESC LIMIT 8",
    ):
        print("  " + " | ".join(row))

    if missing or mismatched or grade_mismatch:
        print(
            f"\nFAIL: {len(missing)} missing, {len(mismatched)} mismatched"
            + (", grade-row count differs" if grade_mismatch else "")
            + "."
        )
        sys.exit(1)
    print(f"\nPASS: all {len(expected)} recent progress rows intact on {args.environment}.")


if __name__ == "__main__":
    main()
