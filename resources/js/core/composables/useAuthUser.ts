import { computed, ref } from 'vue';
import { useRequest } from '@/core/composables/useRequest';
import type { AuthUser } from '@/core/types/auth.types';

const USER_STORAGE_KEY = 'auth_user';
let sessionChecked = false;

const readStoredUser = (): AuthUser | null => {
    if (typeof localStorage === 'undefined') {
        return null;
    }

    const storedUser = localStorage.getItem(USER_STORAGE_KEY);

    if (!storedUser) {
        return null;
    }

    try {
        return JSON.parse(storedUser) as AuthUser;
    } catch {
        localStorage.removeItem(USER_STORAGE_KEY);
        return null;
    }
};

const user = ref<AuthUser | null>(readStoredUser());

export function useAuthUser() {
    const isAuthenticated = computed(() => {
        if (typeof localStorage === 'undefined') {
            return false;
        }

        return Boolean(localStorage.getItem('auth_token'));
    });

    const setUser = (authUser: AuthUser): void => {
        user.value = authUser;
        sessionChecked = true;

        if (typeof localStorage !== 'undefined') {
            localStorage.setItem(USER_STORAGE_KEY, JSON.stringify(authUser));
        }
    };

    const clearSession = (): void => {
        user.value = null;
        sessionChecked = false;

        if (typeof localStorage !== 'undefined') {
            localStorage.removeItem(USER_STORAGE_KEY);
            localStorage.removeItem('auth_token');
            localStorage.removeItem('company_id');
        }
    };

    const ensureSession = async (): Promise<boolean> => {
        if (!isAuthenticated.value) {
            clearSession();
            return false;
        }

        if (sessionChecked && user.value) {
            return true;
        }

        const { get } = useRequest();
        const response = await get<AuthUser>('/api/user');

        if (response.statusCode.value === 200 && response.data.value) {
            setUser(response.data.value);
            return true;
        }

        clearSession();
        return false;
    };

    return {
        user,
        isAuthenticated,
        setUser,
        clearSession,
        ensureSession,
    };
}
