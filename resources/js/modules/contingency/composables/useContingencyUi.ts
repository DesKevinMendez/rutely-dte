import { computed, ref } from 'vue';
import { useRequest } from '@/core/composables/useRequest';
import type { ApiResponse } from '@/core/types/api.types';
import type {
    CircuitState,
    ContingencyApiStatus,
    ContingencyUpdatePayload,
} from '../types/contingency.types';

export async function useContingencyUi() {
    const contingencyActive = ref(false);
    const circuitState = ref<CircuitState>('CLOSED');
    const error = ref<string | null>(null);
    const { get, put, isLoading } = useRequest();

    const serviceLabel = computed(() =>
        circuitState.value === 'CLOSED'
            ? 'OPERATIVO (CLOSED)'
            : 'EN CONTINGENCIA (MANUAL_OPEN)',
    );

    const applyStatus = (status: ContingencyApiStatus): void => {
        contingencyActive.value = status.contingency_active;
        circuitState.value = status.circuit_state;
    };

    const loadStatus = async (): Promise<boolean> => {
        const response = await get<ApiResponse<ContingencyApiStatus>>(
            '/api/v1/contingency',
        );

        if (response.data.value) {
            applyStatus(response.data.value.data);
            error.value = null;

            return true;
        }

        error.value =
            response.error.value ??
            'No se pudo cargar el estado de contingencia.';

        return false;
    };

    const updateStatus = async (active: boolean): Promise<boolean> => {
        const response = await put<
            ApiResponse<ContingencyApiStatus>,
            ContingencyUpdatePayload
        >('/api/v1/contingency', { active });

        if (response.data.value) {
            applyStatus(response.data.value.data);
            error.value = null;

            return true;
        }

        error.value =
            response.error.value ?? 'No se pudo actualizar la contingencia.';

        return false;
    };

    const toggleActivation = async (): Promise<void> => {
        await updateStatus(!contingencyActive.value);
    };

    await loadStatus();

    return {
        contingencyActive,
        circuitState,
        serviceLabel,
        isLoading,
        error,
        refreshStatus: loadStatus,
        toggleActivation,
    };
}
