<script setup lang="ts">
import { Alert, Badge, Card } from 'ornito';
import { formatIdentityDocument } from '@/core/composables/formatIdentityDocument';
import type {
    MhCredentialsMetadata,
    MhEnvironment,
} from '../types/mh-credentials.types';

const { metadata, selectedEnvironment } = defineProps<{
    metadata: MhCredentialsMetadata | null;
    selectedEnvironment: MhEnvironment;
}>();

const loginStates = {
    authenticated: {
        tone: 'success',
        label: 'Exitoso',
        note: 'Hacienda aceptó estas credenciales y están almacenadas de forma segura.',
    },
    rejected: {
        tone: 'danger',
        label: 'Rechazado',
        note: 'Hacienda rechazó estas credenciales. Actualícelas para poder transmitir.',
    },
    unverified: {
        tone: 'warning',
        label: 'Sin verificar',
        note: 'No hay un login reciente con estas credenciales. Se comprobarán al transmitir.',
    },
} as const;
</script>

<template>
    <Card
        title="Estado de Credenciales MH"
        :subtitle="`Ambiente seleccionado: ${selectedEnvironment}`"
    >
        <div v-if="metadata" class="space-y-5">
            <div class="flex items-center justify-between">
                <span
                    class="text-sm font-medium text-gray-500 dark:text-gray-400"
                    >Estado actual</span
                >
                <span
                    class="rounded-full border px-3 py-1 text-xs font-bold"
                    :class="
                        metadata.active
                            ? 'border-emerald-200 bg-emerald-100 text-emerald-700 dark:border-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-400'
                            : 'border-red-200 bg-red-100 text-red-700 dark:border-red-800 dark:bg-red-950/60 dark:text-red-400'
                    "
                >
                    {{ metadata.active ? 'ACTIVO' : 'INACTIVO' }}
                </span>
            </div>

            <div
                class="space-y-3 rounded-xl border border-gray-200 bg-gray-50 p-4 text-sm dark:border-gray-700 dark:bg-gray-800/60"
            >
                <div class="flex items-center justify-between gap-3">
                    <span class="text-gray-500 dark:text-gray-400"
                        >NIT registrado:</span
                    >
                    <span
                        class="font-mono font-bold text-gray-900 dark:text-white"
                        >{{ formatIdentityDocument(metadata.nit) }}</span
                    >
                </div>
                <div
                    class="flex items-center justify-between border-t border-gray-200 pt-2 dark:border-gray-700"
                >
                    <span class="text-gray-500 dark:text-gray-400"
                        >Ambiente:</span
                    >
                    <span class="font-semibold text-gray-900 dark:text-white">{{
                        metadata.environment
                    }}</span>
                </div>
                <div
                    class="flex items-center justify-between gap-3 border-t border-gray-200 pt-2 dark:border-gray-700"
                >
                    <span class="text-gray-500 dark:text-gray-400"
                        >Login en Hacienda:</span
                    >
                    <Badge
                        :variant="loginStates[metadata.loginStatus].tone"
                        text="xs"
                    >
                        {{ loginStates[metadata.loginStatus].label }}
                    </Badge>
                </div>
                <div
                    class="flex items-center justify-between gap-3 border-t border-gray-200 pt-2 dark:border-gray-700"
                >
                    <span class="text-gray-500 dark:text-gray-400"
                        >Última actualización:</span
                    >
                    <span class="text-right text-gray-700 dark:text-gray-300">
                        {{
                            metadata.updatedAt
                                ? new Date(metadata.updatedAt).toLocaleString(
                                      'es-SV',
                                  )
                                : 'N/A'
                        }}
                    </span>
                </div>
            </div>

            <Alert :type="loginStates[metadata.loginStatus].tone">
                {{ loginStates[metadata.loginStatus].note }}
            </Alert>
        </div>

        <div v-else class="space-y-2 py-6 text-center">
            <div
                class="mx-auto flex h-12 w-12 items-center justify-center rounded-full border border-amber-200 bg-amber-50 font-bold text-amber-600 dark:border-amber-800 dark:bg-amber-950/60 dark:text-amber-400"
            >
                !
            </div>
            <p class="text-sm font-bold text-gray-900 dark:text-white">
                Sin Credenciales Registradas
            </p>
            <p class="text-xs text-gray-500 dark:text-gray-400">
                No hay credenciales registradas para el ambiente de
                <strong>{{ selectedEnvironment }}</strong
                >.
            </p>
        </div>
    </Card>
</template>
