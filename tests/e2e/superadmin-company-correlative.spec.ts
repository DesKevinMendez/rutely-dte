import { expect, test } from '@playwright/test';
import { createLoginApiMock } from './mocks/login';

test('superadmin opens the DTE correlative section and updates the next number', async ({
    page,
}) => {
    const auth = createLoginApiMock('superadmin');
    const companyId = '7ea98ad4-d5f4-41ca-bec9-014521e30c7f';
    let nextValue = 125;
    let savedValue: number | null = null;

    await page.addInitScript(({ token, user }) => {
        localStorage.setItem('auth_token', token);
        localStorage.setItem('auth_user', JSON.stringify(user));
    }, auth.data);
    await page.route('**/api/user', (route) =>
        route.fulfill({ json: auth.data.user }),
    );
    await page.route(`**/api/v1/superadmin/companies/${companyId}`, (route) =>
        route.fulfill({
            json: {
                data: {
                    id: companyId,
                    name: 'Empresa de pruebas',
                    nit: '06142812901015',
                },
            },
        }),
    );
    await page.route(
        `**/api/v1/superadmin/companies/${companyId}/dte-correlative`,
        (route) => {
            if (route.request().method() === 'PATCH') {
                savedValue = Number(route.request().postDataJSON().next_value);
                nextValue = savedValue;
            }

            return route.fulfill({
                json: { data: { year: 2026, next_value: nextValue } },
            });
        },
    );

    await page.goto(`/#/superadmin/companies/${companyId}/profile`);
    await expect(page.getByText('Información de la empresa')).toBeVisible();
    await expect(page.getByLabel('Próximo correlativo DTE')).toHaveCount(0);
    await page.getByRole('link', { name: 'Correlativo DTE' }).click();

    await expect(page).toHaveURL(
        new RegExp(`/companies/${companyId}/dte-correlative$`),
    );
    await expect(
        page.getByText('El correlativo es el número consecutivo', {
            exact: false,
        }),
    ).toBeVisible();
    const input = page.getByLabel('Próximo correlativo DTE');
    await expect(input).toHaveValue('125');
    await expect(input).toBeVisible();
    await expect(input).toBeEditable();
    await input.fill('90');
    await page.getByRole('button', { name: 'Guardar correlativo' }).click();
    await expect(page.getByText('Correlativo actualizado.')).toBeVisible();
    expect(savedValue).toBe(90);
});
