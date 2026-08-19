<script setup lang="ts">
import {
    BaseButton,
    Card,
    DataTable,
    FormInput,
    FormSelect,
    Modal,
} from 'ornito';
import type { TableField } from 'ornito';
import { computed, ref, watch } from 'vue';
import type { DteDraft, DteItem } from '../types/dte.types';

const props = defineProps<{
    isOpen: boolean;
}>();

const emit = defineEmits<{
    'update:isOpen': [value: boolean];
    created: [draft: DteDraft];
}>();

const typeOptions = [
    { value: '01', label: 'Factura Electrónica (01)' },
    { value: '03', label: 'Comprobante de Crédito Fiscal - CCF (03)' },
    { value: '05', label: 'Nota de Crédito (05)' },
    { value: '14', label: 'Factura de Sujeto Excluido (14)' },
];

const documentOptions = [
    { value: '36', label: 'NIT (36)' },
    { value: '13', label: 'DUI (13)' },
    { value: '03', label: 'Pasaporte (03)' },
    { value: '37', label: 'Otro (37)' },
];

const tipoDte = ref('01');
const tipoDocumento = ref('36');
const receptorNombre = ref('CLIENTE DE PRUEBAS S.A. DE C.V.');
const receptorDocumento = ref('0614-280390-112-1');
const receptorCorreo = ref('cliente@ejemplo.com');
const items = ref<DteItem[]>([
    {
        descripcion: 'Servicios de Desarrollo de Software DTE',
        cantidad: 1,
        precioUni: 100,
    },
]);

const itemColumns: TableField<DteItem>[] = [
    {
        label: 'Descripción',
        key: 'descripcion',
        width: 320,
        slot: 'description',
    },
    { label: 'Cant.', key: 'cantidad', width: 120, slot: 'quantity' },
    {
        label: 'Precio Uni ($)',
        key: 'precioUni',
        width: 150,
        slot: 'unitPrice',
    },
];

const subtotal = computed(() =>
    items.value.reduce(
        (total, item) =>
            total + Number(item.cantidad || 0) * Number(item.precioUni || 0),
        0,
    ),
);
const iva = computed(() =>
    tipoDte.value === '03' ? subtotal.value * 0.13 : 0,
);
const total = computed(() => subtotal.value + iva.value);

const addItem = (): void => {
    items.value.push({ descripcion: '', cantidad: 1, precioUni: 0 });
};

const removeItem = (row: DteItem): void => {
    const index = items.value.indexOf(row);

    if (index >= 0 && items.value.length > 1) {
        items.value.splice(index, 1);
    }
};

const itemIndex = (row: DteItem): number =>
    Math.max(items.value.indexOf(row), 0);

const updateDescription = (row: DteItem, value: unknown): void => {
    row.descripcion = String(value ?? '');
};

const updateQuantity = (row: DteItem, value: unknown): void => {
    row.cantidad = Number(value ?? 0);
};

const updateUnitPrice = (row: DteItem, value: unknown): void => {
    row.precioUni = Number(value ?? 0);
};

const reset = (): void => {
    tipoDte.value = '01';
    tipoDocumento.value = '36';
    receptorNombre.value = 'CLIENTE DE PRUEBAS S.A. DE C.V.';
    receptorDocumento.value = '0614-280390-112-1';
    receptorCorreo.value = 'cliente@ejemplo.com';
    items.value = [
        {
            descripcion: 'Servicios de Desarrollo de Software DTE',
            cantidad: 1,
            precioUni: 100,
        },
    ];
};

watch(
    () => props.isOpen,
    (isOpen) => {
        if (isOpen) {
            reset();
        }
    },
);

const handleOpenChange = (value: boolean): void => {
    emit('update:isOpen', value);
};

const submit = (): void => {
    emit('created', {
        tipoDte: tipoDte.value,
        receptorNombre: receptorNombre.value,
        receptorDocumento: receptorDocumento.value,
        receptorCorreo: receptorCorreo.value,
        items: items.value.map((item) => ({ ...item })),
        montoTotal: total.value,
    });
    emit('update:isOpen', false);
};
</script>

<template>
    <Modal
        :open="props.isOpen"
        title="Emitir Documento Tributario Electrónico (DTE)"
        subtitle="Complete la información para generar y transmitir el comprobante fiscal al MH."
        size="xl"
        @update:open="handleOpenChange"
    >
        <form class="space-y-5 pt-2 text-sm" @submit.prevent="submit">
            <div
                class="rounded-xl border border-primary-200 bg-primary-50 p-3 text-xs text-primary-700 dark:border-primary-800 dark:bg-primary-950/40 dark:text-primary-300"
            >
                Modo UI: esta emisión se agrega únicamente al listado local de
                demostración.
            </div>

            <FormSelect
                v-model="tipoDte"
                id="tipo-dte"
                name="tipoDte"
                label="Tipo de Comprobante"
                :options="typeOptions"
            />

            <Card title="Datos del Receptor / Cliente">
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <FormInput
                            v-model="receptorNombre"
                            id="receptor-nombre"
                            name="receptorNombre"
                            type="text"
                            label="Nombre o Razón Social"
                            small
                        />
                    </div>
                    <FormSelect
                        v-model="tipoDocumento"
                        id="tipo-documento"
                        name="tipoDocumento"
                        label="Tipo de Documento"
                        small
                        :options="documentOptions"
                    />
                    <FormInput
                        v-model="receptorDocumento"
                        id="receptor-documento"
                        name="receptorDocumento"
                        type="text"
                        label="Nº Documento"
                        small
                    />
                    <div class="sm:col-span-2">
                        <FormInput
                            v-model="receptorCorreo"
                            id="receptor-correo"
                            name="receptorCorreo"
                            type="email"
                            label="Correo Electrónico"
                            small
                        />
                    </div>
                </div>
            </Card>

            <Card title="Ítems del Documento" col-in-mobile>
                <template #headerButtons>
                    <BaseButton
                        type="button"
                        variant="outline"
                        size="small"
                        @click="addItem"
                    >
                        Agregar Ítem
                    </BaseButton>
                </template>

                <DataTable
                    :columns="itemColumns"
                    :data="items"
                    :show-search="false"
                    actions-label="Acción"
                >
                    <template #description="{ row }">
                        <FormInput
                            :model-value="row.descripcion"
                            :id="`item-desc-${itemIndex(row)}`"
                            :name="`item-${itemIndex(row)}-descripcion`"
                            type="text"
                            label=""
                            small
                            @update:model-value="updateDescription(row, $event)"
                        />
                    </template>

                    <template #quantity="{ row }">
                        <FormInput
                            :model-value="row.cantidad"
                            :id="`item-qty-${itemIndex(row)}`"
                            :name="`item-${itemIndex(row)}-cantidad`"
                            type="number"
                            label=""
                            small
                            @update:model-value="updateQuantity(row, $event)"
                        />
                    </template>

                    <template #unitPrice="{ row }">
                        <FormInput
                            :model-value="row.precioUni"
                            :id="`item-price-${itemIndex(row)}`"
                            :name="`item-${itemIndex(row)}-precio`"
                            type="number"
                            label=""
                            small
                            @update:model-value="updateUnitPrice(row, $event)"
                        />
                    </template>

                    <template #actions="{ row }">
                        <BaseButton
                            type="button"
                            variant="outline"
                            size="small"
                            :disabled="items.length <= 1"
                            @click.stop="removeItem(row)"
                        >
                            Quitar
                        </BaseButton>
                    </template>
                </DataTable>
            </Card>

            <Card>
                <div
                    class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between"
                >
                    <span class="text-xs text-gray-500 dark:text-gray-400"
                        >El monto se calcula automáticamente según el tipo de
                        comprobante.</span
                    >
                    <div
                        class="text-right text-xs text-gray-500 dark:text-gray-400"
                    >
                        <div>
                            Subtotal:
                            <strong>${{ subtotal.toFixed(2) }}</strong>
                        </div>
                        <div v-if="tipoDte === '03'">
                            IVA (13%): <strong>${{ iva.toFixed(2) }}</strong>
                        </div>
                        <div
                            class="mt-1 text-base font-extrabold text-primary-600 dark:text-primary-400"
                        >
                            Total: ${{ total.toFixed(2) }} USD
                        </div>
                    </div>
                </div>
            </Card>

            <div
                class="flex justify-end gap-2 border-t border-gray-200 pt-4 dark:border-gray-800"
            >
                <BaseButton
                    type="button"
                    variant="outline"
                    size="auto"
                    @click="emit('update:isOpen', false)"
                    >Cancelar</BaseButton
                >
                <BaseButton type="submit" variant="primary" size="auto"
                    >Emitir DTE</BaseButton
                >
            </div>
        </form>
    </Modal>
</template>
