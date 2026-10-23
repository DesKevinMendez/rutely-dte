<script setup lang="ts">
import { IconPlus } from '@tabler/icons-vue';
import { Alert, BaseButton, Card, DataTable } from 'ornito';
import type { TableField } from 'ornito';
import { computed, watch } from 'vue';
import { useRoute } from 'vue-router';
import CompanyUserFormModal from '@/modules/superadmin/companies/components/CompanyUserFormModal.vue';
import useCompanyUsers from '@/modules/superadmin/companies/composables/useCompanyUsers';
import type { CompanyUser } from '@/modules/superadmin/companies/types/company-user.types';

const route = useRoute();
const companyId = computed(() => String(route.params.id ?? ''));
const {
    users,
    isLoading,
    error,
    isFormModalOpen,
    isSaving,
    formError,
    loadUsers,
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
        format: (user) => user.phone || 'No registrado',
    },
];

watch(companyId, loadUsers, { immediate: true });
</script>

<template>
    <div class="space-y-6">
        <Alert v-if="error" type="danger" role="alert">
            {{ error }}
        </Alert>

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

            <p
                v-if="isLoading"
                class="py-8 text-center text-sm text-gray-500 dark:text-gray-400"
                role="status"
            >
                Cargando usuarios…
            </p>
            <DataTable
                v-else
                :columns="columns"
                :data="users"
                :show-search="false"
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
