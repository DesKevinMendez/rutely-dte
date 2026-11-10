<script setup lang="ts">
import { IconChevronDown, IconChevronUp } from '@tabler/icons-vue';
import { BaseButton, Card, CollapseTransition, DataTable } from 'ornito';
import type { TableField } from 'ornito';
import { computed, ref } from 'vue';
import type { DteApiRecord, DteDocumentItem } from '../types/dte.types';

const props = defineProps<{
    dte: DteApiRecord;
}>();

const items = computed(() => props.dte.original_json?.cuerpoDocumento ?? []);
const isOriginalDataOpen = ref(false);

const formatAmount = (amount: number): string =>
    new Intl.NumberFormat('es-SV', {
        style: 'currency',
        currency: 'USD',
        minimumFractionDigits: 2,
        maximumFractionDigits: 8,
    }).format(amount);

const columns: TableField<DteDocumentItem>[] = [
    { label: 'Nº', key: 'numItem' },
    { label: 'Descripción', key: 'descripcion' },
    { label: 'Código', key: 'codigo', format: (item) => item.codigo ?? '—' },
    { label: 'Cantidad', key: 'cantidad' },
    {
        label: 'Precio unitario',
        key: 'precioUni',
        format: (item) => formatAmount(item.precioUni),
    },
    {
        label: 'Descuento',
        key: 'montoDescu',
        format: (item) => formatAmount(item.montoDescu ?? 0),
    },
    {
        label: 'Venta gravada',
        key: 'ventaGravada',
        format: (item) =>
            item.ventaGravada === undefined
                ? '—'
                : formatAmount(item.ventaGravada),
    },
    {
        label: 'IVA del ítem',
        key: 'ivaItem',
        format: (item) =>
            item.ivaItem === undefined ? '—' : formatAmount(item.ivaItem),
    },
];
</script>

<template>
    <div class="space-y-4 pt-4">
        <Card
            title="Ítems enviados a Hacienda"
            :subtitle="`${items.length} ${items.length === 1 ? 'ítem' : 'ítems'}`"
        >
            <DataTable
                v-if="items.length"
                :columns="columns"
                :data="items"
                :show-search="false"
            />
            <p v-else class="text-sm text-gray-600 dark:text-gray-400">
                Este DTE no tiene ítems en el documento original.
            </p>
        </Card>

        <div v-if="items.length" class="space-y-3">
            <BaseButton
                variant="outline"
                size="auto"
                :icon="isOriginalDataOpen ? IconChevronUp : IconChevronDown"
                icon-position="right"
                :aria-expanded="isOriginalDataOpen"
                aria-controls="dte-items-original-data"
                @click="isOriginalDataOpen = !isOriginalDataOpen"
            >
                {{ isOriginalDataOpen ? 'Ocultar' : 'Ver' }} datos originales de
                los ítems
            </BaseButton>

            <CollapseTransition>
                <div v-if="isOriginalDataOpen" id="dte-items-original-data">
                    <Card title="Datos originales de los ítems">
                        <pre
                            class="overflow-x-auto text-xs break-all whitespace-pre-wrap text-gray-800 dark:text-gray-200"
                            >{{ JSON.stringify(items, null, 2) }}</pre>
                    </Card>
                </div>
            </CollapseTransition>
        </div>
    </div>
</template>
