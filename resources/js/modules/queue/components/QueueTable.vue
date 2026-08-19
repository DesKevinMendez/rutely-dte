<script setup lang="ts">
import { Badge, DataTable } from 'ornito';
import type { BadgeVariant, TableField } from 'ornito';
import type { QueueJob } from '../types/queue.types';

const { jobs } = defineProps<{
    jobs: QueueJob[];
}>();

const statusVariant = (status: string): BadgeVariant => {
    switch (status) {
        case 'FAILED':
            return 'danger';
        case 'PENDING':
            return 'warning';
        default:
            return 'neutral';
    }
};

const columns: TableField<QueueJob>[] = [
    { label: 'ID Transmisión', key: 'id', width: 220 },
    { label: 'DTE / Recurso', key: 'dteId', width: 220 },
    { label: 'Operación', key: 'operation', width: 150 },
    { label: 'Intento', key: 'attempts', width: 100 },
    {
        label: 'HTTP',
        key: 'httpStatus',
        width: 100,
        format: (row) => row.httpStatus?.toString() ?? 'N/A',
    },
    { label: 'Estado Cola', key: 'status', width: 140, slot: 'status' },
    {
        label: 'Último Error',
        key: 'lastError',
        width: 300,
        format: (row) => row.lastError || 'Ninguno',
    },
    {
        label: 'Creado',
        key: 'createdAt',
        width: 180,
        format: (row) => new Date(row.createdAt).toLocaleString('es-SV'),
    },
];
</script>

<template>
    <DataTable :columns="columns" :data="jobs" :show-search="false">
        <template #status="{ row }">
            <Badge :variant="statusVariant(row.status)" text="xs">
                {{ row.status }}
            </Badge>
        </template>
    </DataTable>
</template>
