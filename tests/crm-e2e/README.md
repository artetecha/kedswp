# CRM workflow E2E

Programmatic version of the manual CRM test recorded in the "Testing CRM
Workflow Part 1–3" screen recordings (July 2026). Verifies, against a real
environment with the real FluentCRM automation config:

1. **Enrollment** — buying the LearnPress course with a 100% coupon (£0
   WooCommerce order) attaches the `Enrolled KYB 3` tag to the FluentCRM
   contact (FluentCampaign attaches it synchronously on
   `learnpress/user/course-enrolled`).
2. **Completion** — completing all course items, passing the quiz (answer
   key read from the DB, the programmatic version of "look it up under
   LearnPress → Quizzes"), and finishing the course attaches
   `Completed KYB 3` and removes `Enrolled KYB 3` (the removal is a funnel
   action — "KYB 3 - Completion and Advancement" — that runs via FluentCRM's
   scheduler, hence the cron-nudging poll in `helpers/wp.ts`).

The final assertion is exact: the contact's tag set must equal the baseline
plus `Completed KYB 3`, nothing else disturbed.

## ⚠️ This suite MUTATES the target environment

It cancels previous test orders, wipes the test user's enrollment/progress
for the course, detaches the two tags, deletes the contact's
funnel-subscriber rows for the tag-triggered funnels (so once-per-contact
automations re-enter on the next run), and re-creates the `crm-e2e-100`
coupon (100%, restricted to the course product). It then places a real £0
order and completes the course as the test user.

- It is **not** part of the read-only per-PR suite in `tests/e2e/` and must
  never be pointed at production.
- It currently targets the pre-cutover test environment (`main`).
  **After the Pantheon cutover, `main` IS production** — repoint the
  workflow's `TARGET_ENV` default to a dedicated test environment first.
- Inherent side effect (same as the manual procedure): tag-triggered funnels
  may send real emails to the test contact
  (`communications@kingsdivinity.org`).

## How it runs

`.github/workflows/crm-workflow-e2e.yml` — weekly (Mon 05:10 UTC) +
`workflow_dispatch` (environment and course overridable). Needs the
`UPSUN_CLI_TOKEN` secret (SSH + URL resolution) and `E2E_HTTP_USER`/
`E2E_HTTP_PASS` (the env sits behind HTTP basic auth).

State setup, the quiz answer key, login (a minted `logged_in` cookie — no
password is stored or reset anywhere), and tag assertions all run over
`upsun ssh` + `wp eval-file` (scripts in `php/`, always with
`--skip-plugins=learnpress-course-review`, which fatals under wp-cli).
The browser journey itself (cart → coupon → checkout → course player →
quiz → finish) mirrors the recordings through the storefront UI.

## Running locally

```sh
cd tests/crm-e2e
npm ci
BASE_URL=https://main-bvxea6i-idpo3r4eqatcu.uk-1.platformsh.site \
UPSUN_ENV=main \
E2E_HTTP_USER=… E2E_HTTP_PASS=… \
npx playwright test
```

Requires an authenticated `upsun` CLI. Defaults live at the top of
`crm-workflow.spec.ts` (contact email, course 23405 "KYB3 What is Divine
Inspiration?", tag names, coupon code) and are all env-overridable — the
workflow exposes `course_id` as a dispatch input, but note the tag names
must be overridden to match if you point it at a different course.
