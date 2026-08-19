import { expect, test } from '@playwright/test';

test.describe('Password recovery', () => {
    test.beforeEach(async ({ page }) => {
        await page.goto('/#/recovery');
    });

    test('validates the recovery email', async ({ page }) => {
        await page.getByRole('button', { name: 'Enviar Instrucciones' }).click();
        await expect(page.getByText('El correo electrónico es requerido.')).toBeVisible();

        await page.locator('#recovery-email').fill('correo-invalido');
        await page.getByRole('button', { name: 'Enviar Instrucciones' }).click();
        await expect(page.getByText('Ingresá un correo electrónico válido.')).toBeVisible();
    });

    test('confirms recovery instructions for a valid email', async ({ page }) => {
        await expect(page.getByRole('heading', { name: 'Recuperar Contraseña' })).toBeVisible();

        await page.locator('#recovery-email').fill('playwright@rutely.biz');
        await page.getByRole('button', { name: 'Enviar Instrucciones' }).click();

        await expect(
            page.getByText(
                'Las instrucciones de recuperación se enviarán a playwright@rutely.biz.',
                { exact: true },
            ),
        ).toBeVisible();
    });
});
