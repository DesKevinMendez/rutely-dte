<script setup lang="ts">
import { Alert, BaseButton } from 'ornito';
import { useAuth } from '@/core/stores/auth';
import { useDashboardUi } from '../composables/useDashboardUi';
import DashboardMetrics from './DashboardMetrics.vue';

const auth = useAuth();
const { metrics, lastUpdated, isLoading, error, refreshMetrics } =
    await useDashboardUi();
</script>

<template>
    <div class="space-y-6">
        <div
            class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
        >
            <div>
                <h1
                    class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white"
                >
                    Resumen de Facturación Electrónica (DTE)
                </h1>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    Métricas generales de transmisión de comprobantes fiscales
                    al Ministerio de Hacienda
                </p>
                <p
                    v-if="auth.user?.email"
                    class="mt-2 text-sm font-medium text-gray-700 dark:text-gray-300"
                >
                    {{ auth.user.email }}
                </p>
            </div>
            <BaseButton
                variant="outline"
                size="auto"
                :disabled="isLoading"
                @click="refreshMetrics"
            >
                {{ isLoading ? 'Actualizando…' : 'Actualizar Datos' }}
            </BaseButton>
        </div>

        <Alert v-if="error" type="danger">
            {{ error }}
        </Alert>

        <DashboardMetrics :metrics="metrics" />

        <p class="text-right text-xs text-gray-400 dark:text-gray-500">
            Última actualización: {{ lastUpdated.toLocaleString('es-SV') }}
        </p>
    </div>
</template>
