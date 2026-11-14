<script setup lang="ts">
import { IconBuilding, IconUser } from '@tabler/icons-vue';
import { Card, LoadingSVG, SidebarNav, ViewHeader } from 'ornito';

const sidebarItems = [
    {
        name: 'Mi perfil',
        icon: IconUser,
        to: '/my-profile/personal-info',
    },
    {
        name: 'Empresa',
        icon: IconBuilding,
        to: '/my-profile/company',
    },
];
</script>

<template>
    <div class="space-y-6">
        <ViewHeader
            title="Mi Perfil"
            subtitle="Gestiona tu información personal y los datos de tu empresa."
        />

        <div class="grid grid-cols-12 gap-4">
            <aside class="col-span-12 lg:col-span-3 2xl:col-span-2">
                <Card>
                    <h2
                        class="mb-4 text-lg font-semibold text-gray-900 dark:text-white"
                    >
                        Opciones
                    </h2>
                    <SidebarNav :items="sidebarItems" />
                </Card>
            </aside>

            <section class="col-span-12 lg:col-span-9 2xl:col-span-10">
                <Suspense>
                    <RouterView />
                    <template #fallback>
                        <div class="flex w-full justify-center p-6">
                            <LoadingSVG />
                        </div>
                    </template>
                </Suspense>
            </section>
        </div>
    </div>
</template>
