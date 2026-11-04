import type { Page } from '@playwright/test';

export async function authenticateDeveloper(page: Page): Promise<void> {
    const session = JSON.parse(process.env.E2E_SESSION ?? 'null') as {
        token: string;
        user: Record<string, unknown>;
    };

    await page.addInitScript(
        ({ token, user }) => {
            localStorage.setItem('auth_token', token);
            localStorage.setItem('auth_user', JSON.stringify(user));
        },
        session,
    );
}
