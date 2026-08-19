import { expect, test } from '@playwright/test';
import type { Page } from '@playwright/test';
import { authenticateDeveloper } from './support/auth';

const metricValue = (page: Page, title: string) =>
    page.getByText(title, { exact: true }).locator('../..').locator('p.text-2xl');

test.describe('Dashboard', () => {
    test.beforeEach(async ({ page }) => {
        await authenticateDeveloper(page);
        await page.goto('/#/dashboard');
    });

    test('shows metrics calculated from seeded DTEs', async ({ page }) => {
        await expect(
            page.getByRole('heading', { name: 'Resumen de Facturación Electrónica (DTE)' }),
        ).toBeVisible();
        await expect(page.getByText('playwright@rutely.biz', { exact: true })).toBeVisible();

        await expect(metricValue(page, 'Total Emitidos')).toHaveText('3');
        await expect(metricValue(page, 'Monto Total ($ USD)')).toHaveText('$67.80');
        await expect(metricValue(page, 'Exitosos (MH)')).toHaveText('1');
        await expect(metricValue(page, 'Requieren atención')).toHaveText('2');
    });

    test('refreshes dashboard metrics from the API', async ({ page }) => {
        const dashboardResponse = page.waitForResponse(
            (response) =>
                response.request().method() === 'GET' &&
                new URL(response.url()).pathname === '/api/v1/dashboard',
        );

        await page.getByRole('button', { name: 'Actualizar Datos' }).click();

        expect((await dashboardResponse).status()).toBe(200);
        await expect(metricValue(page, 'Total Emitidos')).toHaveText('3');
    });
});
