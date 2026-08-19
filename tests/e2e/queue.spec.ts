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
});
