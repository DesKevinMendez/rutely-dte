import { request } from '@playwright/test';
import type { FullConfig } from '@playwright/test';

export default async function globalSetup(config: FullConfig): Promise<void> {
    const context = await request.newContext({
        baseURL: config.projects[0]?.use.baseURL,
    });
    const response = await context.post('/api/v1/login', {
        data: {
            email: 'playwright@rutely.biz',
            password: 'password',
            device_name: 'playwright-e2e',
        },
    });

    if (!response.ok()) {
        throw new Error(`E2E login failed with status ${response.status()}`);
    }

    const body = (await response.json()) as { data: unknown };

    process.env.E2E_SESSION = JSON.stringify(body.data);

    await context.dispose();
}
