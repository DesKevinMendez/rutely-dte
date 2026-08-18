import { expect, test } from '@playwright/test';

const isLoginRequest = (method: string, url: string): boolean =>
    method === 'POST' && new URL(url).pathname === '/api/v1/login';

test.describe('Login validation', () => {
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
});
