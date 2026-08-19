<script setup lang="ts">
import MhCredentialsForm from '../components/MhCredentialsForm.vue';
import MhCredentialsStatusCard from '../components/MhCredentialsStatusCard.vue';
import { useMhCredentialsUi } from '../composables/useMhCredentialsUi';

const {
    selectedEnvironment,
    saveSuccess,
    credentialsMetadata,
    changeEnvironment,
    saveCredentials,
} = useMhCredentialsUi();
</script>

<template>
    <div class="space-y-6">
        <div>
            <div
                class="mb-2 inline-flex rounded-full border border-primary-200 bg-primary-50 px-2.5 py-1 text-xs font-semibold text-primary-700 dark:border-primary-800 dark:bg-primary-950/40 dark:text-primary-300"
            >
                Simulación UI
            </div>
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
                    @environment-change="changeEnvironment"
                    @save="saveCredentials"
                />
            </div>
        </div>
    </div>
</template>
