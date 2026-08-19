import { expect, test } from '@playwright/test';
import { authenticateDeveloper } from './support/auth';

test.describe('API tokens', () => {
    test.beforeEach(async ({ page }) => {
        await authenticateDeveloper(page);
        await page.goto('/#/tokens');
    });

    test('creates a company API token and shows the plaintext only after creation', async ({ page }) => {
        await expect(page.getByRole('heading', { name: 'Tokens de API' })).toBeVisible();

        const storeRequests: string[] = [];
        page.on('request', (request) => {
            const url = new URL(request.url());

            if (
                request.method() === 'POST' &&
                url.pathname === '/api/v1/tokens'
            ) {
                storeRequests.push(request.url());
            }
        });

        await page
            .getByRole('button', { name: 'Crear token', exact: true })
            .click();
        await page.locator('#api-token-name').fill('ERP Playwright');

        const storeResponse = page.waitForResponse(
            (response) =>
                response.request().method() === 'POST' &&
                new URL(response.url()).pathname === '/api/v1/tokens',
        );

        await page.locator('#api-token-name').press('Enter');

        const response = await storeResponse;
        expect(response.status()).toBe(201);
        expect(storeRequests).toHaveLength(1);

        const body = (await response.json()) as {
            data: {
                record: {
                    id: number;
                    name: string;
                    last_used_at: string | null;
                    created_at: string;
                };
                plain_text_token: string;
            };
        };

        expect(body.data.record.name).toBe('ERP Playwright');
        expect(body.data.record.last_used_at).toBeNull();
        expect(body.data.plain_text_token).toMatch(/^\d+\|.+$/);

        await expect(
            page.getByText(body.data.plain_text_token, { exact: true }),
        ).toBeVisible();

        await page.getByRole('button', { name: 'Ya lo guardé' }).click();

        await expect(
            page.getByText(body.data.plain_text_token, { exact: true }),
        ).toHaveCount(0);
    });
});
