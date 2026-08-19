<script setup lang="ts">
import AuthHero from '../components/AuthHero.vue';
import LoginForm from '../components/LoginForm.vue';
</script>

<template>
    <div
        class="min-h-screen bg-white text-gray-900 lg:flex dark:bg-gray-900 dark:text-white"
    >
        <AuthHero />
        <section
            class="flex w-full items-center justify-center p-8 lg:min-h-screen lg:w-1/3"
        >
            <LoginForm />
        </section>
    </div>
</template>
