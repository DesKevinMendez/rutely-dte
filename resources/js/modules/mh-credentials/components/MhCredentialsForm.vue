<script setup lang="ts">
import { Alert, BaseButton, Card, FormInput } from 'ornito';
import { computed, ref, watch } from 'vue';
import type {
    MhCredentialsPayload,
    MhEnvironment,
} from '../types/mh-credentials.types';

const {
    selectedEnvironment,
    saveSuccess,
    saveError = null,
    isSubmitting = false,
} = defineProps<{
    selectedEnvironment: MhEnvironment;
    saveSuccess: boolean;
    saveError?: string | null;
    isSubmitting?: boolean;
}>();

const emit = defineEmits<{
    save: [payload: MhCredentialsPayload];
}>();

const nit = ref('');
const password = ref('');

const nitDigits = computed(() => nit.value.replace(/\D/g, ''));
const canSubmit = computed(
    () =>
        [9, 14].includes(nitDigits.value.length) &&
        password.value.length > 0 &&
        !isSubmitting,
);

watch(
    () => saveSuccess,
    (success) => {
        if (success) {
            nit.value = '';
            password.value = '';
        }
    },
);

const submit = (): void => {
    if (!canSubmit.value) {
        return;
    }

    emit('save', { nit: nit.value, password: password.value });
};
</script>

<template>
    <Card
        title="Credenciales del Usuario API"
        subtitle="Configure o rote el NIT y la contraseña con los que el sistema se autentica en el API del Ministerio de Hacienda."
    >
        <form class="space-y-5" @submit.prevent="submit">
            <div
                class="rounded-xl border border-blue-200 bg-blue-50 p-4 text-sm text-blue-800 dark:border-blue-800 dark:bg-blue-950/60 dark:text-blue-300"
            >
                Las credenciales no admiten actualización parcial. Cada
                actualización requiere ingresar tanto el
                <strong>NIT</strong> como la
                <strong>contraseña del Usuario API</strong> completos para el
                ambiente seleccionado.
            </div>

            <FormInput
                v-model="nit"
                id="mh-nit"
                name="nit"
                type="text"
                label="NIT de la Empresa"
                placeholder="0614-280390-112-1"
                mask="####-######-###-#"
                help="NIT registrado en el Ministerio de Hacienda para este ambiente."
            />

            <FormInput
                v-model="password"
                id="mh-password"
                name="password"
                type="password"
                label="Contraseña del Usuario API"
                placeholder="Ingrese la contraseña del Usuario API"
                help="Se define en el sitio Emisores DTE de Hacienda: inicie sesión en el ambiente seleccionado y abra Gestión de Usuario API, opción Cambiar Password. No use la clave de Servicios DGII ni la de la llave privada."
            />

            <Alert v-if="saveError" type="danger">
                {{ saveError }}
            </Alert>
            <Alert v-else-if="saveSuccess" type="success">
                Login exitoso en Hacienda. Credenciales guardadas para el
                ambiente de
                <strong>{{ selectedEnvironment }}</strong
                >.
            </Alert>

            <div
                class="flex justify-end border-t border-gray-200 pt-3 dark:border-gray-700"
            >
                <BaseButton
                    type="submit"
                    variant="primary"
                    size="auto"
                    :disabled="!canSubmit"
                >
                    {{
                        isSubmitting
                            ? 'Verificando con Hacienda…'
                            : 'Guardar Credenciales MH'
                    }}
                </BaseButton>
            </div>
        </form>
    </Card>
</template>
