<script setup lang="ts">
import LoginForm from '../components/LoginForm.vue';
</script>

<template>
    <div class="min-h-screen bg-white text-gray-900 dark:bg-gray-900 dark:text-white lg:flex">
        <section class="flex min-h-[36vh] items-center justify-center bg-gray-50 px-6 py-12 dark:bg-gray-800/40 lg:min-h-screen lg:w-2/3">
            <div class="max-w-2xl text-center">
                <div class="mx-auto mb-6 flex h-14 w-14 items-center justify-center rounded-2xl border border-gray-200 bg-white text-xl font-bold text-primary-600 dark:border-gray-700 dark:bg-gray-800 dark:text-primary-400">
                    R
                </div>
                <h1 class="text-balance text-4xl font-bold leading-tight lg:text-6xl">
                    Facturación Electrónica sin fricción
                </h1>
                <p class="mx-auto mt-5 max-w-xl text-balance text-lg text-gray-600 dark:text-gray-300 lg:text-xl">
                    Generá, firmá y transmití tus DTE al Ministerio de Hacienda desde un solo lugar.
                </p>
            </div>
        </section>

        <section class="flex w-full items-center justify-center p-6 sm:p-8 lg:min-h-screen lg:w-1/3">
            <LoginForm />
        </section>
    </div>
</template>
