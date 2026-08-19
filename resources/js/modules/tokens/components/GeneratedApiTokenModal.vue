<script setup lang="ts">
import { IconCheck, IconCopy } from '@tabler/icons-vue';
import { BaseButton, Modal } from 'ornito';
import { ref, watch } from 'vue';

const { open, token } = defineProps<{
    open: boolean;
    token: string;
}>();

const emit = defineEmits<{
    close: [];
}>();

const copied = ref(false);

watch(
    () => token,
    () => {
        copied.value = false;
    },
);

const copyToken = async (): Promise<void> => {
    await navigator.clipboard.writeText(token);
    copied.value = true;
};

const handleOpenChange = (value: boolean): void => {
    if (!value) {
        emit('close');
    }
};
</script>

<template>
    <Modal
        :open="open"
        title="Token creado"
        subtitle="Copie este token ahora. Por seguridad no volverá a mostrarse después de cerrar esta ventana."
        @update:open="handleOpenChange"
    >
        <div class="space-y-5 pt-2">
            <div
                class="rounded-lg border border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-800"
            >
                <p
                    class="mb-2 text-xs font-semibold tracking-wide text-gray-500 uppercase dark:text-gray-400"
                >
                    Token de acceso
                </p>
                <code
                    class="block break-all text-sm font-semibold text-gray-900 dark:text-white"
                    >{{ token }}</code
                >
            </div>

            <div
                class="flex flex-col-reverse gap-2 border-t border-gray-200 pt-4 sm:flex-row sm:justify-end dark:border-gray-800"
            >
                <BaseButton
                    type="button"
                    variant="outline"
                    size="auto"
                    @click="emit('close')"
                >
                    Ya lo guardé
                </BaseButton>
                <BaseButton
                    type="button"
                    variant="primary"
                    size="auto"
                    @click="copyToken"
                >
                    <component
                        :is="copied ? IconCheck : IconCopy"
                        class="mr-2 h-4 w-4"
                    />
                    {{ copied ? 'Copiado' : 'Copiar token' }}
                </BaseButton>
            </div>
        </div>
    </Modal>
</template>
