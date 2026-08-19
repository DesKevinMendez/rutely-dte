import { faker } from '@faker-js/faker';
import type { Page } from '@playwright/test';

interface LoginUser {
    id: string;
    company_id: string;
    role: string;
    phone: string | null;
    name: string;
    email: string;
    email_verified_at: string | null;
    created_at: string;
    updated_at: string;
}

interface CapturedLoginRequest {
    body: Record<string, unknown>;
}

export interface LoginApiMock {
    data: {
        token: string;
        user: LoginUser;
    };
    requests: {
        login: CapturedLoginRequest[];
    };
    mock: (page: Page) => Promise<void>;
}

export function createLoginApiMock(): LoginApiMock {
    const data = {
        token: faker.string.alphanumeric(40),
        user: {
            id: faker.string.uuid({ version: 4 }),
            company_id: faker.string.uuid({ version: 4 }),
            role: 'admin',
            phone: null,
            name: 'Kevin Mendez',
            email: 'kevin@rutely.biz',
            email_verified_at: null,
            created_at: '2026-08-19T00:00:00.000000Z',
            updated_at: '2026-08-19T00:00:00.000000Z',
        },
    };

    const requests = {
        login: [] as CapturedLoginRequest[],
    };

    const mock = async (page: Page): Promise<void> => {
        await page.route('**/api/v1/login', async (route) => {
            requests.login.push({
                body: route.request().postDataJSON() as Record<string, unknown>,
            });

            await route.fulfill({
                status: 200,
                contentType: 'application/json',
                body: JSON.stringify({ data }),
            });
        });

        await page.route('**/api/v1/dashboard', async (route) => {
            await route.fulfill({
                status: 200,
                contentType: 'application/json',
                body: JSON.stringify({
                    data: {
                        metrics: {
                            total: 0,
                            processed: 0,
                            rejected: 0,
                            invalidated: 0,
                            total_amount: 0,
                            pending_transmissions: 0,
                        },
                        recent_dtes: [],
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
