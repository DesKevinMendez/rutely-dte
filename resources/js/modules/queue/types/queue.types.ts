export type QueueApiStatus = 'pending' | 'failed';
export type QueueJobStatus = 'PENDING' | 'FAILED';

export interface QueueApiTransmission {
    id: string;
    transmittable_type: string;
    transmittable_id: string;
    operation: string;
    attempt: number;
    http_status: number | null;
    status: QueueApiStatus;
    error: string | null;
    sent_at: string | null;
    responded_at: string | null;
    created_at: string;
}

export interface QueueJob {
    id: string;
    dteId: string;
    operation: string;
    attempts: number;
    httpStatus: number | null;
    status: QueueJobStatus;
    lastError: string | null;
    createdAt: string;
}

export interface QueueRetryResult {
    count: number;
    results: Array<{
        transmission_id: string;
        result: unknown;
    }>;
}
