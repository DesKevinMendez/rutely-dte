<script setup lang="ts">
import { Badge, DataTable } from 'ornito';
import type { BadgeVariant, TableField } from 'ornito';
import type { DteRecord } from '../types/dte.types';

const props = defineProps<{
    records: DteRecord[];
}>();

const typeLabel = (type: string): string => {
    const labels: Record<string, string> = {
        '01': 'Factura (01)',
        '03': 'CCF (03)',
        '05': 'Nota de Crédito (05)',
        '14': 'Sujeto Excluido (14)',
    };

    return labels[type] ?? type;
};

const statusVariant = (status: string): BadgeVariant => {
    switch (status) {
        case 'PROCESADO':
            return 'success';
        case 'RECHAZADO':
            return 'danger';
        case 'CONTINGENCIA':
            return 'warning';
        default:
            return 'neutral';
    }
};

const columns: TableField<DteRecord>[] = [
    { label: 'Código Generación', key: 'codigoGeneracion', width: 260 },
    { label: 'Nº Control', key: 'numeroControl', width: 260 },
    { label: 'Tipo DTE', key: 'tipoDte', width: 160, format: (row) => typeLabel(row.tipoDte) },
    { label: 'Receptor', key: 'receptorNombre', width: 240, slot: 'recipient' },
    { label: 'Monto Total', key: 'montoTotal', width: 140, format: (row) => `$${row.montoTotal.toFixed(2)}` },
    { label: 'Estado', key: 'estado', width: 160, slot: 'status' },
    { label: 'Fecha', key: 'createdAt', width: 140, format: (row) => new Date(row.createdAt).toLocaleDateString('es-SV') },
];
</script>

<template>
    <DataTable
        :columns="columns"
        :data="props.records"
        :show-search="true"
        search-placeholder="Buscar DTE..."
    >
        <template #recipient="{ row }">
            <div>
                <p class="font-medium text-gray-900 dark:text-white">{{ row.receptorNombre }}</p>
                <p class="text-xs text-gray-500 dark:text-gray-400">Doc: {{ row.receptorDocumento }}</p>
            </div>
        </template>

        <template #status="{ row }">
            <Badge :variant="statusVariant(row.estado)" text="xs">
                {{ row.estado }}
            </Badge>
        </template>
    </DataTable>
</template>
