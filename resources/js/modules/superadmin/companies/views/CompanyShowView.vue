<script setup lang="ts">
import { IconBuilding, IconUser, IconUsers } from '@tabler/icons-vue';
import { Breadcrumb, Card, SidebarNav, ViewHeader } from 'ornito';
import type { BreadcrumbItem } from 'ornito';
import { computed, watch } from 'vue';
import { useRoute } from 'vue-router';
import useCompanyShow from '@/modules/superadmin/companies/composables/useCompanyShow';

const route = useRoute();
const { company, isLoading, error, loadCompany } = useCompanyShow();

const companyName = computed(() => company.value?.name ?? 'Empresa');
const pageTitle = computed(() =>
    route.name === 'superadmin-company-profile-edit'
        ? 'Editar perfil de la empresa'
        : 'Perfil de la empresa',
);
const pageSubtitle = computed(() =>
    route.name === 'superadmin-company-profile-edit'
        ? 'Actualiza la información fiscal y de contacto de la empresa.'
        : 'Consulta la información fiscal y de contacto de la empresa.',
);
const breadcrumbItems = computed<BreadcrumbItem[]>(() => [
    { label: 'Empresas', to: '/superadmin/companies', icon: IconBuilding },
    { label: companyName.value },
]);
const sidebarItems = computed(() => [
    {
        name: 'Perfil',
        icon: IconUser,
        to: `/superadmin/companies/${route.params.id}/profile`,
    },
    {
        name: 'Usuarios',
        icon: IconUsers,
        to: `/superadmin/companies/${route.params.id}/users`,
    },
]);

watch(
    () => String(route.params.id),
    (companyId) => loadCompany(companyId),
    { immediate: true },
);
</script>

<template>
    <div class="space-y-6">
        <Breadcrumb :items="breadcrumbItems" />
        <ViewHeader
            :title="pageTitle"
            :subtitle="pageSubtitle"
            :badge-text="companyName"
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
                <Card v-if="isLoading" class="animate-pulse">
                    <div class="space-y-4">
                        <div
                            class="h-5 w-1/3 rounded bg-gray-200 dark:bg-gray-700"
                        />
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div
                                v-for="item in 6"
                                :key="item"
                                class="h-10 rounded bg-gray-100 dark:bg-gray-800"
                            />
                        </div>
                    </div>
                </Card>
                <Card
                    v-else-if="error"
                    class="text-sm text-red-700 dark:text-red-400"
                >
                    {{ error }}
                </Card>
                <RouterView v-else />
            </section>
        </div>
    </div>
</template>
