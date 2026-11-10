<script setup lang="ts">
import { Alert, BaseButton, Tabs } from 'ornito';
import { ref } from 'vue';
import { useDteDetailUi } from '../composables/useDteDetailUi';
import DteDocumentTab from './DteDocumentTab.vue';
import DteItemsTab from './DteItemsTab.vue';
import DteMhResponseTab from './DteMhResponseTab.vue';
import DteSummaryTab from './DteSummaryTab.vue';

const props = defineProps<{
    dteId: string;
}>();

const tabs = [
    { id: 'details', label: 'Detalle' },
    { id: 'items', label: 'Ítems' },
    { id: 'document', label: 'Documento DTE' },
    { id: 'mh', label: 'Respuesta MH' },
];

const activeTab = ref(0);
const { dte, error, isLoading, loadDte, paymentMethodLabels } =
    useDteDetailUi();

await loadDte(props.dteId);
</script>

<template>
    <Tabs v-if="dte" v-model="activeTab" :tabs="tabs" label="Secciones del DTE">
        <template #tab-0>
            <DteSummaryTab
                :dte="dte"
                :payment-method-labels="paymentMethodLabels"
            />
        </template>
        <template #tab-1><DteItemsTab :dte="dte" /></template>
        <template #tab-2><DteDocumentTab :dte="dte" /></template>
        <template #tab-3><DteMhResponseTab :dte="dte" /></template>
    </Tabs>

    <div v-else class="space-y-4">
        <Alert type="danger">{{ error }}</Alert>
        <BaseButton
            variant="outline"
            size="auto"
            :loading="isLoading"
            @click="loadDte(props.dteId)"
        >
            Reintentar
        </BaseButton>
    </div>
</template>
