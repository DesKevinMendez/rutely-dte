<script setup lang="ts">
import { Alert, BaseButton, Card } from 'ornito';
import QueueTable from './QueueTable.vue';
import { useQueueUi } from '../composables/useQueueUi';

const {
    jobs,
    failedCount,
    lastUpdated,
    isLoading,
    error,
    refreshQueue,
    retryFailed,
} = await useQueueUi();
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
                    Cola de Reintentos de Transmisión (Background Jobs)
                </h1>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    Monitoreo y gestión de transmisiones pendientes o fallidas
                    hacia el Ministerio de Hacienda
                </p>
            </div>
            <div class="flex flex-wrap items-center gap-2.5">
                <BaseButton
                    variant="outline"
                    size="auto"
                    :disabled="isLoading"
                    @click="refreshQueue"
                >
                    {{ isLoading ? 'Actualizando…' : 'Actualizar Cola' }}
                </BaseButton>
                <BaseButton
                    variant="primary"
                    size="auto"
                    :disabled="isLoading || failedCount === 0"
                    @click="retryFailed"
                >
                    Reintentar fallidos ({{ failedCount }})
                </BaseButton>
            </div>
        </div>

        <Alert v-if="error" type="danger">
            {{ error }}
        </Alert>

        <Card title="Transmisiones Pendientes y Fallidas">
            <QueueTable :jobs="jobs" />
        </Card>

        <p class="text-right text-xs text-gray-400 dark:text-gray-500">
            Última actualización: {{ lastUpdated.toLocaleString('es-SV') }}
        </p>
    </div>
</template>
