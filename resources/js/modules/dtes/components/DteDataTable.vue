<script setup lang="ts">
import { Badge, FormInput } from 'ornito';
import type { BadgeVariant } from 'ornito';
import { computed, ref } from 'vue';
import type { DteRecord } from '../types/dte.types';

const { records } = defineProps<{ records: DteRecord[] }>();
const search = ref('');
const typeLabels: Record<string, string> = {
    '01': 'Factura (01)',
    '03': 'CCF (03)',
    '05': 'Nota de Crédito (05)',
    '14': 'Sujeto Excluido (14)',
};

const rows = computed(() => {
    const query = search.value.trim().toLowerCase();

    if (!query) {
        return records;
    }

    return records.filter((record) =>
        [
            record.codigoGeneracion,
            record.numeroControl,
            record.receptorNombre,
            record.receptorDocumento,
            record.estado,
        ].some((value) => value.toLowerCase().includes(query)),
    );
});

const typeLabel = (type: string): string => typeLabels[type] ?? type;

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
</script>

<template>
    <div>
        <div class="mb-4 ml-auto w-64">
            <FormInput
                v-model="search"
                id="dte-table-search"
                name="dteTableSearch"
                label=""
                placeholder="Buscar DTE..."
                small
            />
        </div>

        <div class="overflow-x-auto">
            <table
                class="min-w-full divide-y divide-gray-200 dark:divide-gray-700"
            >
                <thead>
                    <tr>
                        <th class="px-6 py-3 text-left">Código Generación</th>
                        <th class="px-6 py-3 text-left">Nº Control</th>
                        <th class="px-6 py-3 text-left">Tipo DTE</th>
                        <th class="px-6 py-3 text-left">Receptor</th>
                        <th class="px-6 py-3 text-left">Monto Total</th>
                        <th class="px-6 py-3 text-left">Estado</th>
                        <th class="px-6 py-3 text-left">Fecha</th>
                    </tr>
                </thead>
                <tbody
                    class="divide-y divide-gray-200 dark:divide-gray-700"
                >
                    <tr v-for="record in rows" :key="record.id">
                        <td class="px-6 py-4">
                            {{ record.codigoGeneracion }}
                        </td>
                        <td class="px-6 py-4">
                            {{ record.numeroControl }}
                        </td>
                        <td class="px-6 py-4">
                            {{ typeLabel(record.tipoDte) }}
                        </td>
                        <td class="px-6 py-4">
                            <p>{{ record.receptorNombre }}</p>
                            <p class="text-xs text-gray-500">
                                Doc: {{ record.receptorDocumento }}
                            </p>
                        </td>
                        <td class="px-6 py-4">
                            ${{ record.montoTotal.toFixed(2) }}
                        </td>
                        <td class="px-6 py-4">
                            <Badge
                                :variant="statusVariant(record.estado)"
                                text="xs"
                            >
                                {{ record.estado }}
                            </Badge>
                        </td>
                        <td class="px-6 py-4">
                            {{
                                new Date(record.createdAt).toLocaleDateString(
                                    'es-SV',
                                )
                            }}
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
