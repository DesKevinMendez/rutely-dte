<script setup lang="ts">
import { IconRefresh } from '@tabler/icons-vue';
import {
    BaseButton,
    Card,
    CollapseTransition,
    FormInput,
    FormSelect,
} from 'ornito';
import { Form } from 'vee-validate';

const isOpen = defineModel<boolean>('open', { required: true });
const filterTipoDte = defineModel<string>('tipoDte', { required: true });
const filterEstado = defineModel<string>('estado', { required: true });
const filterDate = defineModel<string>('date', { required: true });
const dateMode = defineModel<string>('dateMode', { required: true });
const dateFrom = defineModel<string>('dateFrom', { required: true });
const dateTo = defineModel<string>('dateTo', { required: true });

defineProps<{ dateRangeMessage: string | null }>();

const emit = defineEmits<{
    reset: [];
}>();

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
    { value: 'RECIBIDO', label: 'RECIBIDO' },
    { value: 'PROCESADO', label: 'PROCESADO' },
    { value: 'CONTINGENCIA', label: 'CONTINGENCIA' },
    { value: 'RECHAZADO', label: 'RECHAZADO' },
    { value: 'DESCONOCIDO', label: 'DESCONOCIDO' },
    { value: 'INVALIDADO', label: 'INVALIDADO' },
];

const dateOptions = [
    { value: 'day', label: 'Día específico' },
    { value: 'range', label: 'Rango de fechas' },
];
</script>

<template>
    <CollapseTransition>
        <div v-if="isOpen" id="dte-filter-panel">
            <Card title="Filtros">
                <Form class="grid grid-cols-1 gap-4 md:grid-cols-3">
                    <FormSelect
                        v-model="filterTipoDte"
                        id="filter-tipo-dte"
                        name="filterTipoDte"
                        label="Tipo de DTE"
                        :options="tipoDteOptions"
                    />
                    <FormSelect
                        v-model="filterEstado"
                        id="filter-estado-mh"
                        name="filterEstado"
                        label="Estado de MH"
                        :options="estadoOptions"
                    />
                    <FormSelect
                        v-model="dateMode"
                        id="filter-date-mode"
                        name="dateMode"
                        label="Período de emisión"
                        :options="dateOptions"
                    />
                    <FormInput
                        v-if="dateMode === 'day'"
                        v-model="filterDate"
                        id="filter-emission-date"
                        name="filterDate"
                        type="date"
                        label="Fecha de emisión"
                    />
                    <template v-else>
                        <FormInput
                            v-model="dateFrom"
                            id="filter-date-from"
                            name="dateFrom"
                            type="date"
                            label="Fecha inicial"
                        />
                        <FormInput
                            v-model="dateTo"
                            id="filter-date-to"
                            name="dateTo"
                            type="date"
                            label="Fecha final"
                        />
                    </template>
                </Form>
                <p v-if="dateRangeMessage" class="mt-2 text-sm" role="status">
                    {{ dateRangeMessage }}
                </p>
                <div
                    class="mt-4 flex justify-end border-t border-default-light pt-4 dark:border-default-dark"
                >
                    <BaseButton
                        variant="outline"
                        size="auto"
                        :icon="IconRefresh"
                        @click="emit('reset')"
                    >
                        Restablecer filtros
                    </BaseButton>
                </div>
            </Card>
        </div>
    </CollapseTransition>
</template>
