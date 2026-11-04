import { faker } from '@faker-js/faker';
import { expect, test } from '@playwright/test';
import type { Page } from '@playwright/test';
import { authenticateDeveloper } from './support/auth';

type MhLoginStatus = 'authenticated' | 'rejected' | 'unverified';

const REJECTED_MESSAGE =
    'Hacienda rechazó el NIT o la contraseña. Las credenciales no se guardaron.';
const UNAVAILABLE_MESSAGE =
    'No se pudo contactar a Hacienda para comprobar el login. Las credenciales no se guardaron; inténtelo de nuevo.';
const FORBIDDEN_NIT_MESSAGE =
    'No está autorizado para registrar credenciales con este NIT.';

function storedCredentials(loginStatus: MhLoginStatus): Record<string, unknown> {
    return {
        id: faker.string.uuid(),
        environment: '00',
        nit: '06142803901121',
        active: true,
        login_status: loginStatus,
        updated_at: new Date().toISOString(),
    };
}

// The API is mocked because storing credentials logs in to Hacienda for real.
// What the backend stores and answers is covered by MhConfigurationTest.
async function mockMhCredentialsApi(
    page: Page,
    {
        failedSaves = [],
        stored = null,
    }: {
        failedSaves?: Array<{ status: number; message: string }>;
        stored?: Record<string, unknown> | null;
    } = {},
): Promise<void> {
    const session = JSON.parse(process.env.E2E_SESSION ?? 'null') as {
        user: Record<string, unknown>;
    };
    let credentials = stored;

    await page.route('**/api/user', async (route) => {
        await route.fulfill({ json: session.user });
    });

    await page.route('**/api/v1/mh-credentials*', async (route) => {
        if (route.request().method() === 'POST') {
            const failedSave = failedSaves.shift();

            if (failedSave) {
                await route.fulfill({
                    status: failedSave.status,
                    json: { message: failedSave.message },
                });

                return;
            }

            credentials = storedCredentials('authenticated');
        }

        if (credentials === null) {
            await route.fulfill({
                status: 404,
                json: { message: 'No encontrado.' },
            });

            return;
        }

        await route.fulfill({ json: { data: credentials } });
    });
}

async function fillAndSave(page: Page, password: string): Promise<void> {
    await page.locator('#mh-nit').fill('06142803901121');
    await page.locator('#mh-password').fill(password);
    await page.getByRole('button', { name: 'Guardar Credenciales MH' }).click();
}

test.describe('MH credentials', () => {
    test('stores credentials once and reloads the metadata without duplicate requests', async ({ page }) => {
        await authenticateDeveloper(page);
        await mockMhCredentialsApi(page);

        const credentialsRequests: Array<{
            method: string;
            url: string;
            postData: unknown;
        }> = [];

        page.on('request', (request) => {
            const url = new URL(request.url());

            if (url.pathname === '/api/v1/mh-credentials') {
                credentialsRequests.push({
                    method: request.method(),
                    url: request.url(),
                    postData: request.postDataJSON(),
                });
            }
        });

        await page.goto('/#/mh-credentials');

        await expect(
            page.getByRole('heading', { name: 'Credenciales de Autenticación (MH)' }),
        ).toBeVisible();
        await expect(page.getByText('Sin Credenciales Registradas', { exact: true })).toBeVisible();
        await page.waitForLoadState('networkidle');

        const initialGetRequests = credentialsRequests.filter(
            (request) => request.method === 'GET',
        );
        expect(initialGetRequests).toHaveLength(1);

        const initialGetUrl = new URL(initialGetRequests[0].url);
        expect(initialGetUrl.searchParams.get('environment')).toBe('00');

        const nitInput = page.locator('#mh-nit');
        await nitInput.fill('06142803901121');
        await expect(nitInput).toHaveValue('0614-280390-112-1');

        const saveButton = page.getByRole('button', { name: 'Guardar Credenciales MH' });

        await page.locator('#mh-password').fill('Playwright-secret-01');

        const credentialsResponsePromise = page.waitForResponse(
            (response) =>
                response.request().method() === 'POST' &&
                new URL(response.url()).pathname === '/api/v1/mh-credentials',
        );

        await saveButton.click();

        const credentialsResponse = await credentialsResponsePromise;
        expect(credentialsResponse.status()).toBe(200);
        await page.waitForLoadState('networkidle');

        const postRequests = credentialsRequests.filter(
            (request) => request.method === 'POST',
        );
        expect(postRequests).toHaveLength(1);
        expect(postRequests[0].postData).toEqual({
            environment: '00',
            nit: '0614-280390-112-1',
            pwd: 'Playwright-secret-01',
        });

        // Saving updates the UI from the POST response; it must not trigger
        // an unnecessary second GET for the same credentials.
        expect(
            credentialsRequests.filter((request) => request.method === 'GET'),
        ).toHaveLength(1);

        await expect(page.getByText('ACTIVO', { exact: true })).toBeVisible();
        await expect(page.getByText('0614-280390-112-1', { exact: true })).toBeVisible();
        await expect(page.getByText(/Login exitoso en Hacienda/)).toBeVisible();
        await expect(page.getByText('Exitoso', { exact: true })).toBeVisible();
        await expect(page.locator('#mh-nit')).toHaveValue('');
        await expect(page.locator('#mh-password')).toHaveValue('');

        credentialsRequests.length = 0;
        await page.reload();

        await expect(
            page.getByRole('heading', { name: 'Credenciales de Autenticación (MH)' }),
        ).toBeVisible();
        await expect(page.getByText('ACTIVO', { exact: true })).toBeVisible();
        await expect(page.getByText('0614-280390-112-1', { exact: true })).toBeVisible();
        await page.waitForLoadState('networkidle');

        const reloadGetRequests = credentialsRequests.filter(
            (request) => request.method === 'GET',
        );
        expect(reloadGetRequests).toHaveLength(1);
        expect(
            credentialsRequests.filter((request) => request.method === 'POST'),
        ).toHaveLength(0);

        const reloadGetUrl = new URL(reloadGetRequests[0].url);
        expect(reloadGetUrl.searchParams.get('environment')).toBe('00');
    });

    test('shows the error of a rejected login, stores nothing and lets the password be corrected', async ({ page }) => {
        await authenticateDeveloper(page);
        await mockMhCredentialsApi(page, {
            failedSaves: [{ status: 422, message: REJECTED_MESSAGE }],
        });

        await page.goto('/#/mh-credentials');
        await fillAndSave(page, 'Playwright-wrong-01');

        await expect(page.getByText(REJECTED_MESSAGE)).toBeVisible();
        await expect(page.getByText('Sin Credenciales Registradas', { exact: true })).toBeVisible();
        await expect(page.locator('#mh-nit')).toHaveValue('0614-280390-112-1');
        await expect(page.locator('#mh-password')).toHaveValue('Playwright-wrong-01');

        await page.locator('#mh-password').fill('Playwright-right-02');
        await page.getByRole('button', { name: 'Guardar Credenciales MH' }).click();

        await expect(page.getByText(/Login exitoso en Hacienda/)).toBeVisible();
        await expect(page.getByText(REJECTED_MESSAGE)).toHaveCount(0);
        await expect(page.getByText('Exitoso', { exact: true })).toBeVisible();
        await expect(page.locator('#mh-nit')).toHaveValue('');
        await expect(page.locator('#mh-password')).toHaveValue('');
    });

    test('shows the error when Hacienda is unavailable and keeps the stored credentials', async ({ page }) => {
        await authenticateDeveloper(page);
        await mockMhCredentialsApi(page, {
            failedSaves: [{ status: 503, message: UNAVAILABLE_MESSAGE }],
            stored: storedCredentials('authenticated'),
        });

        await page.goto('/#/mh-credentials');
        await fillAndSave(page, 'Playwright-secret-01');

        await expect(page.getByText(UNAVAILABLE_MESSAGE)).toBeVisible();
        await expect(page.getByText(/Login exitoso en Hacienda/)).toHaveCount(0);
        await expect(page.getByText('Exitoso', { exact: true })).toBeVisible();
        await expect(page.locator('#mh-password')).toHaveValue('Playwright-secret-01');
    });

    test('shows the error when the NIT belongs to another company and stores nothing', async ({ page }) => {
        await authenticateDeveloper(page);
        await mockMhCredentialsApi(page, {
            failedSaves: [{ status: 403, message: FORBIDDEN_NIT_MESSAGE }],
        });

        await page.goto('/#/mh-credentials');
        await fillAndSave(page, 'Playwright-secret-01');

        await expect(page.getByText(FORBIDDEN_NIT_MESSAGE)).toBeVisible();
        await expect(page.getByText('Sin Credenciales Registradas', { exact: true })).toBeVisible();
    });

    const loginStates: Array<{
        loginStatus: MhLoginStatus;
        badge: string;
        note: RegExp;
    }> = [
        {
            loginStatus: 'authenticated',
            badge: 'Exitoso',
            note: /Hacienda aceptó estas credenciales/,
        },
        {
            loginStatus: 'rejected',
            badge: 'Rechazado',
            note: /Hacienda rechazó estas credenciales/,
        },
        {
            loginStatus: 'unverified',
            badge: 'Sin verificar',
            note: /No hay un login reciente/,
        },
    ];

    for (const { loginStatus, badge, note } of loginStates) {
        test(`shows the stored credentials as ${loginStatus}`, async ({ page }) => {
            await authenticateDeveloper(page);
            await mockMhCredentialsApi(page, {
                stored: storedCredentials(loginStatus),
            });

            await page.goto('/#/mh-credentials');

            await expect(page.getByText(badge, { exact: true })).toBeVisible();
            await expect(page.getByText(note)).toBeVisible();
        });
    }
});
