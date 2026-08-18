import { useRequest } from '@/core/composables/useRequest';
import { useAuth } from '@/core/stores/auth';
import type { AuthUser } from '@/core/types/auth.types';

export function useAuthSession() {
    const auth = useAuth();

    const ensureSession = async (): Promise<boolean> => {
        if (!auth.isAuthenticated) {
            auth.clearSession();
            return false;
        }

        if (auth.sessionValidated && auth.user) {
            return true;
        }

        const { get } = useRequest();
        const response = await get<AuthUser>('/api/user');

        if (response.statusCode.value === 200 && response.data.value) {
            auth.setUser(response.data.value);
            return true;
        }

        auth.clearSession();
        return false;
    };

    return {
        ensureSession,
    };
}
