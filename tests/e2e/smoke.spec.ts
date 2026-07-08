import { test, expect, Page } from '@playwright/test';

// Read-only smoke suite for KEDS (Eduma + LearnPress) against an Upsun PR
// preview environment holding a CLONE OF PRODUCTION DATA.
//
// Hard rule: GETs only. Never log in, never POST, never submit forms, never
// add to cart — Stripe/FluentCRM/etc. are live-configured on the clone.

const FATAL_MARKERS = /There has been a critical error|Fatal error|Uncaught (Error|Exception|TypeError)/i;

async function expectNoFatal(page: Page) {
  const body = await page.content();
  expect(body).not.toMatch(FATAL_MARKERS);
}

// Links that look like a course permalink (/courses/<slug>/), excluding the
// archive itself.
async function courseLinksOn(page: Page): Promise<string[]> {
  const hrefs = await page
    .locator('a[href*="/courses/"]')
    .evaluateAll((els) => els.map((el) => (el as HTMLAnchorElement).href));
  return hrefs.filter((href) => {
    try {
      const path = new URL(href).pathname.replace(/\/$/, '');
      return /^\/courses\/[^/]+$/.test(path);
    } catch {
      return false;
    }
  });
}

test.describe('KEDS smoke', () => {
  test('homepage renders', async ({ page }) => {
    const response = await page.goto('/');
    expect(response?.status()).toBe(200);
    await expectNoFatal(page);
    // Eduma always renders a header + footer; a blank white page (partial
    // fatal with output buffering) would fail this.
    await expect(page.locator('body')).not.toBeEmpty();
  });

  test('login page renders its form (no login attempted)', async ({ page }) => {
    const response = await page.goto('/wp-login.php');
    expect(response?.status()).toBe(200);
    await expect(page.locator('#loginform')).toBeVisible();
  });

  test('course archive lists real courses (prod-data assertion)', async ({ page }) => {
    const response = await page.goto('/courses/');
    expect(response?.status()).toBe(200);
    await expectNoFatal(page);

    // The archive of a site with production data must link at least one
    // course permalink.
    expect((await courseLinksOn(page)).length).toBeGreaterThan(0);
  });

  test('a course detail page renders', async ({ page }) => {
    await page.goto('/courses/');
    const links = await courseLinksOn(page);
    expect(links.length).toBeGreaterThan(0);
    const response = await page.goto(links[0]);
    expect(response?.status()).toBe(200);
    await expectNoFatal(page);
  });

  test('REST API responds', async ({ request }) => {
    const response = await request.get('/wp-json/');
    expect(response.status()).toBe(200);
    const json = await response.json();
    expect(json).toHaveProperty('name');
  });

  test('anonymous pages carry router cache headers (upsun mu-plugin)', async ({ request }) => {
    const response = await request.get('/');
    expect(response.status()).toBe(200);
    // PageCache module parity with the former keds-edge-cache mu-plugin.
    expect(response.headers()['cache-control'] ?? '').toContain('s-maxage=600');
  });

  test('no LearnPress guest-session cookie on anonymous pages', async ({ request }) => {
    const response = await request.get('/');
    const setCookies = response
      .headersArray()
      .filter((h) => h.name.toLowerCase() === 'set-cookie')
      .map((h) => h.value);
    expect(setCookies.join('\n')).not.toContain('lp_session_guest');
  });

  test('preview environment sends noindex (upsun mu-plugin)', async ({ request }) => {
    // PR preview environments are non-production, so PreviewProtection must
    // emit X-Robots-Tag. This would fail against production — this suite
    // only ever targets preview environments.
    const response = await request.get('/');
    expect(response.headers()['x-robots-tag'] ?? '').toContain('noindex');
  });

  // Stripe delivers live webhook events to these two URLs (PMPro and the
  // WooCommerce Stripe gateway); when either 500s, Stripe eventually disables
  // the endpoint and subscription renewals stop reaching the site.
  //
  // GETs only, like the rest of the suite — an unsigned POST to wc_stripe
  // would write webhook-state options and error logs on the clone. A GET is
  // still a strong probe on both:
  //   - PMPro runs its full webhook service on GET (admin-ajax dispatch,
  //     Stripe SDK load, API-key setup) before logging "No event ID given".
  //   - WooCommerce answers 200 only when a handler is hooked to
  //     woocommerce_api_wc_stripe, and 400 when the gateway is missing.
  test('PMPro Stripe webhook endpoint boots and answers', async ({ request }) => {
    const response = await request.get('/wp-admin/admin-ajax.php?action=stripe_webhook');
    expect(response.status()).toBe(200);
    const body = await response.text();
    // admin-ajax answers an *unregistered* action with a 400 and body "0";
    // this message only comes from PMPro's handler having actually run.
    expect(body).toContain('No event ID given');
    expect(body).not.toMatch(FATAL_MARKERS);
  });

  test('WooCommerce Stripe webhook handler is registered', async ({ request }) => {
    const response = await request.get('/?wc-api=wc_stripe');
    expect(response.status(), 'expected 200 from the wc-api dispatcher; 400 means no handler is hooked to woocommerce_api_wc_stripe').toBe(200);
  });

  test('sitemap responds', async ({ request }) => {
    // The SEO Framework (autodescription) replaces core's /wp-sitemap.xml
    // with its own /sitemap.xml — accept whichever answers.
    const core = await request.get('/wp-sitemap.xml');
    if (core.status() === 200) return;
    const tsf = await request.get('/sitemap.xml');
    expect(tsf.status(), 'neither /wp-sitemap.xml nor /sitemap.xml returned 200').toBe(200);
  });
});
