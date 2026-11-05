<script setup lang="ts">
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
import {
    ConfirmationModal,
    Navbar,
    Sidebar,
    Tabs,
    UserProfileDropdown,
    useSidebar,
} from 'ornito';
import type { DropdownMenuItem, RoutesLink, Tab } from 'ornito';
import { computed, ref, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useRequest } from '@/core/composables/useRequest';
import { useAuth } from '@/core/stores/auth';
import type { AuthUser } from '@/core/types/auth.types';
import type {
    ApiEnvironment,
    Environment,
} from '@/core/types/environment.types';

interface EnvironmentUpdateResponse {
    data: {
        user: AuthUser;
    };
}

const toApiEnvironment = (environment: Environment): ApiEnvironment =>
    environment === 'PRODUCCION' ? '01' : '00';

const fromApiEnvironment = (
    environment: ApiEnvironment | undefined,
): Environment => (environment === '01' ? 'PRODUCCION' : 'PRUEBAS');

const route = useRoute();
const router = useRouter();
const auth = useAuth();
const { isCollapsed, toggleCollapse } = useSidebar();
const { put } = useRequest();
const isMobileSidebarOpen = ref(false);
const isEnvironmentUpdating = ref(false);
const environmentUpdateError = ref<string | null>(null);
const environment = computed<Environment>(() =>
    fromApiEnvironment(auth.user?.company?.environment),
);
const pendingEnvironment = ref<Environment | null>(null);
const environmentTabs: Tab[] = [
    { id: 'PRUEBAS', label: 'Pruebas' },
    { id: 'PRODUCCION', label: 'Producción' },
];

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

    return (
        words
            .slice(0, 2)
            .map((word) => word.charAt(0).toUpperCase())
            .join('') || 'U'
    );
});
const userRole = computed(() => auth.user?.roles[0]?.name ?? 'Usuario');

const environmentConfirmationTitle = computed(() => {
    if (pendingEnvironment.value === 'PRODUCCION') {
        return 'Cambiar a ambiente de Producción';
    }

    return 'Cambiar a ambiente de Pruebas';
});

const environmentConfirmationSubtitle = computed(() => {
    const confirmationText =
        pendingEnvironment.value === 'PRODUCCION'
            ? '¿Está seguro de cambiar a Producción? Los DTE emitidos en este ambiente corresponden a emisión real ante el Ministerio de Hacienda.'
            : '¿Está seguro de cambiar a Pruebas? Las operaciones se realizarán en el ambiente de pruebas del Ministerio de Hacienda.';

    return environmentUpdateError.value
        ? `${confirmationText} ${environmentUpdateError.value}`
        : confirmationText;
});

const requestEnvironmentChange = (targetEnvironment: Environment): void => {
    if (targetEnvironment === environment.value) {
        return;
    }

    environmentUpdateError.value = null;
    pendingEnvironment.value = targetEnvironment;
};

const selectedEnvironmentIndex = computed<number>({
    get: () => (environment.value === 'PRUEBAS' ? 0 : 1),
    set: (index) => {
        requestEnvironmentChange(index === 0 ? 'PRUEBAS' : 'PRODUCCION');
    },
});

const cancelEnvironmentChange = (): void => {
    if (isEnvironmentUpdating.value) {
        return;
    }

    pendingEnvironment.value = null;
    environmentUpdateError.value = null;
};

const confirmEnvironmentChange = async (): Promise<void> => {
    if (!pendingEnvironment.value || isEnvironmentUpdating.value) {
        return;
    }

    const companyId = auth.user?.company_id;

    if (!companyId) {
        environmentUpdateError.value =
            'No se encontró la empresa para actualizar el ambiente.';

        return;
    }

    isEnvironmentUpdating.value = true;
    environmentUpdateError.value = null;

    const response = await put<
        EnvironmentUpdateResponse,
        { environment: ApiEnvironment }
    >(`/api/v1/companies/${encodeURIComponent(companyId)}/environment`, {
        environment: toApiEnvironment(pendingEnvironment.value),
    });
    const updatedUser = response.data.value?.data.user;

    if (updatedUser) {
        auth.setUser(updatedUser);
        pendingEnvironment.value = null;
    } else {
        environmentUpdateError.value =
            response.error.value ??
            'No se pudo actualizar el ambiente de la empresa.';
    }

    isEnvironmentUpdating.value = false;
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
                <Navbar
                    class="max-sm:px-2"
                    @toggle-mobile-sidebar="toggleMobileSidebar"
                >
                    <template #right>
                        <Tabs
                            v-model="selectedEnvironmentIndex"
                            :tabs="environmentTabs"
                            variant="segmented"
                            label="Ambiente"
                            class="!space-y-0 [&_.tab-content]:hidden max-sm:[&_[role=tab]]:px-2 max-sm:[&_[role=tablist]]:px-1 [&>div:first-child]:!h-auto"
                        >
                            <template #tab-0>
                                <span class="hidden" aria-hidden="true" />
                            </template>
                            <template #tab-1>
                                <span class="hidden" aria-hidden="true" />
                            </template>
                        </Tabs>

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
                    <div class="mx-auto w-full">
                        <RouterView />
                    </div>
                </main>
            </div>
        </div>

        <ConfirmationModal
            :open="pendingEnvironment !== null"
            :loading="isEnvironmentUpdating"
            :title="environmentConfirmationTitle"
            :subtitle="environmentConfirmationSubtitle"
            @close="cancelEnvironmentChange"
            @confirm="confirmEnvironmentChange"
        />
    </div>
</template>
