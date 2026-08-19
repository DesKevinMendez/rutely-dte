import { computed, ref } from 'vue';
import { useRequest } from '@/core/composables/useRequest';
import type { ApiResponse, PaginatedApiResponse } from '@/core/types/api.types';
import type {
    QueueApiTransmission,
    QueueJob,
    QueueRetryResult,
} from '../types/queue.types';

const mapJob = (transmission: QueueApiTransmission): QueueJob => ({
    id: transmission.id,
    dteId: transmission.transmittable_id,
    operation: transmission.operation,
    attempts: transmission.attempt,
    httpStatus: transmission.http_status,
    status: transmission.status === 'failed' ? 'FAILED' : 'PENDING',
    lastError: transmission.error,
    createdAt: transmission.created_at,
});

export async function useQueueUi() {
    const jobs = ref<QueueJob[]>([]);
    const lastUpdated = ref(new Date());
    const error = ref<string | null>(null);
    const { get, post, isLoading } = useRequest();

    const failedCount = computed(
        () => jobs.value.filter((job) => job.status === 'FAILED').length,
    );

    const loadQueue = async (): Promise<boolean> => {
        const response = await get<PaginatedApiResponse<QueueApiTransmission>>(
            '/api/v1/queue?per_page=50',
        );

        if (response.data.value) {
            jobs.value = response.data.value.data.map(mapJob);
            lastUpdated.value = new Date();
            error.value = null;

            return true;
        }

        error.value = response.error.value ?? 'No se pudo cargar la cola.';

        return false;
    };

    const retryFailed = async (): Promise<boolean> => {
        const response = await post<ApiResponse<QueueRetryResult>>(
            '/api/v1/queue/retries',
        );

        if (!response.data.value) {
            error.value =
                response.error.value ??
                'No se pudieron reintentar las transmisiones.';

            return false;
        }

        error.value = null;
        await loadQueue();

        return true;
    };

    await loadQueue();

    return {
        jobs,
        failedCount,
        lastUpdated,
        isLoading,
        error,
        refreshQueue: loadQueue,
        retryFailed,
    };
}
