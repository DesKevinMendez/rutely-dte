<script setup lang="ts">
import { IconBuildingStore, IconDashboard, IconLock } from '@tabler/icons-vue';
import { Navbar, Sidebar, UserProfileDropdown, useSidebar } from 'ornito';
import type { DropdownMenuItem, RoutesLink } from 'ornito';
import { computed, ref, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useAuth } from '@/core/stores/auth';

const route = useRoute();
const router = useRouter();
const auth = useAuth();
const { isCollapsed, toggleCollapse } = useSidebar();
const isMobileSidebarOpen = ref(false);

const navigation: RoutesLink[] = [
    {
        route: '/superadmin/dashboard',
        name: 'Dashboard',
        icon: IconDashboard,
    },
    {
        route: '/superadmin/companies',
        name: 'Empresas',
        icon: IconBuildingStore,
    },
];

const userName = computed(() => auth.user?.name ?? 'Superadmin');
const userInitials = computed(() => {
    const words = userName.value.trim().split(/\s+/).filter(Boolean);

    return (
        words
            .slice(0, 2)
            .map((word) => word.charAt(0).toUpperCase())
            .join('') || 'S'
    );
});
const userRole = computed(() => auth.user?.roles[0]?.name ?? 'superadmin');

const logout = async (): Promise<void> => {
    auth.clearSession();
    await router.push({ name: 'login' });
};

const userMenuItems = computed<DropdownMenuItem[]>(() => [
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
                    subtitle="Administración"
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
                    subtitle="Administración"
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
    </div>
</template>
