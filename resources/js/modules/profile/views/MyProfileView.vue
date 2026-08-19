<script setup lang="ts">
import { Card } from 'ornito';
import ProfileHeaderCard from '../components/ProfileHeaderCard.vue';
import { useProfileUi } from '../composables/useProfileUi';

const { profile } = useProfileUi();
</script>

<template>
    <div class="space-y-6">
        <div>
            <h1
                class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white"
            >
                Mi Perfil
            </h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                Información de tu cuenta de administrador
            </p>
        </div>

        <ProfileHeaderCard :profile="profile" />

        <Card title="Información de Contacto">
            <div class="divide-y divide-gray-200 dark:divide-gray-800">
                <div class="flex items-center justify-between gap-4 py-3">
                    <span class="text-sm text-gray-500 dark:text-gray-400"
                        >Correo Electrónico</span
                    >
                    <span
                        class="text-right text-sm font-medium text-gray-900 dark:text-white"
                        >{{ profile.email }}</span
                    >
                </div>
                <div class="flex items-center justify-between gap-4 py-3">
                    <span class="text-sm text-gray-500 dark:text-gray-400"
                        >Teléfono</span
                    >
                    <span
                        class="text-right text-sm font-medium text-gray-900 dark:text-white"
                        >{{ profile.phone }}</span
                    >
                </div>
            </div>
        </Card>
    </div>
</template>
