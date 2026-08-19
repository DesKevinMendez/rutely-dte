<script setup lang="ts">
import { StatCard } from 'ornito';
import type { DteDashboardMetrics } from '../types/dashboard.types';

defineProps<{
    metrics: DteDashboardMetrics;
}>();
</script>

<template>
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <StatCard
            title="Total Emitidos"
            :value="metrics.totalEmitidos"
            description="Documentos procesados"
            change-type="info"
        />
        <StatCard
            title="Monto Total ($ USD)"
            :value="`$${metrics.totalMonto.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`"
            description="Facturación bruta acumulada"
            change-type="info"
        />
        <StatCard
            title="Exitosos (MH)"
            :value="metrics.procesadosCount"
            description="Aprobados con sello de recibido"
            change-type="positive"
        />
        <StatCard
            title="Rechazados / Contingencia"
            :value="metrics.rechazadosCount + metrics.contingenciaCount"
            description="Requieren atención o reintento"
            change-type="negative"
        />
    </div>
</template>
