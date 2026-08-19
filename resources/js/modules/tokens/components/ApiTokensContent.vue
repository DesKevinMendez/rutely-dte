<script setup lang="ts">
import { IconPlus } from '@tabler/icons-vue';
import { Alert, BaseButton, Card, DataTable } from 'ornito';
import type { TableField } from 'ornito';
import { ref } from 'vue';
import { useApiTokensUi } from '../composables/useApiTokensUi';
import type { ApiTokenApiRecord } from '../types/api-token.types';
import CreateApiTokenModal from './CreateApiTokenModal.vue';
import GeneratedApiTokenModal from './GeneratedApiTokenModal.vue';

const isCreateModalOpen = ref(false);
const tableKey = ref(0);
const {
    generatedToken,
    isLoading,
    error,
    createToken,
    clearGeneratedToken,
} = useApiTokensUi();

const formatDateTime = (value: string): string =>
    new Date(value).toLocaleString('es-SV', {
        timeZone: 'America/El_Salvador',
    });

const formatLastUsedAt = (value: string | null): string =>
    value ? formatDateTime(value) : 'Nunca';

const columns: TableField<ApiTokenApiRecord>[] = [
    { label: 'Nombre', key: 'name' },
    { label: 'Token', key: 'id', slot: 'token' },
    {
        label: 'Último uso',
        key: 'last_used_at',
        format: (row) => formatLastUsedAt(row.last_used_at),
    },
    {
        label: 'Creado',
        key: 'created_at',
        format: (row) => formatDateTime(row.created_at),
    },
];

const handleCreate = async (name: string): Promise<void> => {
    if (await createToken(name)) {
        isCreateModalOpen.value = false;
        tableKey.value += 1;
    }
};

const closeGeneratedToken = (): void => {
    clearGeneratedToken();
};
</script>

<template>
    <div class="space-y-6">
        <div
            class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
        >
            <div>
                <h1
                    class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white"
                >
                    Tokens de API
                </h1>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    Genere tokens para integraciones externas. Cada token tendrá
                    únicamente la ability
                    <code class="font-semibold">create:dte</code>.
                </p>
            </div>
            <BaseButton
                variant="primary"
                size="auto"
                :disabled="isLoading"
                @click="isCreateModalOpen = true"
            >
                <IconPlus class="mr-2 h-4 w-4" />
                Crear token
            </BaseButton>
        </div>

        <Alert v-if="error" type="danger">
            {{ error }}
        </Alert>

        <Card title="Tokens de acceso">
            <DataTable
                :key="tableKey"
                :columns="columns"
                url="/api/v1/tokens"
            >
                <template #token>
                    <code class="font-mono text-gray-500 dark:text-gray-400">
                        ********************
                    </code>
                </template>
            </DataTable>
        </Card>

        <CreateApiTokenModal
            v-model:is-open="isCreateModalOpen"
            :is-submitting="isLoading"
            @create="handleCreate"
        />

        <GeneratedApiTokenModal
            :open="generatedToken !== null"
            :token="generatedToken ?? ''"
            @close="closeGeneratedToken"
        />
    </div>
</template>
