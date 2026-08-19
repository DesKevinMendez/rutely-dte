import { expect, test } from '@playwright/test';
import { authenticateDeveloper } from './support/auth';

test.describe('MH credentials', () => {
    test('formats the NIT and stores credentials through the backend', async ({ page }) => {
        await authenticateDeveloper(page);
        await page.goto('/#/mh-credentials');

        await expect(
            page.getByRole('heading', { name: 'Credenciales de Autenticación (MH)' }),
        ).toBeVisible();
        await expect(page.getByText('Sin Credenciales Registradas', { exact: true })).toBeVisible();

        const nitInput = page.locator('#mh-nit');
        await nitInput.fill('06142803901121');
        await expect(nitInput).toHaveValue('0614-280390-112-1');

        await page.locator('#mh-password').fill('playwright-secret');

        const credentialsRequest = page.waitForRequest(
            (request) =>
                request.method() === 'POST' &&
                new URL(request.url()).pathname === '/api/v1/mh-credentials',
        );
        const credentialsResponse = page.waitForResponse(
            (response) =>
                response.request().method() === 'POST' &&
                new URL(response.url()).pathname === '/api/v1/mh-credentials',
        );

        await page.getByRole('button', { name: 'Guardar Credenciales MH' }).click();

        expect((await credentialsRequest).postDataJSON()).toEqual({
            environment: '00',
            nit: '0614-280390-112-1',
            pwd: 'playwright-secret',
        });
        expect((await credentialsResponse).status()).toBe(200);

        await expect(page.getByText('ACTIVO', { exact: true })).toBeVisible();
        await expect(page.getByText('0614-280390-112-1', { exact: true })).toBeVisible();
        await expect(page.getByText(/Credenciales guardadas correctamente/)).toBeVisible();
    });
});
