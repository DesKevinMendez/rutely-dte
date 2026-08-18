<script setup lang="ts">
import OnboardingForm from '../components/OnboardingForm.vue';
</script>

<template>
    <div class="min-h-screen bg-gray-50 px-4 py-8 text-gray-900 dark:bg-gray-900 dark:text-white sm:py-12">
        <div class="mx-auto w-full max-w-4xl space-y-6">
            <header class="text-center">
                <div class="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-2xl border border-gray-200 bg-white text-lg font-bold text-primary-600 dark:border-gray-700 dark:bg-gray-800 dark:text-primary-400">
                    R
                </div>
                <p class="text-sm font-semibold text-primary-600 dark:text-primary-400">Configuración inicial</p>
                <h1 class="mt-1 text-3xl font-bold tracking-tight">Datos de la empresa emisora</h1>
                <p class="mx-auto mt-2 max-w-2xl text-sm text-gray-500 dark:text-gray-400">
                    Completá la información que utilizará Rutely DTE para identificar al emisor y construir sus documentos tributarios electrónicos.
                </p>
            </header>

            <OnboardingForm />
        </div>
    </div>
</template>
