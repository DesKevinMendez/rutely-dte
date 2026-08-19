import { readFile } from 'node:fs/promises';
import { expect, test } from '@playwright/test';
import type { Page } from '@playwright/test';
import { authenticateDeveloper } from './support/auth';

const testCertificatePath = 'certificates/playwright-mh-test-certificate.crt';

async function storeRepositoryCertificate(page: Page): Promise<void> {
    const token = await page.evaluate(() => localStorage.getItem('auth_token'));
    expect(token).toBeTruthy();

    const certificadoXml = await readFile(testCertificatePath, 'utf8');
    const response = await page.request.post('/api/v1/mh-certificates', {
        headers: {
            Authorization: `Bearer ${token}`,
        },
        data: {
            environment: '00',
            certificadoXml,
            passwordPri: 'password',
        },
    });

    expect(response.status()).toBe(200);
}

test.describe('DTEs', () => {
    test.describe.configure({ mode: 'serial' });

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

        const table = page.getByRole('table');

        await expect(table.getByText('PROCESADO', { exact: true })).toBeVisible();
        await expect(table.getByText('RECHAZADO', { exact: true })).toBeVisible();
        await expect(table.getByText('INVALIDADO', { exact: true })).toBeVisible();
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

    test('creates a Factura 01 from the UI against the Laravel backend', async ({ page }) => {
        await storeRepositoryCertificate(page);
        await expect(page.getByText('DTE-01-00010001-000000000000001', { exact: true })).toBeVisible();

        await page.getByRole('button', { name: 'Emitir Nuevo DTE', exact: true }).click();
        await expect(page.locator('#tipo-dte')).toBeVisible();
        await expect(page.locator('#tipo-dte')).toHaveValue('01');

        await page.locator('#receptor-nombre').fill('CLIENTE PLAYWRIGHT FACTURA');
        await page.locator('#receptor-documento').fill('0614-280390-112-1');
        await page.locator('#receptor-correo').fill('factura.playwright@example.test');
        await page.locator('#item-desc-0').fill('Servicio E2E Playwright');
        await page.locator('#item-qty-0').fill('2');
        await page.locator('#item-price-0').fill('25');

        await expect(page.getByText('Total: $56.50 USD', { exact: true })).toBeVisible();

        const dteGetRequests: string[] = [];
        page.on('request', (request) => {
            const url = new URL(request.url());

            if (request.method() === 'GET' && url.pathname === '/api/v1/dtes') {
                dteGetRequests.push(request.url());
            }
        });

        const dteResponsePromise = page.waitForResponse(
            (response) =>
                response.request().method() === 'POST' &&
                new URL(response.url()).pathname === '/api/v1/dtes',
        );

        await page.getByRole('button', { name: 'Emitir DTE', exact: true }).click();

        const dteResponse = await dteResponsePromise;
        expect(dteResponse.status()).toBe(201);

        const body = (await dteResponse.json()) as {
            data: {
                record: {
                    control_number: string;
                    dte_type: string;
                    receiver_document: string;
                    total_amount: number;
                    original_json: {
                        identificacion: {
                            fecEmi: string;
                            horEmi: string;
                        };
                    };
                };
            };
        };
        const record = body.data.record;

        expect(record.dte_type).toBe('01');
        expect(record.receiver_document).toBe('0614-280390-112-1');
        expect(record.total_amount).toBe(5650);
        expect(record.original_json.identificacion.fecEmi).toMatch(/^\d{4}-\d{2}-\d{2}$/);
        expect(record.original_json.identificacion.horEmi).toMatch(/^\d{2}:\d{2}:\d{2}$/);

        await expect(page.getByText(record.control_number, { exact: true })).toBeVisible();
        await expect(
            page.getByText('CLIENTE PLAYWRIGHT FACTURA', { exact: true }),
        ).toBeVisible();

        const [year, month, day] = record.original_json.identificacion.fecEmi.split('-');
        await expect(
            page.getByText(
                `${day}/${month}/${year} ${record.original_json.identificacion.horEmi}`,
                { exact: true },
            ),
        ).toBeVisible();

        await page.waitForLoadState('networkidle');
        expect(dteGetRequests).toHaveLength(1);

        const refreshUrl = new URL(dteGetRequests[0]);
        expect(refreshUrl.searchParams.get('page')).toBe('1');
        expect(refreshUrl.searchParams.get('per_page')).toBe('10');
    });
});
