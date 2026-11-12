import { ref } from 'vue';
import { useRequest } from '@/core/composables/useRequest';
import type { ApiResponse } from '@/core/types/api.types';
import type { DteApiRecord } from '@/modules/dtes/types/dte.types';

export function useQueueDteUi() {
    const dte = ref<DteApiRecord | null>(null);
    const error = ref<string | null>(null);
    const { get, isLoading } = useRequest();

    const loadDte = async (dteId: string): Promise<void> => {
        if (dte.value?.id !== dteId) {
            dte.value = null;
        }

        const response = await get<ApiResponse<DteApiRecord>>(
            `/api/v1/dtes/${encodeURIComponent(dteId)}`,
        );

        dte.value = response.data.value?.data ?? null;
        error.value = dte.value
            ? null
            : (response.error.value ?? 'No se pudo cargar el DTE.');
    };

    return { dte, error, isLoading, loadDte };
}
