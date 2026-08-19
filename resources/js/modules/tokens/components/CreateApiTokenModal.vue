<script setup lang="ts">
import { BaseButton, FormInput, Modal } from 'ornito';
import { computed, ref, watch } from 'vue';

const { isOpen, isSubmitting = false } = defineProps<{
    isOpen: boolean;
    isSubmitting?: boolean;
}>();

const emit = defineEmits<{
    'update:isOpen': [value: boolean];
    create: [name: string];
}>();

const name = ref('');
const normalizedName = computed(() => name.value.trim());

watch(
    () => isOpen,
    (open) => {
        if (open) {
            name.value = '';
        }
    },
);

const handleOpenChange = (value: boolean): void => {
    if (!isSubmitting) {
        emit('update:isOpen', value);
    }
};

const submit = (): void => {
    if (isSubmitting || !normalizedName.value) {
        return;
    }

    emit('create', normalizedName.value);
};
</script>

<template>
    <Modal
        :open="isOpen"
        title="Crear token de API"
        subtitle="Identifique el token con un nombre que indique qué integración lo utilizará."
        @update:open="handleOpenChange"
    >
        <form class="space-y-5 pt-2" @submit.prevent="submit">
            <FormInput
                v-model="name"
                id="api-token-name"
                name="name"
                type="text"
                label="Nombre del token"
                placeholder="Ej. Integración ERP"
                :disabled="isSubmitting"
            />

            <div
                class="rounded-lg border border-gray-200 bg-gray-50 p-4 text-sm text-gray-600 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300"
            >
                Este token tendrá únicamente el permiso
                <code class="font-semibold text-gray-900 dark:text-white"
                    >create:dte</code
                >.
            </div>

            <div
                class="flex justify-end gap-2 border-t border-gray-200 pt-4 dark:border-gray-800"
            >
                <BaseButton
                    type="button"
                    variant="outline"
                    size="auto"
                    :disabled="isSubmitting"
                    @click="emit('update:isOpen', false)"
                >
                    Cancelar
                </BaseButton>
                <BaseButton
                    type="submit"
                    variant="primary"
                    size="auto"
                    :disabled="isSubmitting || !normalizedName"
                >
                    {{ isSubmitting ? 'Creando…' : 'Crear token' }}
                </BaseButton>
            </div>
        </form>
    </Modal>
</template>
