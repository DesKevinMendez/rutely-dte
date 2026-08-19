<script setup lang="ts">
import { BaseButton, Card, FormInput, FormSelect, Modal } from 'ornito';
import { computed, ref, watch } from 'vue';
import type { DteDraft, DteItem } from '../types/dte.types';

const { isOpen, isSubmitting = false } = defineProps<{
    isOpen: boolean;
    isSubmitting?: boolean;
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

const subtotal = computed(() =>
    items.value.reduce(
        (total, item) =>
            total + Number(item.cantidad || 0) * Number(item.precioUni || 0),
        0,
    ),
);
const iva = computed(() => subtotal.value * 0.13);
const total = computed(() => subtotal.value + iva.value);

const addItem = (): void => {
    items.value.push({ descripcion: '', cantidad: 1, precioUni: 0 });
};

const removeItem = (index: number): void => {
    if (items.value.length > 1) {
        items.value.splice(index, 1);
    }
};

const updateDescription = (item: DteItem, value: unknown): void => {
    item.descripcion = String(value ?? '');
};

const updateQuantity = (item: DteItem, value: unknown): void => {
    item.cantidad = Number(value ?? 0);
};

const updateUnitPrice = (item: DteItem, value: unknown): void => {
    item.precioUni = Number(value ?? 0);
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
    () => isOpen,
    (open) => {
        if (open) {
            reset();
        }
    },
);

const handleOpenChange = (value: boolean): void => {
    if (!isSubmitting) {
        emit('update:isOpen', value);
    }
};

const submit = (): void => {
    if (isSubmitting) {
        return;
    }

    emit('created', {
        tipoDte: tipoDte.value,
        tipoDocumento: tipoDocumento.value,
        receptorNombre: receptorNombre.value,
        receptorDocumento: receptorDocumento.value,
        receptorCorreo: receptorCorreo.value,
        items: items.value.map((item) => ({ ...item })),
        montoTotal: total.value,
    });
};
</script>

<template>
    <Modal
        :open="isOpen"
        title="Emitir Documento Tributario Electrónico (DTE)"
        subtitle="Complete la información para generar y transmitir el comprobante fiscal al MH."
        size="xl"
        @update:open="handleOpenChange"
    >
        <form class="space-y-5 pt-2 text-sm" @submit.prevent="submit">
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

                <div class="space-y-3">
                    <div
                        class="hidden grid-cols-[minmax(0,1fr)_7rem_9rem_5rem] gap-3 px-1 text-xs font-semibold text-gray-500 sm:grid dark:text-gray-400"
                    >
                        <span>Descripción</span>
                        <span>Cant.</span>
                        <span>Precio Uni ($)</span>
                        <span>Acción</span>
                    </div>

                    <div
                        v-for="(item, index) in items"
                        :key="index"
                        class="grid grid-cols-1 gap-3 rounded-lg border border-gray-200 p-3 sm:grid-cols-[minmax(0,1fr)_7rem_9rem_5rem] sm:items-end dark:border-gray-700"
                    >
                        <FormInput
                            :model-value="item.descripcion"
                            :id="`item-desc-${index}`"
                            :name="`item-${index}-descripcion`"
                            type="text"
                            label="Descripción"
                            small
                            @update:model-value="updateDescription(item, $event)"
                        />
                        <FormInput
                            :model-value="item.cantidad"
                            :id="`item-qty-${index}`"
                            :name="`item-${index}-cantidad`"
                            type="number"
                            label="Cant."
                            small
                            @update:model-value="updateQuantity(item, $event)"
                        />
                        <FormInput
                            :model-value="item.precioUni"
                            :id="`item-price-${index}`"
                            :name="`item-${index}-precio`"
                            type="number"
                            label="Precio Uni ($)"
                            small
                            @update:model-value="updateUnitPrice(item, $event)"
                        />
                        <BaseButton
                            type="button"
                            variant="outline"
                            size="small"
                            :disabled="items.length <= 1"
                            @click="removeItem(index)"
                        >
                            Quitar
                        </BaseButton>
                    </div>
                </div>
            </Card>

            <Card>
                <div
                    class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between"
                >
                    <span class="text-xs text-gray-500 dark:text-gray-400"
                        >El monto se calcula automáticamente con IVA según el
                        flujo actual del backend.</span
                    >
                    <div
                        class="text-right text-xs text-gray-500 dark:text-gray-400"
                    >
                        <div>
                            Subtotal:
                            <strong>${{ subtotal.toFixed(2) }}</strong>
                        </div>
                        <div>
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
                    :disabled="isSubmitting"
                    @click="emit('update:isOpen', false)"
                    >Cancelar</BaseButton
                >
                <BaseButton
                    type="submit"
                    variant="primary"
                    size="auto"
                    :disabled="isSubmitting"
                >
                    {{ isSubmitting ? 'Emitiendo…' : 'Emitir DTE' }}
                </BaseButton>
            </div>
        </form>
    </Modal>
</template>
