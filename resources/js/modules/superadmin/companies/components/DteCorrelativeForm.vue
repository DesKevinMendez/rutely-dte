<script setup lang="ts">
import { Alert, BaseButton, FormInput, yup } from 'ornito';
import { Form } from 'vee-validate';
import { toRef } from 'vue';
import useDteCorrelative from '@/modules/superadmin/companies/composables/useDteCorrelative';

const props = defineProps<{ companyId: string }>();
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
</script>

<template>
    <div>
        <Form
            v-if="!isLoading && fiscalYear !== null"
            class="mt-6 space-y-4"
            @submit="saveCorrelative"
        >
            <FormInput
                id="dte-next-correlative"
                v-model="nextValue"
                name="next_value"
                type="number"
                label="Próximo correlativo DTE"
                :min="1"
                :max="999999999999999"
                :rules="correlativeRules"
                :help="`Aplica a todos los tipos de DTE del establecimiento durante ${fiscalYear}.`"
            />
            <Alert v-if="saved" type="success">Correlativo actualizado.</Alert>
            <BaseButton type="submit" size="auto" :loading="isSaving"
                >Guardar correlativo</BaseButton
            >
        </Form>
        <Alert v-if="error" class="mt-4" type="danger">{{ error }}</Alert>
    </div>
</template>
