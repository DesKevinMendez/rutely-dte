<script setup lang="ts">
import { Alert, Badge, BaseButton, Card, DataTable, FormSelect } from 'ornito';
import type { BadgeVariant, TableField } from 'ornito';
import { computed, ref } from 'vue';
import { useDtesUi } from '../composables/useDtesUi';
import type { DteApiRecord, DteDraft } from '../types/dte.types';
import CreateDteModal from './CreateDteModal.vue';

const isCreateModalOpen = ref(false);
const tableKey = ref(0);
const { filterTipoDte, filterEstado, isLoading, error, createDte } = useDtesUi();

const tipoDteOptions = [
    { value: '', label: 'Todos los tipos' },
    { value: '01', label: 'Factura (01)' },
    { value: '03', label: 'Comprobante de Crédito Fiscal (03)' },
    { value: '05', label: 'Nota de Crédito (05)' },
    { value: '14', label: 'Sujeto Excluido (14)' },
];

const estadoOptions = [
    { value: '', label: 'Todos los estados' },
    { value: 'BORRADOR', label: 'BORRADOR' },
    { value: 'FIRMADO', label: 'FIRMADO' },
    { value: 'PROCESADO', label: 'PROCESADO' },
    { value: 'CONTINGENCIA', label: 'CONTINGENCIA' },
    { value: 'RECHAZADO', label: 'RECHAZADO' },
    { value: 'INVALIDADO', label: 'INVALIDADO' },
];

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

        return emissionTime ? `${formattedDate} ${emissionTime}` : formattedDate;
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

    if (filterTipoDte.value) {
        params.set('filter[tipoDte]', filterTipoDte.value);
    }

    if (filterEstado.value) {
        params.set('filter[estado]', filterEstado.value);
    }

    const query = params.toString();

    return query ? `/api/v1/dtes?${query}` : '/api/v1/dtes';
});

const receiverName = (record: DteApiRecord): string =>
    record.original_json?.receptor?.nombre?.trim() || 'Cliente General';

const receiverDocument = (record: DteApiRecord): string =>
    record.receiver_document ||
    record.original_json?.receptor?.numDocumento ||
    'N/A';

const statusVariant = (status: string): BadgeVariant => {
    switch (status) {
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

const handleCreated = async (draft: DteDraft): Promise<void> => {
    if (await createDte(draft)) {
        isCreateModalOpen.value = false;
        refreshTable();
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
            <div class="flex items-center gap-2.5">
                <BaseButton
                    variant="outline"
                    size="auto"
                    :disabled="isLoading"
                    @click="refreshTable"
                >
                    Actualizar
                </BaseButton>
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

        <Alert v-if="error" type="danger">
            {{ error }}
        </Alert>

        <Card title="Listado de Comprobantes Emitidos">
            <template #headerButtons>
                <div class="flex flex-wrap items-center gap-2">
                    <div class="w-44">
                        <FormSelect
                            v-model="filterTipoDte"
                            id="filter-tipo-dte"
                            name="filterTipoDte"
                            label=""
                            small
                            :options="tipoDteOptions"
                        />
                    </div>
                    <div class="w-44">
                        <FormSelect
                            v-model="filterEstado"
                            id="filter-estado"
                            name="filterEstado"
                            label=""
                            small
                            :options="estadoOptions"
                        />
                    </div>
                </div>
            </template>

            <DataTable
                :key="tableKey"
                :columns="columns"
                :url="tableUrl"
                search-by="query"
                search-placeholder="Buscar DTE..."
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
    </div>
</template>
