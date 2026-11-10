import { ref } from 'vue';
import { useRequest } from '@/core/composables/useRequest';
import type { ApiResponse } from '@/core/types/api.types';
import type { DteApiRecord } from '../types/dte.types';

export function useDteDetailUi() {
    const dte = ref<DteApiRecord | null>(null);
    const error = ref<string | null>(null);
    const { get, isLoading } = useRequest();

    const loadDte = async (dteId: string): Promise<void> => {
        const response = await get<ApiResponse<DteApiRecord>>(
            `/api/v1/dtes/${encodeURIComponent(dteId)}`,
        );

        dte.value = response.data.value?.data ?? null;
        error.value =
            response.error.value ??
            (dte.value ? null : 'No se encontró el DTE.');
    };

    return { dte, error, isLoading, loadDte };
}
