import { expect, test } from '@playwright/test';
import { authenticateDeveloper } from './support/auth';

test.describe('DTEs', () => {
    test.beforeEach(async ({ page }) => {
        await authenticateDeveloper(page);
        await page.goto('/#/dtes');
    });

    test('shows seeded DTEs from the backend', async ({ page }) => {
        await expect(
            page.getByRole('heading', { name: 'Documentos Tributarios Electrónicos (DTEs)' }),
        ).toBeVisible();

        await expect(page.getByText('DTE-01-00010001-000000000000001', { exact: true })).toBeVisible();
        await expect(page.getByText('DTE-01-00010001-000000000000002', { exact: true })).toBeVisible();
        await expect(page.getByText('DTE-01-00010001-000000000000003', { exact: true })).toBeVisible();
        await expect(page.getByText('PROCESADO', { exact: true })).toBeVisible();
        await expect(page.getByText('RECHAZADO', { exact: true })).toBeVisible();
        await expect(page.getByText('INVALIDADO', { exact: true })).toBeVisible();
    });

    test('filters DTEs by status', async ({ page }) => {
        await page.locator('#filter-estado').selectOption('PROCESADO');

        await expect(page.getByText('DTE-01-00010001-000000000000001', { exact: true })).toBeVisible();
        await expect(page.getByText('DTE-01-00010001-000000000000002', { exact: true })).toHaveCount(0);
        await expect(page.getByText('DTE-01-00010001-000000000000003', { exact: true })).toHaveCount(0);
    });

    test('refreshes the DTE list from the API', async ({ page }) => {
        const dtesResponse = page.waitForResponse(
            (response) =>
                response.request().method() === 'GET' &&
                new URL(response.url()).pathname === '/api/v1/dtes',
        );

        await page.getByRole('button', { name: 'Actualizar', exact: true }).click();

        expect((await dtesResponse).status()).toBe(200);
        await expect(page.getByText('DTE-01-00010001-000000000000001', { exact: true })).toBeVisible();
    });
});
