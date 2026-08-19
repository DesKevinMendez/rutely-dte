import { ref } from 'vue';
import type { DteDashboardMetrics } from '../types/dashboard.types';

const initialMetrics: DteDashboardMetrics = {
    totalEmitidos: 1284,
    totalMonto: 87423.58,
    procesadosCount: 1249,
    rechazadosCount: 12,
    contingenciaCount: 23,
};

export function useDashboardUi() {
    const metrics = ref<DteDashboardMetrics>({ ...initialMetrics });
    const lastUpdated = ref(new Date());

    const refreshMetrics = (): void => {
        lastUpdated.value = new Date();
    };

    return {
        metrics,
        lastUpdated,
        refreshMetrics,
    };
}
