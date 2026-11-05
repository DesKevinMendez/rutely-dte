import { expect, test } from '@playwright/test';
import { authenticateDeveloper } from './support/auth';

test.describe('Certificates', () => {
    test.describe.configure({ mode: 'serial' });

    test.beforeEach(async ({ page }) => {
        await authenticateDeveloper(page);

        const { user } = JSON.parse(process.env.E2E_SESSION ?? 'null') as {
            user: { company: { environment: '00' | '01' } };
        };
        user.company.environment = '00';
        await page.route('**/api/user', (route) => route.fulfill({ json: user }));
        await page.route('**/api/v1/companies/*/environment', (route) => {
            const { environment } = route.request().postDataJSON() as {
                environment: '00' | '01';
            };
            user.company.environment = environment;

            return route.fulfill({ json: { data: { user } } });
        });
        await page.route('**/api/v1/mh-certificates?environment=*', (route) =>
            route.fulfill({ status: 404, json: { message: 'No encontrado.' } }),
        );

        await page.goto('/#/certificates');
    });

    test('shows the empty certificate state for the selected environment', async ({ page }) => {
        await expect(page.getByRole('heading', { name: 'Certificados Digitales (MH)' })).toBeVisible();
        await expect(page.getByText('Sin Certificado Registrado', { exact: true })).toBeVisible();
        await expect(page.getByText('Ambiente seleccionado: PRUEBAS', { exact: true })).toBeVisible();
    });

    test('uses the navbar environment to load and upload the certificate', async ({ page }) => {
        const certificateFile = {
            name: 'playwright-certificate.xml',
            mimeType: 'application/xml',
            buffer: Buffer.from('<certificado />'),
        };
        const fileInput = page.locator('#certificate-file');
        const passwordInput = page.locator('#certificate-password');
        const submitButton = page.getByRole('button', {
            name: 'Guardar y Encriptar Certificado',
        });
        await expect(page.getByText('Ambiente de Destino')).toHaveCount(0);
        await fileInput.setInputFiles(certificateFile);
        await passwordInput.fill('Playwright-draft');

        const productionTab = page
            .getByRole('tablist', { name: 'Ambiente' })
            .getByRole('tab', { name: 'Producción' });
        await productionTab.click();
        const dialog = page.getByRole('dialog');
        await dialog
            .getByRole('textbox', { name: 'Confirmación' })
            .fill('Confirmar');

        const certificateResponse = page.waitForResponse(
            (response) =>
                response.request().method() === 'GET' &&
                new URL(response.url()).pathname === '/api/v1/mh-certificates' &&
                new URL(response.url()).searchParams.get('environment') === '01',
        );

        await dialog
            .getByRole('button', { name: 'Confirmar', exact: true })
            .click();

        expect((await certificateResponse).status()).toBe(404);
        await expect(productionTab).toHaveAttribute('aria-selected', 'true');
        await expect(
            page.getByText('Ambiente seleccionado: PRODUCCION', { exact: true }),
        ).toBeVisible();
        await expect(
            page.getByText('Sin Certificado Registrado', { exact: true }),
        ).toBeVisible();
        await expect(fileInput).toHaveValue('');
        await expect(passwordInput).toHaveValue('');
        await expect(submitButton).toBeDisabled();

        await page.route('**/api/v1/mh-certificates', (route) =>
            route.fulfill({
                json: {
                    data: {
                        id: 'test-certificate',
                        environment: '01',
                        nit: '06142812901015',
                        active: true,
                        updated_at: null,
                    },
                },
            }),
        );
        await fileInput.setInputFiles(certificateFile);
        await passwordInput.fill('Playwright-certificate-password');
        const uploadRequest = page.waitForRequest(
            (request) =>
                request.method() === 'POST' &&
                new URL(request.url()).pathname === '/api/v1/mh-certificates',
        );
        await submitButton.click();

        expect((await uploadRequest).postDataJSON()).toEqual({
            environment: '01',
            certificadoXml: '<certificado />',
            passwordPri: 'Playwright-certificate-password',
        });
        await expect(
            page.getByText(/Certificado guardado correctamente.*PRODUCCION/),
        ).toBeVisible();
        await expect(page.getByText('ACTIVO', { exact: true })).toBeVisible();
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

    test('rejects a certificate larger than 2 MB before uploading it', async ({ page }) => {
        await page.locator('#certificate-file').setInputFiles({
            name: 'oversized-certificate.xml',
            mimeType: 'application/xml',
            buffer: Buffer.alloc(2 * 1024 * 1024 + 1, 'a'),
        });

        await expect(
            page.getByText('El certificado no debe pesar más de 2 MB.', { exact: true }),
        ).toBeVisible();
        await expect(page.getByRole('button', { name: 'Guardar y Encriptar Certificado' })).toBeDisabled();
    });

    test('requires the private key password before uploading the certificate', async ({ page }) => {
        const uploadRequests: string[] = [];
        page.on('request', (request) => {
            if (request.method() === 'POST' && new URL(request.url()).pathname === '/api/v1/mh-certificates') {
                uploadRequests.push(request.url());
            }
        });

        await expect(page.getByText('(Opcional)')).toHaveCount(0);

        await page.locator('#certificate-file').setInputFiles({
            name: 'playwright-certificate.xml',
            mimeType: 'application/xml',
            buffer: Buffer.from('<certificado />'),
        });
        await page.getByRole('button', { name: 'Guardar y Encriptar Certificado' }).click();

        await expect(
            page.getByText('La contraseña de la clave privada es requerida.', { exact: true }),
        ).toBeVisible();
        expect(uploadRequests).toHaveLength(0);
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
        await expect(page.getByText('0614-281290-101-5', { exact: true })).toBeVisible();
        await expect(
            page.getByText(
                'El certificado se encuentra almacenado de forma segura y listo para el firmador DTE.',
                { exact: true },
            ),
        ).toBeVisible();
    });
});
