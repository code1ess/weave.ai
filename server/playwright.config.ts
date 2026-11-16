import { defineConfig } from '@playwright/test';
import { resolve } from 'node:path';

export default defineConfig({
  testDir: './tests/Browser',
  fullyParallel: false,
  workers: 1,
  use: {
    baseURL: 'http://127.0.0.1:8138',
    headless: true,
    launchOptions: {
      executablePath: process.env.PLAYWRIGHT_CHROMIUM_EXECUTABLE_PATH,
    },
    trace: 'retain-on-failure',
  },
  webServer: {
    command: 'node tests/Browser/start.mjs',
    url: 'http://127.0.0.1:8138',
    reuseExistingServer: false,
    env: {
      APP_ENV: 'local',
      APP_URL: 'http://127.0.0.1:8138',
      DB_CONNECTION: 'sqlite',
      DB_DATABASE: resolve('storage/framework/testing/browser.sqlite'),
      SESSION_DRIVER: 'file',
      SESSION_COOKIE: 'weave_browser_test',
      CACHE_STORE: 'array',
      QUEUE_CONNECTION: 'sync',
      ASSET_DISK: 'local',
      XDEBUG_MODE: 'off',
    },
  },
});
