<script setup lang="ts">
import { Modal } from 'ornito';
import DteDetailsContent from './DteDetailsContent.vue';
import DteDetailsSkeleton from './DteDetailsSkeleton.vue';

defineProps<{
    open: boolean;
    dteId: string | null;
}>();

const emit = defineEmits<{
    close: [];
}>();

const handleOpenChange = (open: boolean): void => {
    if (!open) {
        emit('close');
    }
};
</script>

<template>
    <Modal
        :open="open"
        title="Detalle del DTE"
        subtitle="Documento emitido y respuesta del Ministerio de Hacienda."
        size="2xl"
        @update:open="handleOpenChange"
    >
        <Suspense v-if="dteId" :key="dteId">
            <DteDetailsContent :dte-id="dteId" />

            <template #fallback>
                <DteDetailsSkeleton />
            </template>
        </Suspense>
    </Modal>
</template>
