<script setup lang="ts">
import { IconFilter, IconRefresh } from '@tabler/icons-vue';
import { Badge, BaseButton, BaseButtonIcon, Card, DataTable } from 'ornito';
import type { BadgeVariant, TableField } from 'ornito';
import { computed, onScopeDispose, ref, watch } from 'vue';
import { formatIdentityDocument } from '@/core/composables/formatIdentityDocument';
import { useDtesUi } from '../composables/useDtesUi';
import type { DteApiRecord, DteDraft } from '../types/dte.types';
import CreateDteModal from './CreateDteModal.vue';
import DteDetailsModal from './DteDetailsModal.vue';
import DteFiltersPanel from './DteFiltersPanel.vue';

const isCreateModalOpen = ref(false);
const isFiltersOpen = ref(false);
const selectedDteId = ref<string | null>(null);
const tableKey = ref(0);
const {
    filterTipoDte,
    filterEstado,
    filterFecha,
    resetFilters,
    isLoading,
    createDte,
} = useDtesUi();
const requestFilterTipoDte = ref(filterTipoDte.value);
const requestFilterEstado = ref(filterEstado.value);
const requestFilterFecha = ref(filterFecha.value);
let filterDebounce: ReturnType<typeof setTimeout> | null = null;

watch(
    [filterTipoDte, filterEstado, filterFecha],
    ([tipoDte, estado, fecha]) => {
        if (filterDebounce !== null) {
            clearTimeout(filterDebounce);
        }

        filterDebounce = setTimeout(() => {
            requestFilterTipoDte.value = tipoDte;
            requestFilterEstado.value = estado;
            requestFilterFecha.value = fecha ?? '';
            filterDebounce = null;
        }, 500);
    },
);

onScopeDispose(() => {
    if (filterDebounce !== null) {
        clearTimeout(filterDebounce);
    }
});

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

const tableUrl = computed(() => {
    const params = new URLSearchParams();

    if (requestFilterTipoDte.value) {
        params.set('filter[tipoDte]', requestFilterTipoDte.value);
    }

    if (requestFilterEstado.value) {
        params.set('filter[estado]', requestFilterEstado.value);
    }

    if (requestFilterFecha.value) {
        params.set('filter[fecha]', requestFilterFecha.value);
    }

    const query = params.toString();

    return query ? `/api/v1/dtes?${query}` : '/api/v1/dtes';
});

const receiverName = (record: DteApiRecord): string =>
    record.original_json?.receptor?.nombre?.trim() || 'Cliente General';

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

const handleCreated = async (
    draft: DteDraft,
    setServerErrors: (
        errors: Record<string, string>,
        message: string | null,
    ) => void,
): Promise<void> => {
    const result = await createDte(draft);

    if (result.success) {
        isCreateModalOpen.value = false;
        refreshTable();
    } else {
        setServerErrors(result.validationErrors, result.serverError);
    }
};
</script>

<template>
    <div class="space-y-6">
        <div
            class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
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
            <div class="flex flex-wrap items-center gap-2.5">
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
                <BaseButton
                    variant="primary"
                    size="auto"
                    :disabled="isLoading"
                    @click="isCreateModalOpen = true"
                >
                    Emitir Nuevo DTE
                </BaseButton>
            </div>
        </div>

        <DteFiltersPanel
            v-model:open="isFiltersOpen"
            v-model:tipo-dte="filterTipoDte"
            v-model:estado="filterEstado"
            v-model:fecha="filterFecha"
            @reset="resetFilters"
        />

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

        <CreateDteModal
            v-model:is-open="isCreateModalOpen"
            :is-submitting="isLoading"
            @created="handleCreated"
        />

        <DteDetailsModal
            :open="selectedDteId !== null"
            :dte-id="selectedDteId"
            @close="selectedDteId = null"
        />
    </div>
</template>
