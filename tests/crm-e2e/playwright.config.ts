import { defineConfig } from '@playwright/test';

// Mutating CRM-workflow suite. BASE_URL is a designated Upsun TEST
// environment — never point this at production. State setup/teardown and
// CRM assertions run over `upsun ssh` + wp-cli (see helpers/wp.ts), so
// UPSUN_PROJECT/UPSUN_ENV must identify the same environment as BASE_URL.
export default defineConfig({
  testDir: '.',
  // One long journey test; phases depend on each other.
  timeout: 15 * 60_000,
  expect: { timeout: 30_000 },
  workers: 1,
  // Retrying a half-completed enrollment journey would need another state
  // reset anyway; fail fast and keep the trace instead.
  retries: 0,
  reporter: [['list'], ['html', { open: 'never' }]],
  use: {
    baseURL: process.env.BASE_URL,
    trace: 'retain-on-failure',
    navigationTimeout: 60_000,
    actionTimeout: 30_000,
    userAgent: 'keds-ci-crm-e2e (+github-actions)',
    httpCredentials: process.env.E2E_HTTP_USER
      ? {
          username: process.env.E2E_HTTP_USER,
          password: process.env.E2E_HTTP_PASS ?? '',
        }
      : undefined,
  },
  projects: [{ name: 'chromium', use: { browserName: 'chromium' } }],
});
