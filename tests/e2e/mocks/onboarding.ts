import { faker } from '@faker-js/faker';
import type { Page } from '@playwright/test';

interface CapturedRequest {
    body: Record<string, unknown>;
    headers: Record<string, string>;
}

export interface OnboardingApiMock {
    data: {
        departmentId: string;
        municipalityId: string;
        districtId: string;
        companyId: string;
        userId: string;
        token: string;
    };
    requests: {
        register: CapturedRequest[];
        company: CapturedRequest[];
    };
    mock: (page: Page) => Promise<void>;
}

const uuid = (): string => faker.string.uuid({ version: 4 });

export function createOnboardingApiMock(): OnboardingApiMock {
    const data = {
        departmentId: uuid(),
        municipalityId: uuid(),
        districtId: uuid(),
        companyId: uuid(),
        userId: uuid(),
        token: faker.string.alphanumeric(40),
    };

    const requests = {
        register: [] as CapturedRequest[],
        company: [] as CapturedRequest[],
    };

    const catalogs: Record<string, object[]> = {
        '/api/v1/data/economic-activities': [
            { id: uuid(), code: '62010', description: 'Programación informática' },
        ],
        '/api/v1/data/establishment-types': [
            { id: uuid(), code: '01', description: 'Sucursal / Agencia' },
        ],
        '/api/v1/data/departments': [
            { id: data.departmentId, code: '06', name: 'San Salvador' },
        ],
        '/api/v1/data/municipalities': [
            {
                id: data.municipalityId,
                departament_id: data.departmentId,
                departament_code: '06',
                code: '01',
                name: 'San Salvador Centro',
            },
        ],
        '/api/v1/data/districts': [
            {
                id: data.districtId,
                departament_id: data.departmentId,
                municipality_id: data.municipalityId,
                code: '01',
                name: 'San Salvador',
            },
        ],
    };

    const mock = async (page: Page): Promise<void> => {
        await page.route('**/api/v1/data/**', async (route) => {
            const pathname = new URL(route.request().url()).pathname;

            await route.fulfill({
                status: 200,
                contentType: 'application/json',
                body: JSON.stringify({ data: catalogs[pathname] ?? [] }),
            });
        });

        await page.route('**/api/v1/register', async (route) => {
            const request = route.request();

            requests.register.push({
                body: request.postDataJSON() as Record<string, unknown>,
                headers: request.headers(),
            });

            await route.fulfill({
                status: 201,
                contentType: 'application/json',
                body: JSON.stringify({
                    data: {
                        token: data.token,
                        user: {
                            id: data.userId,
                            company_id: null,
                            role: 'admin',
                            phone: null,
                            name: 'Kevin Mendez',
                            email: 'kevin@rutely.biz',
                            created_at: '2026-08-19T00:00:00.000000Z',
                            updated_at: '2026-08-19T00:00:00.000000Z',
                        },
                    },
                }),
            });
        });

        await page.route('**/api/v1/companies', async (route) => {
            const request = route.request();

            requests.company.push({
                body: request.postDataJSON() as Record<string, unknown>,
                headers: request.headers(),
            });

            await route.fulfill({
                status: 201,
                contentType: 'application/json',
                body: JSON.stringify({
                    data: {
                        id: data.companyId,
                    },
                }),
            });
        });
    };

    return {
        data,
        requests,
        mock,
    };
}
