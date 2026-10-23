<script setup lang="ts">
import { BaseButton, FormInput, Modal, SwitchInput } from 'ornito';
import { computed, ref, watch } from 'vue';
import {
    API_TOKEN_ABILITY_OPTIONS,
    type ApiTokenAbility,
} from '../types/api-token.types';

const { isOpen, isSubmitting = false } = defineProps<{
    isOpen: boolean;
    isSubmitting?: boolean;
}>();

const emit = defineEmits<{
    'update:isOpen': [value: boolean];
    create: [name: string, abilities: ApiTokenAbility[]];
}>();

const name = ref('');
const selectedAbilities = ref<ApiTokenAbility[]>([]);
const normalizedName = computed(() => name.value.trim());

watch(
    () => isOpen,
    (open) => {
        if (open) {
            name.value = '';
            selectedAbilities.value = [];
        }
    },
);

const handleOpenChange = (value: boolean): void => {
    if (!isSubmitting) {
        emit('update:isOpen', value);
    }
};

const submit = (): void => {
    if (isSubmitting || !normalizedName.value || !selectedAbilities.value.length) {
        return;
    }

    emit('create', normalizedName.value, [...selectedAbilities.value]);
};

const updateAbilitySelection = (
    ability: ApiTokenAbility,
    isSelected: boolean,
): void => {
    selectedAbilities.value = isSelected
        ? [...new Set([...selectedAbilities.value, ability])]
        : selectedAbilities.value.filter(
              (selectedAbility) => selectedAbility !== ability,
          );
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

            <fieldset class="space-y-3" :disabled="isSubmitting">
                <legend
                    class="text-sm font-medium text-gray-900 dark:text-white"
                >
                    Permisos del token
                </legend>
                <SwitchInput
                    v-for="ability in API_TOKEN_ABILITY_OPTIONS"
                    :key="ability.value"
                    :label="ability.label"
                    :model-value="selectedAbilities.includes(ability.value)"
                    :disabled="isSubmitting"
                    @update:model-value="updateAbilitySelection(ability.value, $event)"
                />
                <p
                    v-if="!selectedAbilities.length"
                    class="text-xs text-gray-500 dark:text-gray-400"
                    role="status"
                >
                    Selecciona al menos un permiso.
                </p>
            </fieldset>

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
                    :disabled="isSubmitting || !normalizedName || !selectedAbilities.length"
                >
                    {{ isSubmitting ? 'Creando…' : 'Crear token' }}
                </BaseButton>
            </div>
        </form>
    </Modal>
</template>
