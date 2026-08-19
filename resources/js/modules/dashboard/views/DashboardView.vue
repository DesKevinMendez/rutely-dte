<script setup lang="ts">
import { BaseButton } from 'ornito';
import { useAuth } from '@/core/stores/auth';
import DashboardMetrics from '../components/DashboardMetrics.vue';
import { useDashboardUi } from '../composables/useDashboardUi';

const auth = useAuth();
const { metrics, lastUpdated, refreshMetrics } = useDashboardUi();
</script>

<template>
    <div class="space-y-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <div class="mb-2 inline-flex rounded-full border border-primary-200 bg-primary-50 px-2.5 py-1 text-xs font-semibold text-primary-700 dark:border-primary-800 dark:bg-primary-950/40 dark:text-primary-300">
                    Datos de demostración
                </div>
                <h1 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white">
                    Resumen de Facturación Electrónica (DTE)
                </h1>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    Métricas generales de transmisión de comprobantes fiscales al Ministerio de Hacienda
                </p>
                <p v-if="auth.user?.email" class="mt-2 text-sm font-medium text-gray-700 dark:text-gray-300">
                    {{ auth.user.email }}
                </p>
            </div>
            <BaseButton variant="outline" size="auto" @click="refreshMetrics">
                Actualizar Datos
            </BaseButton>
        </div>

        <DashboardMetrics :metrics="metrics" />

        <p class="text-right text-xs text-gray-400 dark:text-gray-500">
            Última actualización: {{ lastUpdated.toLocaleString('es-SV') }}
        </p>
    </div>
</template>
