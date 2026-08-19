import { computed, ref } from 'vue';
import type { CircuitState } from '../types/contingency.types';

export function useContingencyUi() {
    const contingencyActive = ref(false);
    const circuitState = ref<CircuitState>('CLOSED');

    const serviceLabel = computed(() =>
        circuitState.value === 'CLOSED'
            ? 'OPERATIVO (CLOSED)'
            : 'EN CONTINGENCIA (OPEN)',
    );

    const toggleActivation = (): void => {
        contingencyActive.value = !contingencyActive.value;
        circuitState.value = contingencyActive.value ? 'OPEN' : 'CLOSED';
    };

    return {
        contingencyActive,
        circuitState,
        serviceLabel,
        toggleActivation,
    };
}
