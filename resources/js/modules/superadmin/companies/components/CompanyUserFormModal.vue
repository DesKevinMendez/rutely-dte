<script setup lang="ts">
import { Modal } from 'ornito';
import CompanyUserForm from '@/modules/superadmin/companies/components/CompanyUserForm.vue';
import type { CompanyUserPayload } from '@/modules/superadmin/companies/types/company-user.types';

defineProps<{
    open: boolean;
    isSaving: boolean;
    error: string | null;
}>();

const emit = defineEmits<{
    'update:open': [open: boolean];
    submit: [payload: CompanyUserPayload];
    cancel: [];
}>();
</script>

<template>
    <Modal
        :open="open"
        title="Nuevo usuario"
        subtitle="Agrega un administrador. La contraseña temporal se enviará a su correo."
        size="lg"
        @update:open="emit('update:open', $event)"
    >
        <CompanyUserForm
            v-if="open"
            :is-saving="isSaving"
            :error="error"
            @submit="emit('submit', $event)"
            @cancel="emit('cancel')"
        />
    </Modal>
</template>
