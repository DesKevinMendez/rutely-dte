import { expect } from '@playwright/test';
import type { Page } from '@playwright/test';

const credentials = {
    email: 'playwright@rutely.biz',
    password: 'password',
};

export async function authenticateDeveloper(page: Page): Promise<void> {
    const response = await page.request.post('/api/v1/login', {
        data: {
            ...credentials,
            device_name: 'playwright-e2e',
        },
    });

    expect(response.ok()).toBeTruthy();

    const body = (await response.json()) as {
        data: {
            token: string;
            user: Record<string, unknown>;
        };
    };

    await page.addInitScript(
        ({ token, user }) => {
            localStorage.setItem('auth_token', token);
            localStorage.setItem('auth_user', JSON.stringify(user));
        },
        body.data,
    );
}
