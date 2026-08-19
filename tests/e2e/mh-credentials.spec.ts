import { expect, test } from '@playwright/test';
import { authenticateDeveloper } from './support/auth';

test.describe('MH credentials', () => {
    test('stores fictitious credentials once and reloads persisted metadata without duplicate requests', async ({ page }) => {
        await authenticateDeveloper(page);

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

        await page.locator('#mh-password').fill('playwright-secret');

        const credentialsResponsePromise = page.waitForResponse(
            (response) =>
                response.request().method() === 'POST' &&
                new URL(response.url()).pathname === '/api/v1/mh-credentials',
        );

        await page.getByRole('button', { name: 'Guardar Credenciales MH' }).click();

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
            pwd: 'playwright-secret',
        });

        // Saving updates the UI from the POST response; it must not trigger
        // an unnecessary second GET for the same credentials.
        expect(
            credentialsRequests.filter((request) => request.method === 'GET'),
        ).toHaveLength(1);

        await expect(page.getByText('ACTIVO', { exact: true })).toBeVisible();
        await expect(page.getByText('0614-280390-112-1', { exact: true })).toBeVisible();
        await expect(page.getByText(/Credenciales guardadas correctamente/)).toBeVisible();

        // Reload to prove the metadata was persisted in the database rather
        // than only being kept in the current Vue state.
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
});
