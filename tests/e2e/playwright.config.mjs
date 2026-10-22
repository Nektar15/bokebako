// E2E テストの設定（ADR 0015）。使い方は README.md
import { defineConfig, devices } from '@playwright/test';

export default defineConfig({
  testDir: './specs',
  globalSetup: './global-setup.mjs',
  fullyParallel: false,
  workers: 1, // DB を共有するので 1 本ずつ
  retries: 0,
  reporter: [['list'], ['html', { open: 'never' }]],
  use: {
    baseURL: process.env.E2E_BASE_URL || 'http://localhost:8080',
    launchOptions: process.env.E2E_CHROMIUM ? { executablePath: process.env.E2E_CHROMIUM } : {},
    screenshot: 'only-on-failure',
    trace: 'retain-on-failure',
  },
  projects: [
    { name: 'iphone', use: { ...devices['iPhone 13'], browserName: 'chromium' } },
    { name: 'android', use: { ...devices['Pixel 7'] } },
    { name: 'pc', use: { ...devices['Desktop Chrome'], viewport: { width: 1280, height: 800 } } },
  ],
});
