import { ref } from 'vue';
import { useRequest } from '@/core/composables/useRequest';
import type { ApiResponse } from '@/core/types/api.types';
import type {
    DashboardApiData,
    DashboardApiMetrics,
    DteDashboardMetrics,
} from '../types/dashboard.types';

const emptyMetrics = (): DteDashboardMetrics => ({
    totalEmitidos: 0,
    totalMonto: 0,
    procesadosCount: 0,
    rechazadosCount: 0,
    invalidatedCount: 0,
    pendingTransmissionsCount: 0,
});

const mapMetrics = (metrics: DashboardApiMetrics): DteDashboardMetrics => ({
    totalEmitidos: metrics.total,
    totalMonto: metrics.total_amount / 100,
    procesadosCount: metrics.processed,
    rechazadosCount: metrics.rejected,
    invalidatedCount: metrics.invalidated,
    pendingTransmissionsCount: metrics.pending_transmissions,
});

export async function useDashboardUi() {
    const metrics = ref<DteDashboardMetrics>(emptyMetrics());
    const lastUpdated = ref(new Date());
    const error = ref<string | null>(null);
    const { get, isLoading } = useRequest();

    const loadDashboard = async (): Promise<void> => {
        const response =
            await get<ApiResponse<DashboardApiData>>('/api/v1/dashboard');

        if (response.data.value) {
            metrics.value = mapMetrics(response.data.value.data.metrics);
            lastUpdated.value = new Date();
            error.value = null;

            return;
        }

        error.value = response.error.value ?? 'No se pudo cargar el dashboard.';
    };

    await loadDashboard();

    return {
        metrics,
        lastUpdated,
        isLoading,
        error,
        refreshMetrics: loadDashboard,
    };
}
