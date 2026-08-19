<script setup lang="ts">
import { BaseButton, Card, FormInput } from 'ornito';
import { ref, watch } from 'vue';
import type {
    MhCredentialsPayload,
    MhEnvironment,
} from '../types/mh-credentials.types';

const {
    selectedEnvironment,
    saveSuccess,
    isSubmitting = false,
} = defineProps<{
    selectedEnvironment: MhEnvironment;
    saveSuccess: boolean;
    isSubmitting?: boolean;
}>();

const emit = defineEmits<{
    environmentChange: [environment: MhEnvironment];
    save: [payload: MhCredentialsPayload];
}>();

const nit = ref('');
const password = ref('');

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
    if (
        nit.value.replace(/\D/g, '').length !== 14 ||
        !password.value.trim() ||
        isSubmitting
    ) {
        return;
    }

    emit('save', { nit: nit.value, password: password.value });
};
</script>

<template>
    <Card
        title="Credenciales Portal MH"
        subtitle="Configure o rote el NIT y la contraseña de autenticación para el portal del Ministerio de Hacienda."
    >
        <form class="space-y-5" @submit.prevent="submit">
            <div
                v-if="saveSuccess"
                class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800 dark:border-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300"
            >
                Credenciales guardadas correctamente para el ambiente de
                <strong>{{ selectedEnvironment }}</strong>.
            </div>

            <div
                class="rounded-xl border border-blue-200 bg-blue-50 p-4 text-sm text-blue-800 dark:border-blue-800 dark:bg-blue-950/60 dark:text-blue-300"
            >
                Las credenciales no admiten actualización parcial. Cada
                actualización requiere ingresar tanto el
                <strong>NIT</strong> como la
                <strong>contraseña</strong> completos para el ambiente
                seleccionado.
            </div>

            <div>
                <p
                    class="mb-2 block text-sm font-semibold text-gray-700 dark:text-gray-300"
                >
                    Ambiente de Destino
                </p>
                <div class="grid grid-cols-2 gap-3">
                    <BaseButton
                        type="button"
                        variant="outline"
                        size="auto"
                        class="h-auto justify-start py-3 text-left"
                        :disabled="isSubmitting"
                        :class="
                            selectedEnvironment === 'PRUEBAS'
                                ? 'border-primary-500 bg-primary-50 dark:bg-primary-950/30'
                                : ''
                        "
                        @click="emit('environmentChange', 'PRUEBAS')"
                    >
                        <span class="flex flex-col items-start leading-tight">
                            <span class="font-semibold">Pruebas (Sandbox)</span>
                            <span
                                class="mt-1 text-xs font-normal text-gray-500 dark:text-gray-400"
                                >Ambiente de pruebas MH</span
                            >
                        </span>
                    </BaseButton>
                    <BaseButton
                        type="button"
                        variant="outline"
                        size="auto"
                        class="h-auto justify-start py-3 text-left"
                        :disabled="isSubmitting"
                        :class="
                            selectedEnvironment === 'PRODUCCION'
                                ? 'border-primary-500 bg-primary-50 dark:bg-primary-950/30'
                                : ''
                        "
                        @click="emit('environmentChange', 'PRODUCCION')"
                    >
                        <span class="flex flex-col items-start leading-tight">
                            <span class="font-semibold">Producción</span>
                            <span
                                class="mt-1 text-xs font-normal text-gray-500 dark:text-gray-400"
                                >Emisión real de DTEs</span
                            >
                        </span>
                    </BaseButton>
                </div>
            </div>

            <FormInput
                v-model="nit"
                id="mh-nit"
                name="nit"
                type="text"
                label="NIT de la Empresa"
                placeholder="0614-280390-112-1"
                help="NIT registrado en el Ministerio de Hacienda para este ambiente."
            />

            <FormInput
                v-model="password"
                id="mh-password"
                name="password"
                type="password"
                label="Contraseña del Portal MH"
                placeholder="Ingrese la contraseña del portal MH"
                help="Contraseña para la obtención de tokens de transmisión en /seguridad/auth."
            />

            <div
                class="flex justify-end border-t border-gray-200 pt-3 dark:border-gray-700"
            >
                <BaseButton
                    type="submit"
                    variant="primary"
                    size="auto"
                    :disabled="
                        nit.replace(/\D/g, '').length !== 14 ||
                        !password.trim() ||
                        isSubmitting
                    "
                >
                    {{
                        isSubmitting
                            ? 'Guardando…'
                            : 'Guardar Credenciales MH'
                    }}
                </BaseButton>
            </div>
        </form>
    </Card>
</template>
