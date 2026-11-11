<script setup lang="ts">
import { IconDownload, IconFilter, IconRefresh } from '@tabler/icons-vue';
import {
    Alert,
    Badge,
    BaseButton,
    BaseButtonIcon,
    Card,
    DataTable,
} from 'ornito';
import type { BadgeVariant, TableField } from 'ornito';
import { computed, ref, watch } from 'vue';
import { formatIdentityDocument } from '@/core/composables/formatIdentityDocument';
import { useDteReportUi } from '../composables/useDteReportUi';
import { useDtesUi } from '../composables/useDtesUi';
import type { DteApiRecord } from '../types/dte.types';
import DteDetailsModal from './DteDetailsModal.vue';
import DteFiltersPanel from './DteFiltersPanel.vue';

const isFiltersOpen = ref(false);
const selectedDteId = ref<string | null>(null);
const tableKey = ref(0);
const {
    filterTipoDte,
    filterEstado,
    filterDate,
    dateMode,
    dateFrom,
    dateTo,
    dateRangeMessage,
    resetFilters,
    isLoading,
} = useDtesUi();
const { downloadError, isDownloading, downloadReport } = useDteReportUi();

const typeLabels: Record<string, string> = {
    '01': 'Factura (01)',
    '03': 'CCF (03)',
    '05': 'Nota de Crédito (05)',
    '14': 'Sujeto Excluido (14)',
};

const formatEmissionDateTime = (record: DteApiRecord): string => {
    const emissionDate = record.original_json?.identificacion?.fecEmi;
    const emissionTime = record.original_json?.identificacion?.horEmi;

    if (emissionDate) {
        const [year, month, day] = emissionDate.split('-');
        const formattedDate =
            year && month && day ? `${day}/${month}/${year}` : emissionDate;

        return emissionTime
            ? `${formattedDate} ${emissionTime}`
            : formattedDate;
    }

    return new Date(record.created_at).toLocaleString('es-SV', {
        timeZone: 'America/El_Salvador',
    });
};

const columns: TableField<DteApiRecord>[] = [
    { label: 'Código Generación', key: 'generation_code' },
    { label: 'Nº Control', key: 'control_number' },
    {
        label: 'Tipo DTE',
        key: 'dte_type',
        format: (row) => typeLabels[row.dte_type] ?? row.dte_type,
    },
    { label: 'Receptor', key: 'receiver_document', slot: 'receiver' },
    {
        label: 'Monto Total',
        key: 'total_amount',
        format: (row) => `$${(row.total_amount / 100).toFixed(2)}`,
    },
    { label: 'Estado', key: 'status', slot: 'status' },
    {
        label: 'Emisión',
        key: 'created_at',
        format: formatEmissionDateTime,
    },
];

const filters = computed(() => {
    if (dateRangeMessage.value !== null) {
        return null;
    }

    const params = new URLSearchParams();

    if (filterTipoDte.value) {
        params.set('filter[tipoDte]', filterTipoDte.value);
    }

    if (filterEstado.value) {
        params.set('filter[estado]', filterEstado.value);
    }

    if (dateMode.value === 'range') {
        params.set('filter[from]', dateFrom.value);
        params.set('filter[to]', dateTo.value);
    } else if (filterDate.value) {
        params.set('filter[date]', filterDate.value);
    }

    return params.toString();
});

const tableFilters = ref(filters.value ?? '');
watch(filters, (query, _previous, onCleanup) => {
    if (query === null) {
        return;
    }

    const timer = setTimeout(() => {
        tableFilters.value = query;
    }, 500);
    onCleanup(() => clearTimeout(timer));
});

const tableUrl = computed(() =>
    tableFilters.value ? `/api/v1/dtes?${tableFilters.value}` : '/api/v1/dtes',
);

const receiverName = (record: DteApiRecord): string =>
    record.original_json?.receptor?.nombre?.trim() || '-';

const receiverDocument = (record: DteApiRecord): string => {
    const receiver = record.original_json?.receptor;
    const document = record.receiver_document || receiver?.numDocumento;

    if (!document) {
        return 'N/A';
    }

    const hasOwnFormat =
        receiver?.tipoDocumento &&
        !['13', '36'].includes(receiver.tipoDocumento);

    return hasOwnFormat ? document : formatIdentityDocument(document);
};

const statusVariant = (status: string): BadgeVariant => {
    switch (status) {
        case 'RECIBIDO':
        case 'PROCESADO':
            return 'success';
        case 'RECHAZADO':
            return 'danger';
        case 'CONTINGENCIA':
        case 'FIRMADO':
            return 'warning';
        default:
            return 'neutral';
    }
};

const refreshTable = (): void => {
    tableKey.value += 1;
};
</script>

<template>
    <div class="space-y-6">
        <div
            class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between"
        >
            <div>
                <h1
                    class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white"
                >
                    Documentos Tributarios Electrónicos (DTEs)
                </h1>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    Emisión, consulta y seguimiento de la transmisión de
                    comprobantes fiscales al Ministerio de Hacienda.
                </p>
            </div>
            <div class="flex flex-wrap items-center gap-2.5 lg:shrink-0">
                <BaseButton
                    variant="outline"
                    size="auto"
                    :icon="IconDownload"
                    :loading="isDownloading"
                    :disabled="filters === null"
                    @click="filters !== null && downloadReport(filters)"
                >
                    Descargar reporte
                </BaseButton>
                <BaseButtonIcon
                    variant="outline"
                    size="md"
                    :icon="IconRefresh"
                    aria-label="Actualizar DTEs"
                    :disabled="isLoading"
                    @click="refreshTable"
                />
                <BaseButtonIcon
                    variant="outline"
                    size="md"
                    :icon="IconFilter"
                    aria-label="Mostrar filtros"
                    :aria-expanded="isFiltersOpen"
                    aria-controls="dte-filter-panel"
                    @click="isFiltersOpen = !isFiltersOpen"
                />
            </div>
        </div>

        <DteFiltersPanel
            v-model:open="isFiltersOpen"
            v-model:tipo-dte="filterTipoDte"
            v-model:estado="filterEstado"
            v-model:date="filterDate"
            v-model:date-mode="dateMode"
            v-model:date-from="dateFrom"
            v-model:date-to="dateTo"
            :date-range-message="dateRangeMessage"
            @reset="resetFilters"
        />

        <Alert v-if="downloadError" role="alert" type="danger">
            {{ downloadError }}
        </Alert>

        <Card title="Listado de Comprobantes Emitidos">
            <DataTable
                :key="tableKey"
                :columns="columns"
                :url="tableUrl"
                search-by="query"
                search-placeholder="Buscar DTE..."
                @row-selected="(row: DteApiRecord) => (selectedDteId = row.id)"
            >
                <template #receiver="{ row }">
                    <p>{{ receiverName(row) }}</p>
                    <p class="text-xs text-gray-500">
                        Doc: {{ receiverDocument(row) }}
                    </p>
                </template>
                <template #status="{ row }">
                    <Badge :variant="statusVariant(row.status)" text="xs">
                        {{ row.status }}
                    </Badge>
                </template>
            </DataTable>
        </Card>

        <DteDetailsModal
            :open="selectedDteId !== null"
            :dte-id="selectedDteId"
            @close="selectedDteId = null"
        />
    </div>
</template>
