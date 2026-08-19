import { createPublicKey, verify as verifySignature } from 'node:crypto';
import { readFile } from 'node:fs/promises';
import { expect, test } from '@playwright/test';
import type { Page } from '@playwright/test';
import { authenticateDeveloper } from './support/auth';

const testCertificatePath = 'certificates/playwright-mh-test-certificate.crt';

async function authorizationHeaders(page: Page): Promise<{ Authorization: string }> {
    const token = await page.evaluate(() => localStorage.getItem('auth_token'));
    expect(token).toBeTruthy();

    return {
        Authorization: `Bearer ${token}`,
    };
}

async function storeRepositoryCertificate(page: Page): Promise<void> {
    const certificadoXml = await readFile(testCertificatePath, 'utf8');
    const response = await page.request.post('/api/v1/mh-certificates', {
        headers: await authorizationHeaders(page),
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

    test('persists a cryptographically valid RS512 signature for the issued Factura 01', async ({ page }) => {
        await storeRepositoryCertificate(page);

        await page.getByRole('button', { name: 'Emitir Nuevo DTE', exact: true }).click();
        await expect(page.locator('#tipo-dte')).toBeVisible();
        await page.locator('#receptor-nombre').fill('CLIENTE FIRMA PLAYWRIGHT');
        await page.locator('#receptor-documento').fill('0614-150592-101-9');
        await page.locator('#receptor-correo').fill('firma.playwright@example.test');
        await page.locator('#item-desc-0').fill('Servicio firmado E2E');
        await page.locator('#item-qty-0').fill('1');
        await page.locator('#item-price-0').fill('12.34');

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
                    id: string;
                    generation_code: string;
                    control_number: string;
                    dte_type: string;
                    issuer_nit: string;
                    signed_json: string;
                };
            };
        };
        const record = body.data.record;

        expect(record.dte_type).toBe('01');
        expect(record.issuer_nit).toBe('06142812901015');
        expect(record.signed_json).toBeTruthy();

        const segments = record.signed_json.split('.');
        expect(segments).toHaveLength(3);

        const [protectedHeader, encodedPayload, encodedSignature] = segments;
        const header = JSON.parse(
            Buffer.from(protectedHeader, 'base64url').toString('utf8'),
        ) as { alg: string };
        const payload = JSON.parse(
            Buffer.from(encodedPayload, 'base64url').toString('utf8'),
        ) as {
            identificacion: {
                tipoDte: string;
                numeroControl: string;
                codigoGeneracion: string;
            };
            emisor: { nit: string };
            receptor: { nombre: string };
            cuerpoDocumento: Array<{ descripcion: string }>;
        };

        expect(header.alg).toBe('RS512');
        expect(payload.identificacion.tipoDte).toBe('01');
        expect(payload.identificacion.numeroControl).toBe(record.control_number);
        expect(payload.identificacion.codigoGeneracion).toBe(record.generation_code);
        expect(payload.emisor.nit).toBe('06142812901015');
        expect(payload.receptor.nombre).toBe('CLIENTE FIRMA PLAYWRIGHT');
        expect(payload.cuerpoDocumento[0]?.descripcion).toBe('Servicio firmado E2E');

        const certificateXml = await readFile(testCertificatePath, 'utf8');
        const publicKeyMatch = certificateXml.match(
            /<publicKey>[\s\S]*?<encodied>([^<]+)<\/encodied>/i,
        );

        if (!publicKeyMatch) {
            throw new Error('El certificado de pruebas no contiene la llave pública RSA.');
        }

        const publicKey = createPublicKey({
            key: Buffer.from(publicKeyMatch[1].replace(/\s+/g, ''), 'base64'),
            format: 'der',
            type: 'spki',
        });
        const signatureIsValid = verifySignature(
            'RSA-SHA512',
            Buffer.from(`${protectedHeader}.${encodedPayload}`),
            publicKey,
            Buffer.from(encodedSignature, 'base64url'),
        );

        expect(signatureIsValid).toBe(true);

        const persistedResponse = await page.request.get(`/api/v1/dtes/${record.id}`, {
            headers: await authorizationHeaders(page),
        });
        expect(persistedResponse.status()).toBe(200);

        const persistedBody = (await persistedResponse.json()) as {
            data: {
                signed_json: string;
                control_number: string;
                generation_code: string;
            };
        };

        expect(persistedBody.data.signed_json).toBe(record.signed_json);
        expect(persistedBody.data.control_number).toBe(record.control_number);
        expect(persistedBody.data.generation_code).toBe(record.generation_code);
    });
});
