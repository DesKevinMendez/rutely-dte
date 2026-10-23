<script setup lang="ts">
import { IconPlus } from '@tabler/icons-vue';
import { Alert, BaseButton, Card, DataTable } from 'ornito';
import type { TableField } from 'ornito';
import { computed, ref } from 'vue';
import { useApiTokensUi } from '../composables/useApiTokensUi';
import type {
    ApiTokenApiRecord,
    ApiTokenAbility,
} from '../types/api-token.types';
import CreateApiTokenModal from './CreateApiTokenModal.vue';
import GeneratedApiTokenModal from './GeneratedApiTokenModal.vue';

const { companyId } = defineProps<{
    companyId?: string;
}>();

const isCreateModalOpen = ref(false);
const tableKey = ref(0);
const tokensUrl = computed(() =>
    companyId
        ? `/api/v1/superadmin/companies/${encodeURIComponent(companyId)}/tokens`
        : '/api/v1/tokens',
);
const {
    generatedToken,
    isLoading,
    error,
    createToken,
    clearGeneratedToken,
} = useApiTokensUi(tokensUrl);

const formatDateTime = (value: string): string =>
    new Date(value).toLocaleString('es-SV', {
        timeZone: 'America/El_Salvador',
    });

const formatLastUsedAt = (value: string | null): string =>
    value ? formatDateTime(value) : 'Nunca';

const columns: TableField<ApiTokenApiRecord>[] = [
    { label: 'Nombre', key: 'name' },
    {
        label: 'Permisos',
        key: 'abilities',
        format: (row) => row.abilities.join(', '),
    },
    { label: 'Token', key: 'token_preview', slot: 'token' },
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

const handleCreate = async (
    name: string,
    abilities: ApiTokenAbility[],
): Promise<void> => {
    if (await createToken(name, abilities)) {
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
        <Alert v-if="error" type="danger">
            {{ error }}
        </Alert>

        <Card title="Tokens de acceso">
            <template v-if="companyId" #headerButtons>
                <BaseButton
                    variant="outline"
                    size="auto"
                    :icon="IconPlus"
                    icon-position="left"
                    :disabled="isLoading"
                    @click="isCreateModalOpen = true"
                >
                    Crear token
                </BaseButton>
            </template>

            <DataTable
                :key="`${tokensUrl}-${tableKey}`"
                :columns="columns"
                :url="tokensUrl"
            >
                <template #token="{ row }">
                    <code class="font-mono text-gray-500 dark:text-gray-400">
                        {{ row.token_preview }}
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
