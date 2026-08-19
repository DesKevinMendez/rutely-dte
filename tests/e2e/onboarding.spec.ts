import { expect, test } from '@playwright/test';
import type { Page } from '@playwright/test';
import { createOnboardingApiMock } from './mocks/onboarding';

const requiredMessages = [
    'El nombre del administrador es requerido.',
    'El correo electrónico del administrador es requerido.',
    'La contraseña es requerida.',
    'La confirmación de contraseña es requerida.',
    'La razón social es requerida.',
    'El nombre comercial es requerido.',
    'El NIT es requerido.',
    'La actividad económica es requerida.',
    'El tipo de establecimiento es requerido.',
    'El teléfono es requerido.',
    'El correo electrónico es requerido.',
    'La dirección es requerida.',
    'El departamento es requerido.',
    'El municipio es requerido.',
    'El distrito es requerido.',
    'El código de establecimiento es requerido.',
    'El código de punto de venta es requerido.',
];

async function selectSearchableOption(
    page: Page,
    fieldId: string,
    option: string,
    search?: string,
): Promise<void> {
    const input = page.locator(`#${fieldId}`);
    const searchableSelect = input.locator('xpath=../..');

    await expect(input).toBeEnabled();
    await input.click();

    if (search) {
        await input.fill(search);
    }

    const optionContainer = searchableSelect
        .locator('.cursor-pointer')
        .filter({ hasText: option })
        .first();

    await expect(optionContainer).toBeVisible();
    await optionContainer.click();
    await expect(input).toHaveValue(option);
}

test.describe('Onboarding', () => {
    test('validates required fields and email formats before sending requests', async ({ page }) => {
        const api = createOnboardingApiMock();
        await api.mock(page);
        await page.goto('/#/onboarding');

        await page.getByRole('button', { name: 'Continuar' }).click();

        for (const message of requiredMessages) {
            await expect(page.getByText(message, { exact: true })).toBeVisible();
        }

        expect(api.requests.register).toHaveLength(0);
        expect(api.requests.company).toHaveLength(0);

        await page.locator('#userEmail').fill('correo-invalido');
        await page.locator('#email').fill('correo-invalido');
        await page.getByRole('button', { name: 'Continuar' }).click();

        await expect(
            page.getByText('Ingresá un correo electrónico válido.', { exact: true }),
        ).toHaveCount(2);

        expect(api.requests.register).toHaveLength(0);
        expect(api.requests.company).toHaveLength(0);
    });

    test('sends each onboarding request once with the correct payload and shows the dashboard', async ({ page }) => {
        const api = createOnboardingApiMock();
        await api.mock(page);
        await page.goto('/#/onboarding');

        await page.locator('#userName').fill('Kevin Mendez');
        await page.locator('#userEmail').fill('kevin@rutely.biz');
        await page.locator('#password').fill('secret123');
        await page.locator('#passwordConfirmation').fill('secret123');
        await page.locator('#name').fill('Rutely, S.A. de C.V.');
        await page.locator('#commercialName').fill('Rutely');
        await page.locator('#nit').fill('06142812901015');

        await selectSearchableOption(
            page,
            'onboarding-economic-activity-select',
            '62010 - Programación informática',
            'Programación',
        );
        await selectSearchableOption(
            page,
            'onboarding-establishment-type-select',
            'Sucursal / Agencia',
        );

        await page.locator('#phone').fill('78027600');
        await page.locator('#email').fill('billing@rutely.biz');
        await page.locator('#address').fill('San Salvador, El Salvador');

        const municipality = page.locator('#onboarding-municipality-select');
        const district = page.locator('#onboarding-district-select');

        await expect(municipality).toBeDisabled();
        await expect(district).toBeDisabled();

        await selectSearchableOption(
            page,
            'onboarding-department-select',
            'San Salvador',
        );

        await expect(municipality).toBeEnabled();
        await expect(district).toBeDisabled();

        await selectSearchableOption(
            page,
            'onboarding-municipality-select',
            'San Salvador Centro',
        );

        await expect(district).toBeEnabled();

        await selectSearchableOption(
            page,
            'onboarding-district-select',
            'San Salvador',
        );

        await page.locator('#ownEstablishmentCode').fill('M001');
        await page.locator('#ownPosCode').fill('P001');

        await page.getByRole('button', { name: 'Continuar' }).click();

        await expect(page).toHaveURL(/\/#\/dashboard$/);
        await expect(
            page.getByRole('heading', { name: 'Resumen de Facturación Electrónica' }),
        ).toBeVisible();

        expect(api.requests.register).toHaveLength(1);
        expect(api.requests.company).toHaveLength(1);

        expect(api.requests.register[0]?.body).toEqual({
            name: 'Kevin Mendez',
            email: 'kevin@rutely.biz',
            phone: null,
            password: 'secret123',
            password_confirmation: 'secret123',
        });

        expect(api.requests.company[0]?.body).toEqual({
            name: 'Rutely, S.A. de C.V.',
            address: 'San Salvador, El Salvador',
            phone: '+503 7802 7600',
            nit: '06142812901015',
            nrc: null,
            commercial_name: 'Rutely',
            economic_activity_code: '62010',
            establishment_type: '01',
            departament_id: api.data.departmentId,
            municipality_id: api.data.municipalityId,
            district_id: api.data.districtId,
            email: 'billing@rutely.biz',
            mh_establishment_code: 'M001',
            mh_pos_code: 'P001',
            own_establishment_code: 'M001',
            own_pos_code: 'P001',
        });

        expect(api.requests.company[0]?.headers.authorization).toBe(`Bearer ${api.data.token}`);
    });
});
