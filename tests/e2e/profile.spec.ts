import { expect, test } from '@playwright/test';
import { authenticateDeveloper } from './support/auth';

test.describe('My profile', () => {
    test('shows the authenticated developer profile', async ({ page }) => {
        await authenticateDeveloper(page);
        await page.goto('/#/my-profile');

        const main = page.getByRole('main');

        await expect(page.getByRole('heading', { name: 'Mi Perfil' })).toBeVisible();
        await expect(main.getByText('Playwright User', { exact: true })).toBeVisible();
        await expect(main.getByText('Admin', { exact: true })).toBeVisible();
        await expect(main.getByText('playwright@rutely.biz', { exact: true })).toBeVisible();
    });
});
