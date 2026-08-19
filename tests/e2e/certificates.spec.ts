import { expect, test } from '@playwright/test';
import { authenticateDeveloper } from './support/auth';

test.describe('Certificates', () => {
    test.beforeEach(async ({ page }) => {
        await authenticateDeveloper(page);
        await page.goto('/#/certificates');
    });

    test('shows the empty certificate state for the selected environment', async ({ page }) => {
        await expect(page.getByRole('heading', { name: 'Certificados Digitales (MH)' })).toBeVisible();
        await expect(page.getByText('Sin Certificado Registrado', { exact: true })).toBeVisible();
        await expect(page.getByText('Ambiente seleccionado: PRUEBAS', { exact: true })).toBeVisible();
    });

    test('loads certificate state when changing environment', async ({ page }) => {
        const certificateResponse = page.waitForResponse(
            (response) =>
                response.request().method() === 'GET' &&
                new URL(response.url()).pathname === '/api/v1/mh-certificates',
        );

        await page
            .getByRole('button', {
                name: 'Producción Emisión real de DTEs',
                exact: true,
            })
            .click();

        expect((await certificateResponse).status()).toBe(404);
        await expect(page.getByText('Ambiente seleccionado: PRODUCCION', { exact: true })).toBeVisible();
        await expect(page.getByText('Sin Certificado Registrado', { exact: true })).toBeVisible();
    });

    test('enables certificate submission after selecting a file', async ({ page }) => {
        const submitButton = page.getByRole('button', { name: 'Guardar y Encriptar Certificado' });
        await expect(submitButton).toBeDisabled();

        await page.locator('#certificate-file').setInputFiles({
            name: 'playwright-certificate.xml',
            mimeType: 'application/xml',
            buffer: Buffer.from('<certificado />'),
        });

        await expect(page.getByText('playwright-certificate.xml', { exact: true })).toBeVisible();
        await expect(submitButton).toBeEnabled();
    });
});
