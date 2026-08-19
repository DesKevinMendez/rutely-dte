<script setup lang="ts">
import AuthHero from '../components/AuthHero.vue';
import RecoveryForm from '../components/RecoveryForm.vue';
</script>

<template>
    <div class="min-h-screen bg-white text-gray-900 dark:bg-gray-900 dark:text-white lg:flex">
        <AuthHero />
        <section class="flex w-full items-center justify-center p-8 lg:min-h-screen lg:w-1/3">
            <RecoveryForm />
        </section>
    </div>
</template>
