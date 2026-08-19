import { expect, test } from '@playwright/test';

const isRegisterRequest = (method: string, url: string): boolean =>
    method === 'POST' && new URL(url).pathname === '/api/v1/register';

const isCompanyRequest = (method: string, url: string): boolean =>
    method === 'POST' && new URL(url).pathname === '/api/v1/companies';

test.describe('Onboarding registration validation', () => {
    test.beforeEach(async ({ page }) => {
        await page.goto('/#/onboarding');
    });

    test('does not contact the server when required registration data is empty', async ({ page }) => {
        let registerRequests = 0;
        let companyRequests = 0;

        page.on('request', (request) => {
            if (isRegisterRequest(request.method(), request.url())) {
                registerRequests += 1;
            }

            if (isCompanyRequest(request.method(), request.url())) {
                companyRequests += 1;
            }
        });

        await page.getByRole('button', { name: 'Continuar' }).click();

        await expect(page.getByText('El nombre del administrador es requerido.')).toBeVisible();
        await expect(page.getByText('La contraseña es requerida.')).toBeVisible();
        expect(registerRequests).toBe(0);
        expect(companyRequests).toBe(0);
    });
});
