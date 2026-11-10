import { ref } from 'vue';
import { useRequest } from '@/core/composables/useRequest';
import type { ApiResponse, PaginatedApiResponse } from '@/core/types/api.types';
import type { DteApiRecord } from '../types/dte.types';

interface PaymentMethod {
    code: string;
    description: string;
}

export function useDteDetailUi() {
    const dte = ref<DteApiRecord | null>(null);
    const error = ref<string | null>(null);
    const paymentMethodLabels = ref<Record<string, string>>({});
    const { get, isLoading } = useRequest();
    const { get: getPaymentMethods } = useRequest();

    const loadDte = async (dteId: string): Promise<void> => {
        const [response, methodsResponse] = await Promise.all([
            get<ApiResponse<DteApiRecord>>(
                `/api/v1/dtes/${encodeURIComponent(dteId)}`,
            ),
            getPaymentMethods<PaginatedApiResponse<PaymentMethod>>(
                '/api/v1/data/payment-methods?per_page=100',
            ),
        ]);

        dte.value = response.data.value?.data ?? null;
        paymentMethodLabels.value = Object.fromEntries(
            (methodsResponse.data.value?.data ?? []).map((method) => [
                method.code,
                method.description,
            ]),
        );
        error.value =
            response.error.value ??
            (dte.value ? null : 'No se encontró el DTE.');
    };

    return { dte, error, isLoading, loadDte, paymentMethodLabels };
}
