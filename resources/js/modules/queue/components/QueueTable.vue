<script setup lang="ts">
import { IconRefresh } from '@tabler/icons-vue';
import {
    Alert,
    Badge,
    BaseButton,
    BottomModal,
    DataTable,
    InfoItem,
} from 'ornito';
import type { BadgeVariant, TableField } from 'ornito';
import { computed, ref, watch } from 'vue';
import type { QueueDteRetryOutcome, QueueJob } from '../types/queue.types';

const { jobs, canRetry, retrying, retryOutcome } = defineProps<{
    jobs: QueueJob[];
    canRetry: boolean;
    retrying: boolean;
    retryOutcome: QueueDteRetryOutcome | null;
}>();

const emit = defineEmits<{
    retry: [job: QueueJob];
}>();

const isDetailsOpen = ref(false);
const selectedJob = ref<QueueJob | null>(null);

const isSelectedResource = (job: QueueJob): boolean =>
    job.dteId === selectedJob.value?.dteId &&
    job.operation === selectedJob.value?.operation;

const isSelectedInQueue = computed(() => jobs.some(isSelectedResource));

const isRetryable = computed(
    () =>
        canRetry &&
        isSelectedInQueue.value &&
        selectedJob.value?.operation === 'dte' &&
        selectedJob.value.status === 'FAILED',
);

const selectedRetryOutcome = computed(() =>
    selectedJob.value?.operation === 'dte' &&
    retryOutcome?.dteId === selectedJob.value.dteId
        ? retryOutcome
        : null,
);

watch(
    () => jobs,
    (currentJobs) => {
        selectedJob.value =
            currentJobs.find(isSelectedResource) ?? selectedJob.value;
    },
);

const showDetails = (job: QueueJob): void => {
    selectedJob.value = job;
    isDetailsOpen.value = true;
};

const retrySelected = (): void => {
    if (selectedJob.value) {
        emit('retry', selectedJob.value);
    }
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
        <div class="space-y-4">
            <Alert
                v-if="selectedRetryOutcome"
                :type="selectedRetryOutcome.isAccepted ? 'success' : 'danger'"
            >
                {{ selectedRetryOutcome.message }}
            </Alert>
            <InfoItem label="Intento">
                {{ selectedJob?.attempts }}
            </InfoItem>
            <InfoItem label="Último error">
                <p class="break-words whitespace-pre-wrap">
                    {{
                        selectedJob?.lastError ||
                        'Hacienda no reportó errores en esta transmisión.'
                    }}
                </p>
            </InfoItem>
        </div>

        <template v-if="isRetryable" #footer>
            <div class="flex justify-end">
                <BaseButton
                    size="auto"
                    :icon="IconRefresh"
                    :loading="retrying"
                    :disabled="retrying"
                    @click="retrySelected"
                >
                    Reintentar este DTE
                </BaseButton>
            </div>
        </template>
    </BottomModal>
</template>
