<script setup lang="ts">
import { IconPlus } from '@tabler/icons-vue';
import { BaseButton, Card, DataTable } from 'ornito';
import type { TableField } from 'ornito';
import { computed } from 'vue';
import { useRoute } from 'vue-router';
import { formatSalvadoranPhone } from '@/core/composables/formatSalvadoranPhone';
import CompanyUserFormModal from '@/modules/superadmin/companies/components/CompanyUserFormModal.vue';
import useCompanyUsers from '@/modules/superadmin/companies/composables/useCompanyUsers';
import type { CompanyUser } from '@/modules/superadmin/companies/types/company-user.types';

const route = useRoute();
const companyId = computed(() => String(route.params.id ?? ''));
const {
    usersUrl,
    isFormModalOpen,
    isSaving,
    formError,
    openCreateModal,
    closeFormModal,
    saveUser,
} = useCompanyUsers(companyId);

const columns: TableField<CompanyUser>[] = [
    { label: 'Nombre', key: 'name' },
    { label: 'Correo electrónico', key: 'email' },
    {
        label: 'Teléfono',
        key: 'phone',
        format: (user) =>
            user.phone ? formatSalvadoranPhone(user.phone) : 'No registrado',
    },
];
</script>

<template>
    <div class="space-y-6">
        <Card title="Usuarios de la empresa">
            <template #headerButtons>
                <BaseButton
                    variant="outline"
                    size="auto"
                    :icon="IconPlus"
                    icon-position="left"
                    @click="openCreateModal"
                >
                    Nuevo usuario
                </BaseButton>
            </template>

            <DataTable
                :url="usersUrl"
                :columns="columns"
                search-by="name"
                search-placeholder="Buscar por nombre..."
                actions-label="Opciones"
            />
        </Card>

        <CompanyUserFormModal
            v-model:open="isFormModalOpen"
            :is-saving="isSaving"
            :error="formError"
            @submit="saveUser"
            @cancel="closeFormModal"
        />
    </div>
</template>
