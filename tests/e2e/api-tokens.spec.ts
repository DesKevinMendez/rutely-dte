import { expect, test } from '@playwright/test';
import { authenticateDeveloper } from './support/auth';

interface ApiTokenRecord {
    id: number;
    name: string;
    last_used_at: string | null;
    created_at: string;
}

interface ApiTokenIndexResponse {
    data: ApiTokenRecord[];
    pagination: {
        total: number;
    };
}

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
                record: ApiTokenRecord;
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
        await expect(page.getByText('ERP Playwright', { exact: true })).toBeVisible();
    });

    test('lists company API tokens from the backend with their secrets masked', async ({ page }) => {
        const authToken = await page.evaluate(() =>
            localStorage.getItem('auth_token'),
        );

        expect(authToken).toBeTruthy();

        for (const name of ['ERP Listado A', 'ERP Listado B']) {
            const response = await page.request.post('/api/v1/tokens', {
                headers: {
                    Authorization: `Bearer ${authToken}`,
                    Accept: 'application/json',
                },
                data: { name },
            });

            expect(response.status()).toBe(201);
        }

        const indexRequests: string[] = [];
        page.on('request', (request) => {
            const url = new URL(request.url());

            if (
                request.method() === 'GET' &&
                url.pathname === '/api/v1/tokens'
            ) {
                indexRequests.push(request.url());
            }
        });

        const indexResponse = page.waitForResponse(
            (response) =>
                response.request().method() === 'GET' &&
                new URL(response.url()).pathname === '/api/v1/tokens',
        );

        await page.reload();

        const response = await indexResponse;
        expect(response.status()).toBe(200);

        const body = (await response.json()) as ApiTokenIndexResponse;
        const listedTokens = body.data.filter((token) =>
            ['ERP Listado A', 'ERP Listado B'].includes(token.name),
        );

        expect(listedTokens).toHaveLength(2);

        for (const token of listedTokens) {
            const row = page.getByRole('row').filter({ hasText: token.name });
            const createdAt = new Date(token.created_at).toLocaleString('es-SV', {
                timeZone: 'America/El_Salvador',
            });

            await expect(row).toBeVisible();
            await expect(
                row.getByText('********************', { exact: true }),
            ).toBeVisible();
            await expect(row.getByText('Nunca', { exact: true })).toBeVisible();
            await expect(row.getByText(createdAt, { exact: true })).toBeVisible();
        }

        await page.waitForLoadState('networkidle');
        expect(indexRequests).toHaveLength(1);
    });
});
