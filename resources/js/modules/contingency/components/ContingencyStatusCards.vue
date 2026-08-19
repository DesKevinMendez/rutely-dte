<script setup lang="ts">
import { BaseButton, Card } from 'ornito';
import type { CircuitState } from '../types/contingency.types';

defineProps<{
    contingencyActive: boolean;
    circuitState: CircuitState;
    serviceLabel: string;
}>();

const emit = defineEmits<{
    toggle: [];
}>();
</script>

<template>
    <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
        <Card title="Estado del Circuit Breaker">
            <div class="space-y-4">
                <div>
                    <p
                        class="text-sm font-medium text-gray-500 dark:text-gray-400"
                    >
                        Estado del servicio de Hacienda
                    </p>
                    <p
                        class="mt-1 text-xl font-extrabold tracking-wide"
                        :class="
                            circuitState === 'CLOSED'
                                ? 'text-emerald-600 dark:text-emerald-400'
                                : 'text-red-600 dark:text-red-400'
                        "
                    >
                        {{ serviceLabel }}
                    </p>
                </div>
                <div
                    class="space-y-1 border-t border-gray-200 pt-3 text-sm text-gray-500 dark:border-gray-800 dark:text-gray-400"
                >
                    <p>
                        Estado del Circuit Breaker:
                        <strong class="text-gray-900 dark:text-white">{{
                            circuitState
                        }}</strong>
                    </p>
                    <p>
                        Modo Contingencia Automático:
                        <strong class="text-gray-900 dark:text-white">{{
                            contingencyActive ? 'ACTIVADO' : 'DESACTIVADO'
                        }}</strong>
                    </p>
                </div>
            </div>
        </Card>

        <Card title="Modo Contingencia Manual">
            <div class="space-y-4">
                <div>
                    <p
                        class="text-sm font-medium text-gray-500 dark:text-gray-400"
                    >
                        Transmisión diferida activada
                    </p>
                    <p
                        class="mt-1 text-xl font-extrabold text-gray-900 dark:text-white"
                    >
                        {{ contingencyActive ? 'ACTIVADA' : 'INACTIVA' }}
                    </p>
                </div>
                <div
                    class="flex justify-end border-t border-gray-200 pt-3 dark:border-gray-800"
                >
                    <BaseButton
                        :variant="contingencyActive ? 'danger' : 'primary'"
                        size="auto"
                        @click="emit('toggle')"
                    >
                        {{
                            contingencyActive
                                ? 'Desactivar Contingencia'
                                : 'Activar Contingencia'
                        }}
                    </BaseButton>
                </div>
            </div>
        </Card>
    </div>
</template>
