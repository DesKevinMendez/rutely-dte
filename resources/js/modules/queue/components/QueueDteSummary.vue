<script setup lang="ts">
import { InfoItem } from 'ornito';
import { computed, watch } from 'vue';
import { useQueueDteUi } from '../composables/useQueueDteUi';

const { dteId, refreshedAt } = defineProps<{
    dteId: string;
    refreshedAt: Date;
}>();

const { dte, error, isLoading, loadDte } = useQueueDteUi();

const placeholder = computed(() =>
    isLoading.value ? 'Cargando…' : 'No disponible',
);

const issuedAt = computed(() => {
    const identification = dte.value?.original_json?.identificacion;

    return identification?.fecEmi
        ? `${identification.fecEmi} ${identification.horEmi ?? ''}`.trim()
        : null;
});

watch(
    () => [dteId, refreshedAt],
    () => loadDte(dteId),
    { immediate: true },
);
</script>

<template>
    <p v-if="error" class="text-sm text-gray-600 dark:text-gray-400">
        {{ error }}
    </p>
    <div v-else class="grid gap-4 sm:grid-cols-2">
        <InfoItem label="Código de generación">
            <span class="break-all">{{
                dte?.generation_code ?? placeholder
            }}</span>
        </InfoItem>
        <InfoItem label="Número de control">
            <span class="break-all">{{
                dte?.control_number ?? placeholder
            }}</span>
        </InfoItem>
        <InfoItem label="Fecha y hora de emisión">
            {{ issuedAt ?? placeholder }}
        </InfoItem>
        <InfoItem label="Monto total">
            {{ dte ? `$${(dte.total_amount / 100).toFixed(2)}` : placeholder }}
        </InfoItem>
    </div>
</template>
