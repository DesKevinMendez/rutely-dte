import { expect, test } from '@playwright/test';
import { createLoginApiMock } from './mocks/login';

test.describe('Superadmin company API tokens', () => {
    test('revokes a token after confirmation', async ({ page }) => {
        const auth = createLoginApiMock('superadmin');
        const companyId = '7ea98ad4-d5f4-41ca-bec9-014521e30c7f';
        const token = {
            id: 42,
            name: 'ERP de pruebas',
            abilities: ['list:dte'],
            token_preview: '42|ab***1234',
            last_used_at: null,
            created_at: '2026-10-23T10:00:00.000000Z',
        };
        let tokens = [token];
        let deleteRequests = 0;

        await page.addInitScript(({ token, user }) => {
            localStorage.setItem('auth_token', token);
            localStorage.setItem('auth_user', JSON.stringify(user));
        }, auth.data);

        await page.route('**/api/user', async (route) => {
            await route.fulfill({
                status: 200,
                contentType: 'application/json',
                body: JSON.stringify(auth.data.user),
            });
        });

        await page.route(
            '**/api/v1/superadmin/companies/**/tokens**',
            async (route) => {
                const request = route.request();
                const pathname = new URL(request.url()).pathname;

                if (request.method() === 'GET') {
                    await route.fulfill({
                        status: 200,
                        contentType: 'application/json',
                        body: JSON.stringify({
                            data: tokens,
                            pagination: {
                                total: tokens.length,
                                per_page: 15,
                                current_page: 1,
                                last_page: 1,
                                from: tokens.length > 0 ? 1 : null,
                                to: tokens.length,
                            },
                        }),
                    });

                    return;
                }

                if (
                    request.method() === 'DELETE' &&
                    pathname.endsWith(`/tokens/${token.id}`)
                ) {
                    deleteRequests += 1;
                    tokens = [];
                    await route.fulfill({ status: 202, body: '' });

                    return;
                }

                await route.continue();
            },
        );

        await page.goto(`/#/superadmin/companies/${companyId}/tokens`);

        const tokenRow = page.getByRole('row').filter({ hasText: token.name });
        await expect(tokenRow).toBeVisible();
        await tokenRow
            .getByRole('button', { name: `Revocar token ${token.name}` })
            .click();

        await expect(
            page.getByRole('heading', { name: 'Revocar token de API' }),
        ).toBeVisible();
        expect(deleteRequests).toBe(0);

        const confirmationInput = page.getByRole('textbox', {
            name: 'Confirmación',
        });
        const confirmButton = page.getByRole('button', {
            name: 'Confirmar',
            exact: true,
        });

        await expect(confirmButton).toBeDisabled();
        await confirmationInput.fill('Confirmar');
        await expect(confirmButton).toBeEnabled();

        const deleteResponsePromise = page.waitForResponse(
            (response) =>
                response.request().method() === 'DELETE' &&
                new URL(response.url()).pathname.endsWith(
                    `/tokens/${token.id}`,
                ),
        );
        await confirmButton.click();

        const deleteResponse = await deleteResponsePromise;
        expect(deleteResponse.status()).toBe(202);
        expect(await deleteResponse.text()).toBe('');
        await expect(tokenRow).toHaveCount(0);
    });
});
