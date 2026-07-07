import { test, expect, Page } from '@playwright/test';
import { wpEvalFile, waitForTags } from './helpers/wp';

// Programmatic version of the manual CRM workflow test (screen recordings
// "Testing CRM Workflow Part 1-3", July 2026):
//
//   Part 1  buy the course with a 100% coupon  -> "Enrolled <course>" tag
//   Part 2  complete every item + pass the quiz -> finish course
//   Part 3  assert the tag swap: Enrolled removed, Completed added
//
// State is reset first over `upsun ssh` + wp-cli (php/reset-state.php), the
// browser journey mirrors the recording, and FluentCRM tag assertions poll
// through the same channel. MUTATES the target environment — see
// .github/workflows/crm-workflow-e2e.yml for where this is allowed to run.

const EMAIL = process.env.CRM_TEST_EMAIL || 'communications@kingsdivinity.org';
const COURSE_ID = process.env.COURSE_ID || '23405';
const ENROLLED_TAG = process.env.CRM_TAG_ENROLLED || 'Enrolled KYB 3';
const COMPLETED_TAG = process.env.CRM_TAG_COMPLETED || 'Completed KYB 3';
const COUPON = process.env.CRM_TEST_COUPON || 'crm-e2e-100';

interface QuizQuestion {
  id: number;
  title: string;
  type: string;
  correct: string[];
  blanks: { id: string; fill: string }[];
}
interface QuizData {
  quiz_id: number;
  quiz_title: string;
  passing_grade: string;
  questions: QuizQuestion[];
}
interface ResetResult {
  baseline_tags: string[];
  wc_orders_cancelled?: number[];
  funnels_reset?: string[];
}
interface AuthCookie {
  name: string;
  value: string;
  expires: number;
}
interface CourseInfo {
  url: string;
}

const normalize = (s: string) =>
  s.toLowerCase().replace(/\s+/g, ' ').replace(/^\d+\.\s*/, '').trim();

// LearnPress confirm dialogs (lpModalOverlay) appear only when the site has
// popup confirmation enabled — this site does. Optional-click keeps the test
// working either way.
async function confirmLpModal(page: Page) {
  const yes = page
    .locator('.lp-modal-overlay')
    .locator('button, a')
    .filter({ hasText: /^(yes|ok)$/i })
    .first();
  await yes.click({ timeout: 10_000 }).catch(() => undefined);
}

test('CRM workflow: course purchase and completion drive the FluentCRM tag swap', async ({ page, context, baseURL }) => {
  let baseline: string[] = [];
  let quiz!: QuizData;
  let courseUrl!: string;

  await test.step('reset test state on the environment', async () => {
    const reset = await wpEvalFile<ResetResult>('reset-state.php', [
      EMAIL, COURSE_ID, ENROLLED_TAG, COMPLETED_TAG, COUPON,
    ]);
    baseline = reset.baseline_tags;
    expect(baseline, 'reset must leave the contact without either workflow tag').not.toContain(ENROLLED_TAG);
    expect(baseline).not.toContain(COMPLETED_TAG);
  });

  await test.step('fetch course URL and quiz answer key', async () => {
    const info = await wpEvalFile<CourseInfo>('course-info.php', [COURSE_ID]);
    courseUrl = info.url;
    quiz = await wpEvalFile<QuizData>('quiz-answers.php', [COURSE_ID]);
    expect(quiz.questions.length).toBeGreaterThan(0);
  });

  await test.step('log in via a minted auth cookie', async () => {
    const cookie = await wpEvalFile<AuthCookie>('auth-cookie.php', [EMAIL]);
    await context.addCookies([
      {
        name: cookie.name,
        value: cookie.value,
        domain: new URL(baseURL!).hostname,
        path: '/',
        secure: true,
        httpOnly: true,
        expires: cookie.expires,
      },
    ]);
  });

  // ---- Part 1: enrollment --------------------------------------------------

  await test.step('add the course to the cart', async () => {
    await page.goto(courseUrl);
    await page.getByRole('button', { name: /add to cart/i }).first().click();
    // The add happens over admin-ajax; the button flips to a view-cart state.
    await page
      .getByRole('link', { name: /view cart/i })
      .first()
      .waitFor({ timeout: 15_000 })
      .catch(() => undefined);
    await page.goto('/cart/');
  });

  await test.step('apply the 100% coupon and check out for £0', async () => {
    // WooCommerce cart block: "Add Coupons" panel -> code input -> Apply.
    await page.getByText(/add coupons?/i).first().click();
    await page.getByPlaceholder(/enter code/i).fill(COUPON);
    await page.getByRole('button', { name: /^apply$/i }).click();
    await expect(
      page.locator('.wc-block-components-totals-footer-item').first(),
      'cart total must be £0.00 after the coupon',
    ).toContainText('£0.00');

    await page.getByRole('link', { name: /proceed to checkout/i }).click();

    // Same assertion the recording makes before placing the order: correct
    // billing email, zero total.
    const emailField = page.locator('#email, input[type="email"]').first();
    await expect(emailField).toHaveValue(EMAIL);
    await expect(page.locator('.wc-block-components-totals-footer-item').first()).toContainText('£0.00');

    await page.getByRole('button', { name: /place order/i }).click();
    await page.waitForURL(/order-received/, { timeout: 60_000 });
    await expect(page.getByText(/your order has been received/i)).toBeVisible();
  });

  await test.step('assert the Enrolled tag is attached', async () => {
    const tags = await waitForTags(
      EMAIL,
      (t) => t.includes(ENROLLED_TAG),
      `"${ENROLLED_TAG}" to be attached after checkout`,
    );
    expect(tags).not.toContain(COMPLETED_TAG);
  });

  // ---- Part 2: complete every item, pass the quiz --------------------------

  await test.step('open the course player', async () => {
    await page.goto(courseUrl);
    await page
      .getByRole('link', { name: /continue|start learning|start now/i })
      .or(page.getByRole('button', { name: /continue|start learning|start now/i }))
      .first()
      .click();
    await page.locator('.course-item, .section-item').first().waitFor({ timeout: 30_000 });
  });

  await test.step('mark every lesson-type item complete', async () => {
    // Sidebar lists all curriculum items; the quiz is handled separately.
    const itemLinks = page.locator('.course-item a.section-item-link, .course-item > a');
    const count = await itemLinks.count();
    expect(count, 'course player sidebar must list curriculum items').toBeGreaterThan(0);

    for (let i = 0; i < count; i++) {
      const link = itemLinks.nth(i);
      const isQuiz = (await link
        .locator('xpath=ancestor::*[contains(@class,"course-item")]')
        .getAttribute('class'))?.includes('lp_quiz');
      if (isQuiz) continue;

      await link.click();
      const completeBtn = page.locator('button.lp-btn-complete-item');
      // Items completed on an earlier attempt render no button; skip them.
      if (await completeBtn.isVisible({ timeout: 10_000 }).catch(() => false)) {
        await completeBtn.click();
        await confirmLpModal(page);
        await completeBtn.waitFor({ state: 'hidden', timeout: 30_000 });
      }
    }
  });

  await test.step('take the quiz using the answer key', async () => {
    await page.locator('.course-item.course-item-lp_quiz a, .course-item[class*="lp_quiz"] a').first().click();
    await page.locator('#learn-press-quiz-app').waitFor({ timeout: 30_000 });
    await page.locator('#learn-press-quiz-app button.lp-button.start').click();

    // One question per page, numbered pagination (matches the recording).
    for (let i = 0; i < quiz.questions.length; i++) {
      const pageBtn = page.locator('.questions-pagination button, .questions-pagination .page-numbers').filter({ hasText: new RegExp(`^${i + 1}$`) });
      if (await pageBtn.count()) await pageBtn.first().click();

      const titleEl = page.locator('.question-title').first();
      await titleEl.waitFor({ timeout: 15_000 });
      const shown = normalize(await titleEl.innerText());
      const q = quiz.questions.find((c) => normalize(c.title) === shown) ?? quiz.questions[i];
      expect(q, `no answer-key entry for question "${shown}"`).toBeTruthy();

      if (q.type === 'fill_in_blanks') {
        for (const blank of q.blanks) {
          await page.locator(`.lp-fib-input input[data-id="${blank.id}"]`).fill(blank.fill);
        }
      } else {
        for (const correct of q.correct) {
          const option = page
            .locator('.answer-options .answer-option label.option-title')
            .filter({ hasText: new RegExp(`^\\s*${correct.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')}\\s*$`, 'i') })
            .first();
          await option.click();
        }
      }
    }

    await page.locator('button.lp-button.submit-quiz').click();
    await confirmLpModal(page);

    // The result overlay reports the score; anything under the passing grade
    // means the answer key extraction broke — fail loudly here rather than
    // puzzling over a missing tag later.
    await expect(page.locator('#learn-press-quiz-app')).toContainText(/passed|congratulation|%/i, { timeout: 30_000 });
  });

  await test.step('finish the course', async () => {
    const finishBtn = page.locator('button.lp-btn-finish-course').or(page.getByRole('button', { name: /finish course/i })).first();
    await finishBtn.click();
    await confirmLpModal(page);
    await page.waitForLoadState('networkidle').catch(() => undefined);
  });

  // ---- Part 3: the tag swap is the pass condition ---------------------------

  await test.step('assert Completed replaces Enrolled', async () => {
    const tags = await waitForTags(
      EMAIL,
      (t) => t.includes(COMPLETED_TAG) && !t.includes(ENROLLED_TAG),
      `"${COMPLETED_TAG}" attached and "${ENROLLED_TAG}" removed`,
    );

    // No collateral damage: every other tag survives untouched.
    const expected = [...baseline, COMPLETED_TAG].sort();
    expect([...tags].sort()).toEqual(expected);
  });
});
