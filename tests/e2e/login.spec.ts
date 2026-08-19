import { expect, test } from '@playwright/test';
import { createLoginApiMock } from './mocks/login';

const isLoginRequest = (method: string, url: string): boolean =>
    method === 'POST' && new URL(url).pathname === '/api/v1/login';

test.describe('Login', () => {
    test.beforeEach(async ({ page }) => {
        await page.goto('/#/login');
    });

    test('does not contact the server when credentials are empty', async ({ page }) => {
        let loginRequests = 0;

        page.on('request', (request) => {
            if (isLoginRequest(request.method(), request.url())) {
                loginRequests += 1;
            }
        });

        await page.getByRole('button', { name: 'Acceder al Sistema' }).click();

        await expect(page.getByText('El correo electrónico es requerido.')).toBeVisible();
        await expect(page.getByText('La contraseña es requerida.')).toBeVisible();
        expect(loginRequests).toBe(0);
    });

    test('does not contact the server when the email is invalid', async ({ page }) => {
        let loginRequests = 0;

        page.on('request', (request) => {
            if (isLoginRequest(request.method(), request.url())) {
                loginRequests += 1;
            }
        });

        await page.locator('#email').fill('correo-invalido');
        await page.locator('#password').fill('secret123');
        await page.getByRole('button', { name: 'Acceder al Sistema' }).click();

        await expect(page.getByText('Ingresá un correo electrónico válido.')).toBeVisible();
        expect(loginRequests).toBe(0);
    });

    test('logs in once, stores the session and shows the dashboard', async ({ page }) => {
        const api = createLoginApiMock();
        await api.mock(page);

        await page.locator('#email').fill('kevin@rutely.biz');
        await page.locator('#password').fill('secret123');
        await page.getByRole('button', { name: 'Acceder al Sistema' }).click();

        await expect(page).toHaveURL(/\/#\/dashboard$/);
        await expect(
            page.getByRole('heading', { name: 'Resumen de Facturación Electrónica' }),
        ).toBeVisible();

        expect(api.requests.login).toHaveLength(1);
        expect(api.requests.login[0]?.body).toEqual({
            email: 'kevin@rutely.biz',
            password: 'secret123',
            device_name: 'rutely-dte-web',
        });

        const session = await page.evaluate(() => ({
            token: localStorage.getItem('auth_token'),
            user: JSON.parse(localStorage.getItem('auth_user') ?? 'null'),
        }));

        expect(session).toEqual({
            token: api.data.token,
            user: api.data.user,
        });
    });
});
