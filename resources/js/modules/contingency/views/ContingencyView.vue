<script setup lang="ts">
import ContingencyGuide from '../components/ContingencyGuide.vue';
import ContingencyStatusCards from '../components/ContingencyStatusCards.vue';
import { useContingencyUi } from '../composables/useContingencyUi';

const { contingencyActive, circuitState, serviceLabel, toggleActivation } = useContingencyUi();
</script>

<template>
    <div class="space-y-6">
        <div>
            <div class="mb-2 inline-flex rounded-full border border-amber-200 bg-amber-50 px-2.5 py-1 text-xs font-semibold text-amber-700 dark:border-amber-800 dark:bg-amber-950/40 dark:text-amber-300">
                Simulación UI
            </div>
            <h1 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white">Panel de Contingencia (Ministerio de Hacienda)</h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                Gestión de contingencias según normativa CAT-005 para indisponibilidad del servicio de recepción del MH
            </p>
        </div>

        <ContingencyStatusCards
            :contingency-active="contingencyActive"
            :circuit-state="circuitState"
            :service-label="serviceLabel"
            @toggle="toggleActivation"
        />

        <ContingencyGuide />
    </div>
</template>
