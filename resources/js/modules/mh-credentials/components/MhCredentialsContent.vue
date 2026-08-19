<script setup lang="ts">
import { Alert } from 'ornito';
import { useMhCredentialsUi } from '../composables/useMhCredentialsUi';
import MhCredentialsForm from './MhCredentialsForm.vue';
import MhCredentialsStatusCard from './MhCredentialsStatusCard.vue';

const {
    selectedEnvironment,
    saveSuccess,
    credentialsMetadata,
    isLoading,
    error,
    changeEnvironment,
    saveCredentials,
} = await useMhCredentialsUi();
</script>

<template>
    <div class="space-y-6">
        <div>
            <h1
                class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white"
            >
                Credenciales de Autenticación (MH)
            </h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                Gestión y rotación de credenciales (NIT y contraseña) para la
                autenticación en el portal del Ministerio de Hacienda
            </p>
        </div>

        <Alert v-if="error" type="danger">
            {{ error }}
        </Alert>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <div class="lg:col-span-1">
                <MhCredentialsStatusCard
                    :metadata="credentialsMetadata"
                    :selected-environment="selectedEnvironment"
                />
            </div>
            <div class="lg:col-span-2">
                <MhCredentialsForm
                    :selected-environment="selectedEnvironment"
                    :save-success="saveSuccess"
                    :is-submitting="isLoading"
                    @environment-change="changeEnvironment"
                    @save="saveCredentials"
                />
            </div>
        </div>
    </div>
</template>
