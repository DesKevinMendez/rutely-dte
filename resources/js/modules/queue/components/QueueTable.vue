<script setup lang="ts">
import { Badge, BottomModal, DataTable, InfoItem } from 'ornito';
import type { BadgeVariant, TableField } from 'ornito';
import { ref } from 'vue';
import type { QueueJob } from '../types/queue.types';

const { jobs } = defineProps<{
    jobs: QueueJob[];
}>();

const isDetailsOpen = ref(false);
const selectedJob = ref<QueueJob | null>(null);

const showDetails = (job: QueueJob): void => {
    selectedJob.value = job;
    isDetailsOpen.value = true;
};

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
    <DataTable
        :columns="columns"
        :data="jobs"
        :show-search="false"
        @row-selected="showDetails"
    >
        <template #status="{ row }">
            <Badge :variant="statusVariant(row.status)" text="xs">
                {{ row.status }}
            </Badge>
        </template>
    </DataTable>

    <BottomModal
        v-model:open="isDetailsOpen"
        title="Información de Hacienda"
        subtitle="Último error de la transmisión seleccionada."
    >
        <InfoItem label="Último error">
            <p class="break-words whitespace-pre-wrap">
                {{
                    selectedJob?.lastError ||
                    'Hacienda no reportó errores en esta transmisión.'
                }}
            </p>
        </InfoItem>
    </BottomModal>
</template>
