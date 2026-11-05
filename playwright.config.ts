import { defineConfig, devices } from '@playwright/test';

const baseURL = 'http://127.0.0.1:9003';

export default defineConfig({
    testDir: './tests/e2e',
    globalSetup: './tests/e2e/support/global-setup.ts',
    fullyParallel: true,
    forbidOnly: !!process.env.CI,
    retries: process.env.CI ? 2 : 0,
    workers: process.env.CI ? 1 : undefined,
    reporter: 'html',
    use: {
        baseURL,
        trace: 'on-first-retry',
        screenshot: 'only-on-failure',
        video: 'retain-on-failure',
    },
    webServer: {
        command: process.env.CI
            ? 'sh tests/e2e/support/serve.sh'
            : 'pnpm build && exec docker compose run --rm --service-ports -T e2e',
        url: `${baseURL}/up`,
        reuseExistingServer: false,
        gracefulShutdown: { signal: 'SIGINT', timeout: 10_000 },
        timeout: 120_000,
    },
    projects: [
        {
            name: 'chromium',
            use: { ...devices['Desktop Chrome'] },
        },
    ],
});
