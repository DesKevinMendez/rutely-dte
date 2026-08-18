<script setup lang="ts">
import { computed } from 'vue';
import { useRouter } from 'vue-router';
import { Navbar, UserProfileDropdown } from 'ornito';
import type { DropdownMenuItem } from 'ornito';
import { useAuthUser } from '@/core/composables/useAuthUser';

const router = useRouter();
const { user, clearSession } = useAuthUser();

const userName = computed(() => user.value?.name ?? 'Usuario');
const userInitials = computed(() => {
    const words = userName.value.trim().split(/\s+/).filter(Boolean);

    return words
        .slice(0, 2)
        .map((word) => word.charAt(0).toUpperCase())
        .join('') || 'U';
});
const userRole = computed(() => user.value?.role ?? 'Usuario');

const logout = async (): Promise<void> => {
    clearSession();
    await router.push({ name: 'login' });
};

const userMenuItems = computed<DropdownMenuItem[]>(() => [
    {
        label: 'Cerrar sesión',
        class: 'text-danger-600 dark:text-danger-400',
        onClick: logout,
    },
]);
</script>

<template>
    <div class="min-h-screen bg-gray-50 text-gray-900 dark:bg-gray-900 dark:text-gray-100">
        <Navbar>
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

        <main class="mx-auto w-full max-w-7xl p-4 sm:p-6">
            <RouterView />
        </main>
    </div>
</template>
