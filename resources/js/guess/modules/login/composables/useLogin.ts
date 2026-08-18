import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthUser } from '@/core/composables/useAuthUser';
import { useRequest } from '@/core/composables/useRequest';
import type { LoginRequest, LoginResponse } from '../types/Login';

export default function useLogin() {
    const router = useRouter();
    const { post, isLoading, error } = useRequest();
    const { setUser } = useAuthUser();

    const email = ref('');
    const password = ref('');

    const login = async (): Promise<void> => {
        const payload: LoginRequest = {
            email: email.value,
            password: password.value,
            device_name: 'rutely-dte-web',
        };

        const response = await post<LoginResponse, LoginRequest>('/api/v1/login', payload);
        const session = response.data.value?.data;

        if (!session?.token || !session.user) {
            return;
        }

        localStorage.setItem('auth_token', session.token);
        setUser(session.user);

        await router.push({
            name: session.user.company_id ? 'dashboard' : 'onboarding',
        });
    };

    return {
        email,
        password,
        isLoading,
        error,
        login,
    };
}
