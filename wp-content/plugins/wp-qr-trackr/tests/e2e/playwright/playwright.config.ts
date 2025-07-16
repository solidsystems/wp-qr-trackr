import { defineConfig } from '@playwright/test';

export default defineConfig({
  testDir: './tests',
  timeout: 180000, // Increased global timeout to 3 minutes
  expect: {
    timeout: 60000, // Increased expect timeout to 1 minute
  },
  fullyParallel: false,
  retries: 1, // Reduced retries to minimize test duration
  workers: 1,
  reporter: [
    ['html'],
    ['list'],
    ['json', { outputFile: 'test-results/test-results.json' }]
  ],
  use: {
    baseURL: 'http://wordpress-playwright',
    trace: 'on', // Always capture traces for better debugging
    screenshot: 'on', // Always capture screenshots
    video: 'on', // Always capture video
    headless: true,
    viewport: { width: 1280, height: 720 },
    launchOptions: {
      args: [
        '--disable-dev-shm-usage',
        '--no-sandbox',
        '--disable-setuid-sandbox',
        '--disable-web-security',
        '--disable-features=IsolateOrigins,site-per-process',
        '--window-size=1280,720'
      ],
      slowMo: 0, // No slowMo for CI
    },
    actionTimeout: 90000, // Increased action timeout to 1.5 minutes
    navigationTimeout: 90000, // Increased navigation timeout to 1.5 minutes
    contextOptions: {
      ignoreHTTPSErrors: true,
    }
  },
  projects: [
    {
      name: 'chromium',
      use: {
        browserName: 'chromium',
        channel: 'chrome',
        permissions: ['clipboard-read', 'clipboard-write'],
        bypassCSP: true,
        acceptDownloads: true,
        javaScriptEnabled: true,
      },
    }
  ],
  globalSetup: './global-setup.ts',
  globalTeardown: './global-teardown.ts',
  testMatch: '**/*.spec.ts',
  testIgnore: ['**/node_modules/**/*'],
  preserveOutput: 'always', // Keep all test artifacts
  maxFailures: 1, // Stop after first failure for faster feedback
}); 