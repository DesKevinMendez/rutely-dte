import { defineConfig } from '@playwright/test';
import projectConfig from './playwright.config';

export default defineConfig({
    ...projectConfig,
    use: {
        ...projectConfig.use,
        baseURL: 'http://127.0.0.1:9002',
    },
    webServer: undefined,
});
