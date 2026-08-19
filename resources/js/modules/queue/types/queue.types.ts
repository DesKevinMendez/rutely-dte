export type QueueJobStatus = 'PENDING' | 'FAILED_RETRYABLE' | 'FAILED_FATAL' | 'COMPLETED';

export interface QueueJob {
    id: string;
    dteId: string;
    attempts: number;
    maxAttempts: number;
    nextRetryAt: string | null;
    status: QueueJobStatus;
    lastError: string | null;
}
