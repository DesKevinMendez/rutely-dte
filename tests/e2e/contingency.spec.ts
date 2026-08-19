import { expect, test } from '@playwright/test';
import { authenticateDeveloper } from './support/auth';

test.describe('Contingency', () => {
    test('loads and toggles manual contingency through the backend', async ({ page }) => {
        await authenticateDeveloper(page);
        await page.goto('/#/contingency');

        await expect(
            page.getByRole('heading', { name: 'Panel de Contingencia (Ministerio de Hacienda)' }),
        ).toBeVisible();
        await expect(page.getByText('CLOSED', { exact: true })).toBeVisible();
        await expect(page.getByText('DESACTIVADO', { exact: true })).toBeVisible();

        const updateResponse = page.waitForResponse(
            (response) =>
                ['PUT', 'PATCH'].includes(response.request().method()) &&
                new URL(response.url()).pathname === '/api/v1/contingency',
        );

        await page.getByRole('button', { name: 'Activar Contingencia' }).click();

        expect((await updateResponse).status()).toBe(200);
        await expect(page.getByText('MANUAL_OPEN', { exact: true })).toBeVisible();
        await expect(page.getByText('ACTIVADO', { exact: true })).toBeVisible();
        await expect(page.getByRole('button', { name: 'Desactivar Contingencia' })).toBeVisible();
    });
});
