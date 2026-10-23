<script setup lang="ts">
import { IconMail, IconPhone, IconUser } from '@tabler/icons-vue';
import { BaseButton, FormInput } from 'ornito';
import { Form } from 'vee-validate';
import useCompanyUserForm from '@/modules/superadmin/companies/composables/useCompanyUserForm';
import type { CompanyUserPayload } from '@/modules/superadmin/companies/types/company-user.types';

defineProps<{
    isSaving: boolean;
    error: string | null;
}>();

const emit = defineEmits<{
    submit: [payload: CompanyUserPayload];
    cancel: [];
}>();

const { formData, nameRules, emailRules, phoneRules, buildPayload } =
    useCompanyUserForm();

const submit = (): void => {
    emit('submit', buildPayload());
};
</script>

<template>
    <Form class="space-y-5 pt-2" :initial-values="formData" @submit="submit">
        <div class="grid gap-5 sm:grid-cols-2">
            <FormInput
                id="company-user-name"
                v-model="formData.name"
                name="name"
                label="Nombre"
                placeholder="Nombre completo"
                autocomplete="name"
                :left-icon="IconUser"
                :rules="nameRules"
                :disabled="isSaving"
            />

            <FormInput
                id="company-user-email"
                v-model="formData.email"
                name="email"
                type="email"
                label="Correo electrónico"
                placeholder="usuario@empresa.com"
                autocomplete="email"
                :left-icon="IconMail"
                :rules="emailRules"
                :disabled="isSaving"
            />

            <FormInput
                id="company-user-phone"
                v-model="formData.phone"
                name="phone"
                type="tel"
                label="Teléfono (opcional)"
                placeholder="+503 7777 7777"
                mask="+503 #### ####"
                autocomplete="tel"
                :left-icon="IconPhone"
                :rules="phoneRules"
                :disabled="isSaving"
            />
        </div>

        <div
            v-if="error"
            class="rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-700 dark:border-red-800 dark:bg-red-950/60 dark:text-red-400"
            role="alert"
        >
            {{ error }}
        </div>

        <div
            class="flex justify-end gap-3 border-t border-gray-200 pt-4 dark:border-gray-700"
        >
            <BaseButton
                type="button"
                variant="outline"
                size="auto"
                :disabled="isSaving"
                @click="emit('cancel')"
            >
                Cancelar
            </BaseButton>
            <BaseButton
                type="submit"
                variant="primary"
                size="auto"
                :loading="isSaving"
            >
                {{ isSaving ? 'Creando…' : 'Crear usuario' }}
            </BaseButton>
        </div>
    </Form>
</template>
