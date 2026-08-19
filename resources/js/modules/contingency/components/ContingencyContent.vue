<script setup lang="ts">
import { Alert, BaseButton } from 'ornito';
import ContingencyGuide from './ContingencyGuide.vue';
import ContingencyStatusCards from './ContingencyStatusCards.vue';
import { useContingencyUi } from '../composables/useContingencyUi';

const {
    contingencyActive,
    circuitState,
    serviceLabel,
    isLoading,
    error,
    refreshStatus,
    toggleActivation,
} = await useContingencyUi();
</script>

<template>
    <div class="space-y-6">
        <div
            class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between"
        >
            <div>
                <h1
                    class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white"
                >
                    Panel de Contingencia (Ministerio de Hacienda)
                </h1>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    Gestión de contingencias según normativa CAT-005 para
                    indisponibilidad del servicio de recepción del MH
                </p>
            </div>
            <BaseButton
                variant="outline"
                size="auto"
                :disabled="isLoading"
                @click="refreshStatus"
            >
                Actualizar Estado
            </BaseButton>
        </div>

        <Alert v-if="error" type="danger">
            {{ error }}
        </Alert>

        <ContingencyStatusCards
            :contingency-active="contingencyActive"
            :circuit-state="circuitState"
            :service-label="serviceLabel"
            :is-updating="isLoading"
            @toggle="toggleActivation"
        />

        <ContingencyGuide />
    </div>
</template>
