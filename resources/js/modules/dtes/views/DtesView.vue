<script setup lang="ts">
import { ref } from 'vue';
import { BaseButton, Card, FormSelect } from 'ornito';
import CreateDteModal from '../components/CreateDteModal.vue';
import DteDataTable from '../components/DteDataTable.vue';
import { useDtesUi } from '../composables/useDtesUi';
import type { DteDraft } from '../types/dte.types';

const isCreateModalOpen = ref(false);
const {
    filterTipoDte,
    filterEstado,
    currentPage,
    filteredRecords,
    addRecord,
} = useDtesUi();

const tipoDteOptions = [
    { value: '', label: 'Todos los tipos' },
    { value: '01', label: 'Factura (01)' },
    { value: '03', label: 'Comprobante de Crédito Fiscal (03)' },
    { value: '05', label: 'Nota de Crédito (05)' },
    { value: '14', label: 'Sujeto Excluido (14)' },
];

const estadoOptions = [
    { value: '', label: 'Todos los estados' },
    { value: 'PROCESADO', label: 'PROCESADO' },
    { value: 'CONTINGENCIA', label: 'CONTINGENCIA' },
    { value: 'RECHAZADO', label: 'RECHAZADO' },
    { value: 'INVALIDADO', label: 'INVALIDADO' },
];

const handleCreated = (draft: DteDraft): void => {
    addRecord(draft);
};
</script>

<template>
    <div class="space-y-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white">Documentos Tributarios Electrónicos (DTEs)</h1>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    Emisión, consulta y seguimiento de la transmisión de comprobantes fiscales al Ministerio de Hacienda.
                </p>
            </div>
            <div class="flex items-center gap-2.5">
                <BaseButton variant="outline" size="auto" @click="currentPage = 1">Actualizar</BaseButton>
                <BaseButton variant="primary" size="auto" @click="isCreateModalOpen = true">Emitir Nuevo DTE</BaseButton>
            </div>
        </div>

        <Card title="Listado de Comprobantes Emitidos">
            <template #headerButtons>
                <div class="flex flex-wrap items-center gap-2">
                    <div class="w-44">
                        <FormSelect v-model="filterTipoDte" id="filter-tipo-dte" name="filterTipoDte" label="" small :options="tipoDteOptions" />
                    </div>
                    <div class="w-44">
                        <FormSelect v-model="filterEstado" id="filter-estado" name="filterEstado" label="" small :options="estadoOptions" />
                    </div>
                </div>
            </template>

            <DteDataTable :records="filteredRecords" />
        </Card>

        <CreateDteModal v-model:is-open="isCreateModalOpen" @created="handleCreated" />
    </div>
</template>
