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
    { value: '14', label: 'Factura de Sujeto Excluido (14)' },
];

const documentOptions = [
    { value: '36', label: 'NIT (36)' },
    { value: '13', label: 'DUI (13)' },
    { value: '02', label: 'Carnet de Residente (02)' },
    { value: '03', label: 'Pasaporte (03)' },
    { value: '37', label: 'Otro (37)' },
];

const IVA_RATE = 0.13;
const CONSUMER_IDENTIFICATION_AMOUNT = 25000;

const emptyItem = (): DteItem => ({
    descripcion: '',
    cantidad: 1,
    precioUni: 0,
});

const tipoDte = ref('01');
const tipoDocumento = ref('36');
const receptorNombre = ref('');
const receptorDocumento = ref('');
const receptorCorreo = ref('');
const items = ref<DteItem[]>([emptyItem()]);

const isConsumerInvoice = computed(() => tipoDte.value === '01');
const isTaxCredit = computed(() => tipoDte.value === '03');

const subtotal = computed(() =>
    items.value.reduce(
        (total, item) =>
            total + Number(item.cantidad || 0) * Number(item.precioUni || 0),
        0,
    ),
);
const iva = computed(() => {
    if (isConsumerInvoice.value) {
        return (subtotal.value / (1 + IVA_RATE)) * IVA_RATE;
    }

    return isTaxCredit.value ? subtotal.value * IVA_RATE : 0;
});
const total = computed(() =>
    isTaxCredit.value ? subtotal.value + iva.value : subtotal.value,
);
const requiresIdentification = computed(
    () => isConsumerInvoice.value && total.value >= CONSUMER_IDENTIFICATION_AMOUNT,
);
const ivaLabel = computed(() =>
    isConsumerInvoice.value ? 'IVA incluido (13%)' : 'IVA (13%)',
);
const priceNote = computed(() => {
    if (isConsumerInvoice.value) {
        return 'En la Factura los precios ya incluyen el IVA.';
    }

    return isTaxCredit.value
        ? 'En el Crédito Fiscal los precios no incluyen el IVA, se agrega al total.'
        : 'La Factura de Sujeto Excluido no lleva IVA.';
});

const addItem = (): void => {
    items.value.push(emptyItem());
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
    receptorNombre.value = '';
    receptorDocumento.value = '';
    receptorCorreo.value = '';
    items.value = [emptyItem()];
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
                <p
                    v-if="requiresIdentification"
                    class="mb-3 text-xs text-warning-700 dark:text-warning-400"
                >
                    En operaciones de $25,000.00 o más el nombre y el documento
                    del receptor son obligatorios.
                </p>
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
                            @update:model-value="
                                updateDescription(item, $event)
                            "
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
                    <span class="text-xs text-gray-500 dark:text-gray-400">{{
                        priceNote
                    }}</span>
                    <div
                        class="text-right text-xs text-gray-500 dark:text-gray-400"
                    >
                        <div>
                            Subtotal:
                            <strong>${{ subtotal.toFixed(2) }}</strong>
                        </div>
                        <div v-if="iva > 0">
                            {{ ivaLabel }}:
                            <strong>${{ iva.toFixed(2) }}</strong>
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
