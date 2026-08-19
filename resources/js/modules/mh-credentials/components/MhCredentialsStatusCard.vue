<script setup lang="ts">
import { Card } from 'ornito';
import type { MhCredentialsMetadata, MhEnvironment } from '../types/mh-credentials.types';

const props = defineProps<{
    metadata: MhCredentialsMetadata | null;
    selectedEnvironment: MhEnvironment;
}>();
</script>

<template>
    <Card title="Estado de Credenciales MH" :subtitle="`Ambiente seleccionado: ${props.selectedEnvironment}`">
        <div v-if="props.metadata" class="space-y-5">
            <div class="flex items-center justify-between">
                <span class="text-sm font-medium text-gray-500 dark:text-gray-400">Estado actual</span>
                <span class="rounded-full border border-emerald-200 bg-emerald-100 px-3 py-1 text-xs font-bold text-emerald-700 dark:border-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-400">
                    {{ props.metadata.active ? 'ACTIVO' : 'INACTIVO' }}
                </span>
            </div>

            <div class="space-y-3 rounded-xl border border-gray-200 bg-gray-50 p-4 text-sm dark:border-gray-700 dark:bg-gray-800/60">
                <div class="flex items-center justify-between gap-3">
                    <span class="text-gray-500 dark:text-gray-400">NIT registrado:</span>
                    <span class="font-mono font-bold text-gray-900 dark:text-white">{{ props.metadata.nit }}</span>
                </div>
                <div class="flex items-center justify-between border-t border-gray-200 pt-2 dark:border-gray-700">
                    <span class="text-gray-500 dark:text-gray-400">Ambiente:</span>
                    <span class="font-semibold text-gray-900 dark:text-white">{{ props.metadata.environment }}</span>
                </div>
                <div class="flex items-center justify-between gap-3 border-t border-gray-200 pt-2 dark:border-gray-700">
                    <span class="text-gray-500 dark:text-gray-400">Última actualización:</span>
                    <span class="text-right text-gray-700 dark:text-gray-300">{{ new Date(props.metadata.updatedAt).toLocaleString('es-SV') }}</span>
                </div>
            </div>

            <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-3 text-xs text-emerald-800 dark:border-emerald-800/60 dark:bg-emerald-950/40 dark:text-emerald-300">
                Credenciales encriptadas con seguridad <strong>AES-256-GCM</strong> en la base de datos.
            </div>
        </div>

        <div v-else class="space-y-2 py-6 text-center">
            <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full border border-amber-200 bg-amber-50 font-bold text-amber-600 dark:border-amber-800 dark:bg-amber-950/60 dark:text-amber-400">!</div>
            <p class="text-sm font-bold text-gray-900 dark:text-white">Sin Credenciales Registradas</p>
            <p class="text-xs text-gray-500 dark:text-gray-400">No hay credenciales registradas para el ambiente de <strong>{{ props.selectedEnvironment }}</strong>.</p>
        </div>
    </Card>
</template>
