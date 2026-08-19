<script setup lang="ts">
import { IconPlus } from '@tabler/icons-vue';
import { Alert, BaseButton } from 'ornito';
import { ref } from 'vue';
import { useApiTokensUi } from '../composables/useApiTokensUi';
import CreateApiTokenModal from './CreateApiTokenModal.vue';
import GeneratedApiTokenModal from './GeneratedApiTokenModal.vue';

const isCreateModalOpen = ref(false);
const {
    generatedToken,
    isLoading,
    error,
    createToken,
    clearGeneratedToken,
} = useApiTokensUi();

const handleCreate = async (name: string): Promise<void> => {
    if (await createToken(name)) {
        isCreateModalOpen.value = false;
    }
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
                    Genere tokens para integraciones externas con la ability
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

        <CreateApiTokenModal
            v-model:is-open="isCreateModalOpen"
            :is-submitting="isLoading"
            @create="handleCreate"
        />

        <GeneratedApiTokenModal
            :open="generatedToken !== null"
            :token="generatedToken ?? ''"
            @close="clearGeneratedToken"
        />
    </div>
</template>
