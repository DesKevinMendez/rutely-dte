import { expect, test } from '@playwright/test';
import type { Page } from '@playwright/test';
import { authenticateDeveloper } from './support/auth';

const metricValue = (page: Page, title: string) =>
    page
        .getByText(title, { exact: true })
        .locator('../..')
        .locator('p.text-2xl');

test.describe('Dashboard', () => {
    test.beforeEach(async ({ page }) => {
        await authenticateDeveloper(page);
        await page.goto('/#/dashboard');
    });

    test('shows metrics calculated from seeded DTEs', async ({ page }) => {
        await expect(
            page.getByRole('heading', {
                name: 'Resumen de Facturación Electrónica (DTE)',
            }),
        ).toBeVisible();
        await expect(
            page.getByText('playwright@rutely.biz', { exact: true }),
        ).toBeVisible();

        await expect(metricValue(page, 'Total Emitidos')).toHaveText('3');
        await expect(metricValue(page, 'Monto Total ($ USD)')).toHaveText(
            '$67.80',
        );
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

    test('confirms environment changes through Ornito tabs', async ({
        page,
    }) => {
        const environmentTabs = page.getByRole('tablist', { name: 'Ambiente' });
        const testsTab = environmentTabs.getByRole('tab', { name: 'Pruebas' });
        const productionTab = environmentTabs.getByRole('tab', {
            name: 'Producción',
        });

        await expect(environmentTabs).toBeVisible();
        await expect(testsTab).toHaveAttribute('aria-selected', 'true');
        await expect(
            page.getByText('No content available for this tab', {
                exact: true,
            }),
        ).toHaveCount(0);

        await productionTab.click();

        const confirmationDialog = page.getByRole('dialog');
        await expect(confirmationDialog).toBeVisible();
        await expect(confirmationDialog).toContainText(
            'Cambiar a ambiente de Producción',
        );
        await expect(productionTab).toHaveAttribute('aria-selected', 'false');

        await confirmationDialog
            .getByRole('textbox', { name: 'Confirmación' })
            .fill('Confirmar');
        await confirmationDialog
            .getByRole('button', { name: 'Confirmar', exact: true })
            .click();

        await expect(confirmationDialog).not.toBeVisible();
        await expect(productionTab).toHaveAttribute('aria-selected', 'true');
    });
});
