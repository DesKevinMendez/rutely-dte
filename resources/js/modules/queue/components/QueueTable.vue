<script setup lang="ts">
import { Badge, BaseButton, DataTable } from 'ornito';
import type { BadgeVariant, TableField } from 'ornito';
import type { QueueJob } from '../types/queue.types';

const props = defineProps<{
    jobs: QueueJob[];
}>();

const emit = defineEmits<{
    retry: [jobId: string];
}>();

const statusVariant = (status: string): BadgeVariant => {
    switch (status) {
        case 'COMPLETED':
            return 'success';
        case 'FAILED_FATAL':
            return 'danger';
        case 'PENDING':
        case 'FAILED_RETRYABLE':
            return 'warning';
        default:
            return 'neutral';
    }
};

const columns: TableField<QueueJob>[] = [
    { label: 'ID Trabajo', key: 'id', width: 180 },
    { label: 'DTE ID', key: 'dteId', width: 180 },
    {
        label: 'Intentos',
        key: 'attempts',
        width: 120,
        format: (row) => `${row.attempts} / ${row.maxAttempts}`,
    },
    {
        label: 'Próximo Reintento',
        key: 'nextRetryAt',
        width: 190,
        format: (row) =>
            row.nextRetryAt
                ? new Date(row.nextRetryAt).toLocaleString('es-SV')
                : 'N/A',
    },
    { label: 'Estado Cola', key: 'status', width: 170, slot: 'status' },
    {
        label: 'Último Error',
        key: 'lastError',
        width: 280,
        format: (row) => row.lastError || 'Ninguno',
    },
];
</script>

<template>
    <DataTable
        :columns="columns"
        :data="props.jobs"
        :show-search="false"
        actions-label="Acción"
    >
        <template #status="{ row }">
            <Badge :variant="statusVariant(row.status)" text="xs">
                {{ row.status }}
            </Badge>
        </template>

        <template #actions="{ row }">
            <BaseButton
                v-if="row.status !== 'COMPLETED'"
                size="small"
                variant="outline"
                @click.stop="emit('retry', row.id)"
            >
                Reintentar
            </BaseButton>
        </template>
    </DataTable>
</template>
