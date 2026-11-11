import { expect, test } from '@playwright/test';
import type { Page } from '@playwright/test';
import { authenticateDeveloper } from './support/auth';

type QueueResponse = {
    data: Array<{
        status: string;
    }>;
};

const isQueueResponse = (url: string, method = 'GET') => {
    const parsedUrl = new URL(url);

    return method === 'GET' && parsedUrl.pathname === '/api/v1/queue';
};

const failedCount = (response: QueueResponse) =>
    response.data.filter((transmission) => transmission.status === 'failed').length;

async function expectRetryButtonState(page: Page, count: number) {
    const retryButton = page.getByRole('button', {
        name: `Reintentar fallidos (${count})`,
    });

    await expect(retryButton).toBeVisible();

    if (count === 0) {
        await expect(retryButton).toBeDisabled();

        return;
    }

    await expect(retryButton).toBeEnabled();
}

async function openQueue(page: Page): Promise<QueueResponse> {
    const queueResponsePromise = page.waitForResponse((response) =>
        isQueueResponse(response.url(), response.request().method()),
    );

    await page.goto('/#/queue');

    const queueResponse = await queueResponsePromise;
    expect(queueResponse.status()).toBe(200);

    return (await queueResponse.json()) as QueueResponse;
}

const failedDteId = '01a1c77f-d82b-70e6-8ff0-4f4a2c6d9b11';

const transmission = (overrides: Record<string, unknown> = {}) => ({
    id: '01a1c77f-d83a-73c9-8bf2-1b2c3d4e5f60',
    transmittable_type: 'App\\Models\\Dte',
    transmittable_id: failedDteId,
    operation: 'dte',
    attempt: 1,
    http_status: 400,
    status: 'failed',
    error: 'Error en transmisión MH [HTTP 400]: primer intento',
    sent_at: '2026-11-11T15:03:24.000000Z',
    responded_at: '2026-11-11T15:03:25.000000Z',
    created_at: '2026-11-11T15:03:24.000000Z',
    ...overrides,
});

const queuePage = (rows: unknown[]) => ({
    data: rows,
    pagination: {
        total: rows.length,
        per_page: 50,
        current_page: 1,
        last_page: 1,
        from: rows.length > 0 ? 1 : null,
        to: rows.length > 0 ? rows.length : null,
    },
});

async function mockQueue(page: Page, rows: () => unknown[]): Promise<void> {
    await page.route(/\/api\/v1\/queue(\?|$)/, async (route) => {
        await route.fulfill({ json: queuePage(rows()) });
    });
}

async function openTransmissionDetails(page: Page, dteId: string): Promise<void> {
    await page.goto('/#/queue');
    await page.getByRole('row').filter({ hasText: dteId }).click();
    await expect(page.getByText('Información de Hacienda')).toBeVisible();
}

test.describe('Queue', () => {
    test.beforeEach(async ({ page }) => {
        await authenticateDeveloper(page);
    });

    test('shows the transmission queue state from the backend', async ({ page }) => {
        const queue = await openQueue(page);

        await expect(
            page.getByRole('heading', { name: 'Cola de Reintentos de Transmisión (Background Jobs)' }),
        ).toBeVisible();

        await expectRetryButtonState(page, failedCount(queue));
    });

    test('refreshes the transmission queue from the API', async ({ page }) => {
        await openQueue(page);

        const queueResponsePromise = page.waitForResponse((response) =>
            isQueueResponse(response.url(), response.request().method()),
        );

        await page.getByRole('button', { name: 'Actualizar Cola' }).click();

        const queueResponse = await queueResponsePromise;
        expect(queueResponse.status()).toBe(200);

        const queue = (await queueResponse.json()) as QueueResponse;
        await expectRetryButtonState(page, failedCount(queue));
    });

    test('retries one failed DTE and drops it from the queue once Hacienda accepts it', async ({
        page,
    }) => {
        let rows: unknown[] = [transmission()];
        await mockQueue(page, () => rows);
        await page.route(`**/api/v1/dtes/${failedDteId}/retries`, async (route) => {
            rows = [];
            await route.fulfill({
                json: {
                    data: {
                        record: { id: failedDteId, status: 'PROCESADO' },
                        mh_result: { estado: 'PROCESADO', selloRecibido: 'SELLO-REINTENTO' },
                    },
                },
            });
        });

        await openTransmissionDetails(page, failedDteId);

        const retryRequest = page.waitForRequest(
            (request) =>
                request.method() === 'POST' &&
                new URL(request.url()).pathname === `/api/v1/dtes/${failedDteId}/retries`,
        );
        await page.getByRole('button', { name: 'Reintentar este DTE' }).click();
        await retryRequest;

        await expect(page.getByText('Hacienda aceptó el DTE. Ya salió de la cola.')).toBeVisible();
        await expect(page.getByRole('button', { name: 'Reintentar este DTE' })).toBeHidden();
        await expect(page.getByRole('row').filter({ hasText: failedDteId })).toHaveCount(0);
    });

    test('keeps the DTE open with its new attempt when Hacienda rejects it again', async ({
        page,
    }) => {
        let rows: unknown[] = [transmission()];
        await mockQueue(page, () => rows);
        await page.route(`**/api/v1/dtes/${failedDteId}/retries`, async (route) => {
            rows = [
                transmission({
                    id: '01a1c77f-d83a-73c9-8bf2-1b2c3d4e5f61',
                    attempt: 2,
                    error: 'Error en transmisión MH [HTTP 400]: segundo intento',
                }),
            ];
            await route.fulfill({
                json: {
                    data: {
                        record: { id: failedDteId, status: 'RECHAZADO' },
                        mh_result: { estado: 'RECHAZADO', codigoMsg: '400' },
                    },
                },
            });
        });

        await openTransmissionDetails(page, failedDteId);
        await page.getByRole('button', { name: 'Reintentar este DTE' }).click();

        await expect(
            page.getByText('Hacienda volvió a rechazar el DTE. Revisa el último error.'),
        ).toBeVisible();
        await expect(
            page.getByText('Error en transmisión MH [HTTP 400]: segundo intento').last(),
        ).toBeVisible();
        await expect(page.getByRole('button', { name: 'Reintentar este DTE' })).toBeVisible();
    });

    test('does not offer a single retry for transmissions that are not DTEs', async ({ page }) => {
        await mockQueue(page, () => [
            transmission({
                transmittable_type: 'App\\Models\\DteInvalidation',
                operation: 'invalidation',
            }),
        ]);

        await openTransmissionDetails(page, failedDteId);

        await expect(page.getByRole('button', { name: 'Reintentar este DTE' })).toHaveCount(0);
    });
});
