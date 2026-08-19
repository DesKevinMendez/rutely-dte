import { ref } from 'vue';
import type { QueueJob } from '../types/queue.types';

const initialJobs: QueueJob[] = [
    {
        id: 'JOB-00091',
        dteId: 'DTE-01-M001P001-000000000000182',
        attempts: 2,
        maxAttempts: 5,
        nextRetryAt: '2026-08-18T21:05:00-06:00',
        status: 'FAILED_RETRYABLE',
        lastError: '503 Service Unavailable - Ministerio de Hacienda',
    },
    {
        id: 'JOB-00090',
        dteId: 'DTE-03-M001P001-000000000000181',
        attempts: 1,
        maxAttempts: 5,
        nextRetryAt: '2026-08-18T21:02:00-06:00',
        status: 'PENDING',
        lastError: 'Timeout de conexión al servicio de recepción',
    },
    {
        id: 'JOB-00089',
        dteId: 'DTE-01-M001P001-000000000000180',
        attempts: 3,
        maxAttempts: 5,
        nextRetryAt: null,
        status: 'COMPLETED',
        lastError: null,
    },
];

export function useQueueUi() {
    const jobs = ref<QueueJob[]>(initialJobs.map((job) => ({ ...job })));
    const lastUpdated = ref(new Date());

    const refreshQueue = (): void => {
        lastUpdated.value = new Date();
    };

    const retryJob = (jobId: string): void => {
        const job = jobs.value.find((candidate) => candidate.id === jobId);

        if (!job || job.status === 'COMPLETED') {
            return;
        }

        job.attempts = Math.min(job.attempts + 1, job.maxAttempts);
        job.status = 'PENDING';
        job.lastError = null;
        job.nextRetryAt = new Date(Date.now() + 5 * 60 * 1000).toISOString();
    };

    return {
        jobs,
        lastUpdated,
        refreshQueue,
        retryJob,
    };
}
