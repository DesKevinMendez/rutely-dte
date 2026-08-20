<script setup lang="ts">
import {
    IconAlertTriangle,
    IconApi,
    IconCertificate,
    IconDashboard,
    IconFileText,
    IconKey,
    IconLock,
    IconRefresh,
    IconUser,
} from '@tabler/icons-vue';
import {
    ConfirmationModal,
    Navbar,
    Sidebar,
    UserProfileDropdown,
    useSidebar,
} from 'ornito';
import type { DropdownMenuItem, RoutesLink } from 'ornito';
import { computed, ref, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useAuth } from '@/core/stores/auth';

type Environment = 'PRUEBAS' | 'PRODUCCION';

const route = useRoute();
const router = useRouter();
const auth = useAuth();
const { isCollapsed, toggleCollapse } = useSidebar();
const isMobileSidebarOpen = ref(false);
const environment = ref<Environment>('PRUEBAS');
const pendingEnvironment = ref<Environment | null>(null);

const navigation: RoutesLink[] = [
    { route: '/dashboard', name: 'Dashboard DTE', icon: IconDashboard },
    { route: '/dtes', name: 'Documentos DTE', icon: IconFileText },
    { route: '/contingency', name: 'Contingencia MH', icon: IconAlertTriangle },
    { route: '/queue', name: 'Cola Reintentos', icon: IconRefresh },
    { route: '/certificates', name: 'Certificados MH', icon: IconCertificate },
    { route: '/mh-credentials', name: 'Credenciales MH', icon: IconKey },
    { route: '/tokens', name: 'Tokens API', icon: IconApi },
];

const userName = computed(() => auth.user?.name ?? 'Usuario');
const userInitials = computed(() => {
    const words = userName.value.trim().split(/\s+/).filter(Boolean);

    return (
        words
            .slice(0, 2)
            .map((word) => word.charAt(0).toUpperCase())
            .join('') || 'U'
    );
});
const userRole = computed(() => auth.user?.role ?? 'Usuario');

const environmentConfirmationTitle = computed(() => {
    if (pendingEnvironment.value === 'PRODUCCION') {
        return 'Cambiar a ambiente de Producción';
    }

    return 'Cambiar a ambiente de Pruebas';
});

const environmentConfirmationSubtitle = computed(() => {
    if (pendingEnvironment.value === 'PRODUCCION') {
        return '¿Está seguro de cambiar a Producción? Los DTE emitidos en este ambiente corresponden a emisión real ante el Ministerio de Hacienda.';
    }

    return '¿Está seguro de cambiar a Pruebas? Las operaciones se realizarán en el ambiente de pruebas del Ministerio de Hacienda.';
});

const requestEnvironmentChange = (targetEnvironment: Environment): void => {
    if (targetEnvironment === environment.value) {
        return;
    }

    pendingEnvironment.value = targetEnvironment;
};

const cancelEnvironmentChange = (): void => {
    pendingEnvironment.value = null;
};

const confirmEnvironmentChange = (): void => {
    if (!pendingEnvironment.value) {
        return;
    }

    environment.value = pendingEnvironment.value;
    pendingEnvironment.value = null;
};

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
    <div
        class="relative min-h-screen bg-white text-gray-900 dark:bg-gray-900 dark:text-gray-100"
    >
        <div class="relative z-10 flex h-screen">
            <div class="hidden lg:block">
                <Sidebar
                    :is-collapsed="isCollapsed"
                    logo="/logo.png"
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
                :class="
                    isMobileSidebarOpen ? 'translate-x-0' : '-translate-x-full'
                "
            >
                <Sidebar
                    :is-collapsed="false"
                    logo="/logo.png"
                    title="Rutely DTE"
                    subtitle="Facturación Electrónica MH"
                    version="1.0.0"
                    :menu-items="navigation"
                    @toggle-collapse="toggleCollapse"
                    @close="closeMobileSidebar"
                />
            </div>

            <div
                class="flex min-w-0 flex-1 flex-col overflow-hidden bg-white dark:bg-gray-900"
            >
                <Navbar @toggle-mobile-sidebar="toggleMobileSidebar">
                    <template #right>
                        <div
                            class="hidden items-center rounded-lg border border-gray-200 bg-gray-50 p-1 text-xs sm:flex dark:border-gray-700 dark:bg-gray-800"
                        >
                            <button
                                type="button"
                                class="rounded-md border px-2.5 py-1.5 font-semibold transition-colors"
                                :class="
                                    environment === 'PRUEBAS'
                                        ? 'border-primary-500 bg-white text-primary-700 dark:bg-gray-900 dark:text-primary-300'
                                        : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200'
                                "
                                @click="requestEnvironmentChange('PRUEBAS')"
                            >
                                Pruebas
                            </button>
                            <button
                                type="button"
                                class="rounded-md border px-2.5 py-1.5 font-semibold transition-colors"
                                :class="
                                    environment === 'PRODUCCION'
                                        ? 'border-primary-500 bg-white text-primary-700 dark:bg-gray-900 dark:text-primary-300'
                                        : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200'
                                "
                                @click="requestEnvironmentChange('PRODUCCION')"
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

                <main
                    class="flex-1 overflow-y-auto bg-white p-4 sm:p-6 dark:bg-gray-900"
                >
                    <div class="mx-auto w-full max-w-7xl">
                        <RouterView />
                    </div>
                </main>
            </div>
        </div>

        <ConfirmationModal
            :open="pendingEnvironment !== null"
            :title="environmentConfirmationTitle"
            :subtitle="environmentConfirmationSubtitle"
            @close="cancelEnvironmentChange"
            @confirm="confirmEnvironmentChange"
        />
    </div>
</template>
