<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import {
    Navbar,
    Sidebar,
    UserProfileDropdown,
    useSidebar,
    type DropdownMenuItem,
    type RoutesLink,
} from 'ornito';
import {
    IconAlertTriangle,
    IconCertificate,
    IconDashboard,
    IconFileText,
    IconKey,
    IconLock,
    IconRefresh,
    IconUser,
} from '@tabler/icons-vue';
import { useAuth } from '@/core/stores/auth';

const route = useRoute();
const router = useRouter();
const auth = useAuth();
const { isCollapsed, toggleCollapse } = useSidebar();
const isMobileSidebarOpen = ref(false);
const environment = ref<'PRUEBAS' | 'PRODUCCION'>('PRUEBAS');

const navigation: RoutesLink[] = [
    { route: '/dashboard', name: 'Dashboard DTE', icon: IconDashboard },
    { route: '/dtes', name: 'Documentos DTE', icon: IconFileText },
    { route: '/contingency', name: 'Contingencia MH', icon: IconAlertTriangle },
    { route: '/queue', name: 'Cola Reintentos', icon: IconRefresh },
    { route: '/certificates', name: 'Certificados MH', icon: IconCertificate },
    { route: '/mh-credentials', name: 'Credenciales MH', icon: IconKey },
];

const userName = computed(() => auth.user?.name ?? 'Usuario');
const userInitials = computed(() => {
    const words = userName.value.trim().split(/\s+/).filter(Boolean);

    return words
        .slice(0, 2)
        .map((word) => word.charAt(0).toUpperCase())
        .join('') || 'U';
});
const userRole = computed(() => auth.user?.role ?? 'Usuario');

const logout = async (): Promise<void> => {
    auth.clearSession();
    await router.push({ name: 'login' });
};

const userMenuItems = computed<DropdownMenuItem[]>(() => [
    {
        label: 'Mi perfil',
        icon: IconUser,
        to: '/my-profile',
        class: 'text-gray-900 dark:text-white',
    },
    {
        label: 'Cerrar sesión',
        icon: IconLock,
        class: 'text-danger-600 dark:text-danger-400',
        onClick: logout,
    },
]);

const toggleMobileSidebar = (): void => {
    isMobileSidebarOpen.value = !isMobileSidebarOpen.value;
};

const closeMobileSidebar = (): void => {
    isMobileSidebarOpen.value = false;
};

watch(
    () => route.path,
    () => {
        closeMobileSidebar();
    },
);
</script>

<template>
    <div class="relative min-h-screen bg-white text-gray-900 dark:bg-gray-900 dark:text-gray-100">
        <div class="relative z-10 flex h-screen">
            <div class="hidden lg:block">
                <Sidebar
                    :is-collapsed="isCollapsed"
                    logo="/favicon.svg"
                    title="Rutely DTE"
                    subtitle="Facturación Electrónica MH"
                    version="1.0.0"
                    :menu-items="navigation"
                    @toggle-collapse="toggleCollapse"
                />
            </div>

            <div
                v-if="isMobileSidebarOpen"
                class="fixed inset-0 z-40 bg-black/30 lg:hidden"
                @click="closeMobileSidebar"
            />

            <div
                class="fixed top-0 left-0 z-50 h-full w-64 transform transition-transform duration-300 lg:hidden"
                :class="isMobileSidebarOpen ? 'translate-x-0' : '-translate-x-full'"
            >
                <Sidebar
                    :is-collapsed="false"
                    logo="/favicon.svg"
                    title="Rutely DTE"
                    subtitle="Facturación Electrónica MH"
                    version="1.0.0"
                    :menu-items="navigation"
                    @toggle-collapse="toggleCollapse"
                    @close="closeMobileSidebar"
                />
            </div>

            <div class="flex min-w-0 flex-1 flex-col overflow-hidden bg-white dark:bg-gray-900">
                <Navbar @toggle-mobile-sidebar="toggleMobileSidebar">
                    <template #right>
                        <div class="hidden items-center rounded-lg border border-gray-200 bg-gray-50 p-1 text-xs dark:border-gray-700 dark:bg-gray-800 sm:flex">
                            <button
                                type="button"
                                class="rounded-md px-2.5 py-1.5 font-semibold transition-colors"
                                :class="environment === 'PRUEBAS' ? 'bg-white text-primary-700 dark:bg-gray-900 dark:text-primary-300' : 'text-gray-500 dark:text-gray-400'"
                                @click="environment = 'PRUEBAS'"
                            >
                                Pruebas
                            </button>
                            <button
                                type="button"
                                class="rounded-md px-2.5 py-1.5 font-semibold transition-colors"
                                :class="environment === 'PRODUCCION' ? 'bg-white text-primary-700 dark:bg-gray-900 dark:text-primary-300' : 'text-gray-500 dark:text-gray-400'"
                                @click="environment = 'PRODUCCION'"
                            >
                                Producción
                            </button>
                        </div>

                        <UserProfileDropdown
                            :user-name="userName"
                            :user-initials="userInitials"
                            :user-role="userRole"
                            :menu-items="userMenuItems"
                            :show-notifications="false"
                        />
                    </template>
                </Navbar>

                <main class="flex-1 overflow-y-auto bg-gray-50 p-4 dark:bg-gray-900 sm:p-6">
                    <div class="mx-auto w-full max-w-7xl">
                        <RouterView />
                    </div>
                </main>
            </div>
        </div>
    </div>
</template>
