<script setup lang="ts">
import { IconPlus } from '@tabler/icons-vue';
import { Alert, BaseButton, Card } from 'ornito';
import { ref } from 'vue';
import { useApiTokensUi } from '../composables/useApiTokensUi';
import CreateApiTokenModal from './CreateApiTokenModal.vue';
import GeneratedApiTokenModal from './GeneratedApiTokenModal.vue';

const isCreateModalOpen = ref(false);
const {
    tokens,
    generatedToken,
    isLoading,
    error,
    createToken,
    clearGeneratedToken,
} = await useApiTokensUi();

const formatDateTime = (value: string): string =>
    new Date(value).toLocaleString('es-SV', {
        timeZone: 'America/El_Salvador',
    });

const formatLastUsedAt = (value: string | null): string =>
    value ? formatDateTime(value) : 'Nunca';

const handleCreate = async (name: string): Promise<void> => {
    if (await createToken(name)) {
        isCreateModalOpen.value = false;
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
            <div v-if="tokens.length" class="overflow-x-auto">
                <table class="w-full min-w-[720px] text-left text-sm">
                    <thead>
                        <tr
                            class="border-b border-gray-200 text-xs font-semibold tracking-wide text-gray-500 uppercase dark:border-gray-700 dark:text-gray-400"
                        >
                            <th class="px-4 py-3">Nombre</th>
                            <th class="px-4 py-3">Token</th>
                            <th class="px-4 py-3">Último uso</th>
                            <th class="px-4 py-3">Creado</th>
                        </tr>
                    </thead>
                    <tbody
                        class="divide-y divide-gray-100 dark:divide-gray-800"
                    >
                        <tr
                            v-for="token in tokens"
                            :key="token.id"
                            class="text-gray-700 dark:text-gray-200"
                        >
                            <td class="px-4 py-4 font-semibold">
                                {{ token.name }}
                            </td>
                            <td class="px-4 py-4">
                                <code
                                    class="font-mono text-gray-500 dark:text-gray-400"
                                    >********************</code
                                >
                            </td>
                            <td class="px-4 py-4 text-gray-500 dark:text-gray-400">
                                {{ formatLastUsedAt(token.last_used_at) }}
                            </td>
                            <td class="px-4 py-4 text-gray-500 dark:text-gray-400">
                                {{ formatDateTime(token.created_at) }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div
                v-else
                class="flex min-h-48 flex-col items-center justify-center px-4 py-10 text-center"
            >
                <p class="font-semibold text-gray-900 dark:text-white">
                    No hay tokens creados
                </p>
                <p class="mt-1 max-w-md text-sm text-gray-500 dark:text-gray-400">
                    Cree un token para autorizar a una integración externa a
                    emitir DTE mediante la API.
                </p>
            </div>
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
