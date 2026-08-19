<script setup lang="ts">
import { BaseButton, Card } from 'ornito';
import QueueTable from '../components/QueueTable.vue';
import { useQueueUi } from '../composables/useQueueUi';

const { jobs, lastUpdated, refreshQueue, retryJob } = useQueueUi();
</script>

<template>
    <div class="space-y-6">
        <div
            class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
        >
            <div>
                <div
                    class="mb-2 inline-flex rounded-full border border-primary-200 bg-primary-50 px-2.5 py-1 text-xs font-semibold text-primary-700 dark:border-primary-800 dark:bg-primary-950/40 dark:text-primary-300"
                >
                    Datos de demostración
                </div>
                <h1
                    class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white"
                >
                    Cola de Reintentos de Transmisión (Background Jobs)
                </h1>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    Monitoreo y gestión de trabajos de envío diferido a los
                    servidores del Ministerio de Hacienda
                </p>
            </div>
            <BaseButton variant="outline" size="auto" @click="refreshQueue"
                >Actualizar Cola</BaseButton
            >
        </div>

        <Card title="Trabajos de Transmisión Registrados">
            <QueueTable :jobs="jobs" @retry="retryJob" />
        </Card>

        <p class="text-right text-xs text-gray-400 dark:text-gray-500">
            Última actualización: {{ lastUpdated.toLocaleString('es-SV') }}
        </p>
    </div>
</template>
