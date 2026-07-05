import { defineConfig } from '@playwright/test';

// BASE_URL is the Upsun PR environment (a clone of production data).
// Everything in this suite must stay strictly read-only.
export default defineConfig({
  testDir: '.',
  timeout: 120_000,
  expect: { timeout: 30_000 },
  retries: 1,
  reporter: [['list'], ['html', { open: 'never' }]],
  use: {
    baseURL: process.env.BASE_URL,
    trace: 'retain-on-failure',
    // First hits are cold (route cache empty on a fresh environment).
    navigationTimeout: 60_000,
    actionTimeout: 30_000,
    userAgent: 'keds-ci-smoke (+github-actions)',
    // Upsun preview environments are currently open; if HTTP auth is ever
    // enabled on them, set E2E_HTTP_USER / E2E_HTTP_PASS secrets.
    httpCredentials: process.env.E2E_HTTP_USER
      ? {
          username: process.env.E2E_HTTP_USER,
          password: process.env.E2E_HTTP_PASS ?? '',
        }
      : undefined,
  },
  projects: [{ name: 'chromium', use: { browserName: 'chromium' } }],
});
