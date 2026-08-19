import { expect, test } from '@playwright/test';
import { authenticateDeveloper } from './support/auth';

test.describe('Certificates', () => {
    test.describe.configure({ mode: 'serial' });

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

    test('uploads and stores the repository MH certificate fixture', async ({ page }) => {
        const certificateResponse = page.waitForResponse(
            (response) =>
                response.request().method() === 'POST' &&
                new URL(response.url()).pathname === '/api/v1/mh-certificates',
        );

        await page
            .locator('#certificate-file')
            .setInputFiles('certificates/playwright-mh-test-certificate.crt');
        await page.locator('#certificate-password').fill('password');
        await page.getByRole('button', { name: 'Guardar y Encriptar Certificado' }).click();

        expect((await certificateResponse).status()).toBe(200);
        await expect(
            page.getByText('Certificado guardado correctamente para el ambiente de', {
                exact: false,
            }),
        ).toBeVisible();
        await expect(page.getByText('ACTIVO', { exact: true })).toBeVisible();
        await expect(page.getByText('06142812901015', { exact: true })).toBeVisible();
        await expect(
            page.getByText(
                'El certificado se encuentra almacenado de forma segura y listo para el firmador DTE.',
                { exact: true },
            ),
        ).toBeVisible();
    });
});
