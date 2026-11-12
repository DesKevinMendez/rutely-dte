<script setup lang="ts">
import {
    Alert,
    BaseButton,
    ConfirmationModal,
    FormInput,
    SwitchInput,
    yup,
} from 'ornito';
import { Form } from 'vee-validate';
import { computed, ref, toRef, watch } from 'vue';
import useDteCorrelative from '@/modules/superadmin/companies/composables/useDteCorrelative';

const props = defineProps<{
    companyId: string;
    establishmentCode: string;
    posCode: string;
}>();
const canUpdate = ref(false);
const isConfirmationOpen = ref(false);
const correlativeRules = yup
    .number()
    .typeError('Ingresa un número válido.')
    .integer('Ingresa un número entero.')
    .min(1, 'El correlativo debe ser mayor que cero.')
    .max(999999999999999, 'El correlativo no puede superar 15 dígitos.')
    .required('El correlativo es obligatorio.');
const {
    nextValue,
    fiscalYear,
    error,
    saved,
    isLoading,
    isSaving,
    saveCorrelative,
} = useDteCorrelative(toRef(() => props.companyId));

const correlativeHelp = computed(() => {
    const { establishmentCode, posCode } = props;
    const scope = `El correlativo aplica a todos los tipos de DTE del establecimiento durante ${fiscalYear.value}.`;

    if (
        !establishmentCode ||
        !posCode ||
        !correlativeRules.isValidSync(nextValue.value)
    ) {
        return scope;
    }

    const correlative = String(Number(nextValue.value)).padStart(15, '0');
    const controlNumber = `DTE-01-${establishmentCode}${posCode}-${correlative}`;

    return `Para una factura (01), el número de control sería ${controlNumber}. ${scope}`;
});

watch(
    () => props.companyId,
    () => {
        canUpdate.value = false;
        isConfirmationOpen.value = false;
    },
);

const openConfirmation = (): void => {
    if (canUpdate.value && !isSaving.value) {
        isConfirmationOpen.value = true;
    }
};

const closeConfirmation = (): void => {
    if (!isSaving.value) {
        isConfirmationOpen.value = false;
    }
};

const confirmUpdate = async (): Promise<void> => {
    if (!canUpdate.value || isSaving.value) {
        return;
    }

    await saveCorrelative();
    isConfirmationOpen.value = false;

    if (saved.value) {
        canUpdate.value = false;
    }
};
</script>

<template>
    <div>
        <Form
            v-if="!isLoading && fiscalYear !== null"
            class="mt-6 space-y-4"
            @submit="openConfirmation"
        >
            <SwitchInput
                v-model="canUpdate"
                label="Actualizar correlativo"
                role="group"
                aria-label="Actualizar correlativo"
                :disabled="isSaving"
            />
            <FormInput
                id="dte-next-correlative"
                v-model="nextValue"
                name="next_value"
                type="number"
                label="Próximo correlativo DTE"
                :min="1"
                :max="999999999999999"
                :rules="correlativeRules"
                :disabled="!canUpdate || isSaving"
                :help="correlativeHelp"
            />
            <Alert v-if="saved" type="success">Correlativo actualizado.</Alert>
            <BaseButton
                type="submit"
                size="auto"
                :loading="isSaving"
                :disabled="!canUpdate || isSaving"
                >Guardar correlativo</BaseButton
            >
        </Form>
        <Alert v-if="error" class="mt-4" type="danger">{{ error }}</Alert>
        <ConfirmationModal
            :open="isConfirmationOpen"
            :loading="isSaving"
            title="Actualizar correlativo DTE"
            :subtitle="`El próximo DTE usará el correlativo ${nextValue}. Escribe 'Confirmar' para guardar el cambio.`"
            @close="closeConfirmation"
            @confirm="confirmUpdate"
        />
    </div>
</template>
