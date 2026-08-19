import { expect, test } from '@playwright/test';
import { authenticateDeveloper } from './support/auth';

test.describe('Queue', () => {
    test.beforeEach(async ({ page }) => {
        await authenticateDeveloper(page);
        await page.goto('/#/queue');
    });

    test('shows the transmission queue state from the backend', async ({ page }) => {
        await expect(
            page.getByRole('heading', { name: 'Cola de Reintentos de Transmisión (Background Jobs)' }),
        ).toBeVisible();

        const retryButton = page.getByRole('button', { name: 'Reintentar fallidos (0)' });
        await expect(retryButton).toBeVisible();
        await expect(retryButton).toBeDisabled();
    });

    test('refreshes the transmission queue from the API', async ({ page }) => {
        const queueResponse = page.waitForResponse(
            (response) =>
                response.request().method() === 'GET' &&
                new URL(response.url()).pathname === '/api/v1/queue',
        );

        await page.getByRole('button', { name: 'Actualizar Cola' }).click();

        expect((await queueResponse).status()).toBe(200);
        await expect(page.getByRole('button', { name: 'Reintentar fallidos (0)' })).toBeDisabled();
    });
});
