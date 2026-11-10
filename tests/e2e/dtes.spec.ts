import { createPublicKey, verify as verifySignature } from 'node:crypto';
import { readFile } from 'node:fs/promises';
import { expect, test } from '@playwright/test';
import type { Page } from '@playwright/test';
import { authenticateDeveloper } from './support/auth';

const testCertificatePath = 'certificates/playwright-mh-test-certificate.crt';

async function authorizationHeaders(
    page: Page,
): Promise<{ Authorization: string }> {
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

test.describe('DTE list default date', () => {
    test.use({ timezoneId: 'UTC' });

    test('starts on today in El Salvador when the browser date is already tomorrow', async ({
        page,
    }) => {
        await page.clock.install({
            time: new Date('2026-11-07T04:30:00.000Z'),
        });
        await authenticateDeveloper(page);

        const dtesRequest = page.waitForRequest(
            (request) => new URL(request.url()).pathname === '/api/v1/dtes',
        );

        await page.goto('/#/dtes');

        const { searchParams } = new URL((await dtesRequest).url());
        expect(searchParams.get('filter[fecha]')).toBe('2026-11-06');
    });
});

test.describe('DTEs', () => {
    test.describe.configure({ mode: 'serial' });

    test.beforeEach(async ({ page }) => {
        await authenticateDeveloper(page);
        await page.goto('/#/dtes');
    });

    test('shows seeded DTEs from the backend', async ({ page }) => {
        await expect(
            page.getByRole('heading', {
                name: 'Documentos Tributarios Electrónicos (DTEs)',
            }),
        ).toBeVisible();

        await expect(
            page.getByText('DTE-01-M001P001-000000000000001', { exact: true }),
        ).toBeVisible();
        await expect(
            page.getByText('DTE-01-M001P001-000000000000002', { exact: true }),
        ).toBeVisible();
        await expect(
            page.getByText('DTE-01-M001P001-000000000000003', { exact: true }),
        ).toBeVisible();

        const table = page.getByRole('table');

        await expect(
            table.getByText('PROCESADO', { exact: true }),
        ).toBeVisible();
        await expect(
            table.getByText('RECHAZADO', { exact: true }),
        ).toBeVisible();
        await expect(
            table.getByText('INVALIDADO', { exact: true }),
        ).toBeVisible();
    });

    test('shows the client and every payment method in the DTE detail', async ({
        page,
    }) => {
        await page.route(/\/api\/v1\/dtes\/[^/?]+$/, async (route) => {
            const response = await route.fetch();
            const body = (await response.json()) as {
                data: {
                    original_json: Record<string, unknown>;
                };
            };

            body.data.original_json = {
                ...body.data.original_json,
                receptor: { nombre: 'CLIENTE DE LA ORDEN' },
                resumen: {
                    pagos: [
                        { codigo: '02', montoPago: 10 },
                        { codigo: '05', montoPago: 1.3 },
                    ],
                },
            };

            await route.fulfill({ response, json: body });
        });

        await page
            .getByText('DTE-01-M001P001-000000000000001', { exact: true })
            .click();

        await expect(page.getByText('Cliente', { exact: true })).toBeVisible();
        await expect(
            page.getByText('CLIENTE DE LA ORDEN', { exact: true }),
        ).toBeVisible();
        await expect(
            page.getByText('Métodos de pago', { exact: true }),
        ).toBeVisible();
        await expect(
            page.getByText('Tarjeta Débito (02) · $10.00', { exact: true }),
        ).toBeVisible();
        await expect(
            page.getByText('Transferencia-Depósito Bancario (05) · $1.30', {
                exact: true,
            }),
        ).toBeVisible();
    });

    test('filters DTEs by status', async ({ page }) => {
        await page
            .getByRole('button', { name: 'Mostrar filtros', exact: true })
            .click();
        await page.locator('#filter-estado-mh').selectOption('PROCESADO');

        await expect(
            page.getByText('DTE-01-M001P001-000000000000001', { exact: true }),
        ).toBeVisible();
        await expect(
            page.getByText('DTE-01-M001P001-000000000000002', { exact: true }),
        ).toHaveCount(0);
        await expect(
            page.getByText('DTE-01-M001P001-000000000000003', { exact: true }),
        ).toHaveCount(0);
    });

    test('refreshes the DTE list from the API', async ({ page }) => {
        const dtesResponse = page.waitForResponse(
            (response) =>
                response.request().method() === 'GET' &&
                new URL(response.url()).pathname === '/api/v1/dtes',
        );

        await page
            .getByRole('button', { name: 'Actualizar DTEs', exact: true })
            .click();

        expect((await dtesResponse).status()).toBe(200);
        await expect(
            page.getByText('DTE-01-M001P001-000000000000001', { exact: true }),
        ).toBeVisible();
    });

    test('shows an API-issued Factura 01 after refreshing the list', async ({
        page,
    }) => {
        await storeRepositoryCertificate(page);
        await expect(
            page.getByText('DTE-01-M001P001-000000000000001', { exact: true }),
        ).toBeVisible();

        const dteResponse = await page.request.post('/api/v1/dtes', {
            headers: await authorizationHeaders(page),
            data: {
                tipoDte: '01',
                receptor: {
                    tipoDocumento: '36',
                    numDocumento: '0614-280390-112-1',
                    nombre: 'CLIENTE PLAYWRIGHT FACTURA',
                    correo: 'factura.playwright@example.test',
                },
                items: [
                    {
                        descripcion: 'Servicio E2E Playwright',
                        cantidad: 2,
                        precioUni: 25,
                    },
                ],
            },
        });
        expect(dteResponse.status()).toBe(201);

        const dteGetRequests: string[] = [];
        page.on('request', (request) => {
            const url = new URL(request.url());

            if (request.method() === 'GET' && url.pathname === '/api/v1/dtes') {
                dteGetRequests.push(request.url());
            }
        });

        await page
            .getByRole('button', { name: 'Actualizar DTEs', exact: true })
            .click();

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
        expect(record.receiver_document).toBe('06142803901121');
        expect(record.total_amount).toBe(5000);
        expect(record.original_json.identificacion.fecEmi).toMatch(
            /^\d{4}-\d{2}-\d{2}$/,
        );
        expect(record.original_json.identificacion.horEmi).toMatch(
            /^\d{2}:\d{2}:\d{2}$/,
        );

        await expect(
            page.getByText(record.control_number, { exact: true }),
        ).toBeVisible();
        await expect(
            page.getByText('CLIENTE PLAYWRIGHT FACTURA', { exact: true }),
        ).toBeVisible();
        await expect(
            page.getByText('Doc: 0614-280390-112-1', { exact: true }),
        ).toBeVisible();

        const [year, month, day] =
            record.original_json.identificacion.fecEmi.split('-');
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

    test('does not offer manual DTE issuance from the list', async ({
        page,
    }) => {
        await expect(
            page.getByRole('button', { name: 'Emitir Nuevo DTE', exact: true }),
        ).toHaveCount(0);
        await expect(
            page.getByText('Emitir Documento Tributario Electrónico (DTE)'),
        ).toHaveCount(0);
        await expect(
            page.getByRole('button', { name: 'Mostrar filtros', exact: true }),
        ).toBeVisible();
        await expect(
            page.getByRole('button', { name: 'Actualizar DTEs', exact: true }),
        ).toBeVisible();
    });

    test('persists a cryptographically valid RS512 signature for the issued Factura 01', async ({
        page,
    }) => {
        await storeRepositoryCertificate(page);

        const dteResponse = await page.request.post('/api/v1/dtes', {
            headers: await authorizationHeaders(page),
            data: {
                tipoDte: '01',
                receptor: {
                    tipoDocumento: '36',
                    numDocumento: '0614-150592-101-9',
                    nombre: 'CLIENTE FIRMA PLAYWRIGHT',
                    correo: 'firma.playwright@example.test',
                },
                items: [
                    {
                        descripcion: 'Servicio firmado E2E',
                        cantidad: 1,
                        precioUni: 12.34,
                    },
                ],
            },
        });
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
        expect(payload.identificacion.numeroControl).toBe(
            record.control_number,
        );
        expect(payload.identificacion.codigoGeneracion).toBe(
            record.generation_code,
        );
        expect(payload.emisor.nit).toBe('06142812901015');
        expect(payload.receptor.nombre).toBe('CLIENTE FIRMA PLAYWRIGHT');
        expect(payload.cuerpoDocumento[0]?.descripcion).toBe(
            'Servicio firmado E2E',
        );

        const certificateXml = await readFile(testCertificatePath, 'utf8');
        const publicKeyMatch = certificateXml.match(
            /<publicKey>[\s\S]*?<encodied>([^<]+)<\/encodied>/i,
        );

        if (!publicKeyMatch) {
            throw new Error(
                'El certificado de pruebas no contiene la llave pública RSA.',
            );
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

        const persistedResponse = await page.request.get(
            `/api/v1/dtes/${record.id}`,
            {
                headers: await authorizationHeaders(page),
            },
        );
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
