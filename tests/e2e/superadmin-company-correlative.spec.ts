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
                    mh_establishment_code: 'S005',
                    mh_pos_code: 'P007',
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
    const saveButton = page.getByRole('button', {
        name: 'Guardar correlativo',
    });
    const updateSwitch = page
        .getByRole('group', { name: 'Actualizar correlativo' })
        .getByRole('button');
    await expect(input).toHaveValue('125');
    await expect(input).toBeVisible();
    await expect(input).toBeDisabled();
    await expect(
        page.getByText('DTE-01-S005P007-000000000000125'),
    ).toBeVisible();
    await expect(saveButton).toBeDisabled();
    await updateSwitch.click();
    await expect(input).toBeEditable();
    await expect(saveButton).toBeEnabled();
    await input.clear();
    await expect(page.getByText('Para una factura (01)')).toHaveCount(0);
    await input.fill('90');
    await expect(
        page.getByText('DTE-01-S005P007-000000000000090'),
    ).toBeVisible();
    await updateSwitch.click();
    await expect(input).toBeDisabled();
    await expect(saveButton).toBeDisabled();
    await updateSwitch.click();
    await saveButton.click();

    const confirmation = page.getByRole('dialog');
    await expect(confirmation).toContainText('el correlativo 90');
    expect(savedValue).toBeNull();
    await confirmation.getByRole('button', { name: 'Cancelar' }).click();
    await expect(confirmation).not.toBeVisible();
    expect(savedValue).toBeNull();

    await saveButton.click();
    await confirmation
        .getByRole('textbox', { name: 'Confirmación' })
        .fill('Confirmar');
    await confirmation
        .getByRole('button', { name: 'Confirmar', exact: true })
        .click();
    await expect(page.getByText('Correlativo actualizado.')).toBeVisible();
    await expect(input).toBeDisabled();
    await expect(saveButton).toBeDisabled();
    expect(savedValue).toBe(90);
});
