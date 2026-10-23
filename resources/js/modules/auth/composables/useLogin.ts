import { ref } from 'vue';
import { useRouter } from 'vue-router';
import * as yup from 'yup';
import { useRequest } from '@/core/composables/useRequest';
import { useAuth } from '@/core/stores/auth';
import type { LoginRequest, LoginResponse } from '../types/auth.types';

const rules = {
    email: yup
        .string()
        .required('El correo electrónico es requerido.')
        .email('Ingresá un correo electrónico válido.'),
    password: yup.string().required('La contraseña es requerida.'),
};

export default function useLogin() {
    const router = useRouter();
    const auth = useAuth();
    const { post, isLoading, error } = useRequest();
    const email = ref('');
    const password = ref('');

    const login = async (): Promise<void> => {
        if (isLoading.value) {
            return;
        }

        error.value = null;

        const payload: LoginRequest = {
            email: email.value,
            password: password.value,
            device_name: 'rutely-dte-web',
        };

        const response = await post<LoginResponse, LoginRequest>(
            '/api/v1/login',
            payload,
        );
        const session = response.data.value?.data;

        if (!session?.token || !session.user) {
            return;
        }

        auth.setSession(session.token, session.user);
        const isSuperadmin = session.user.roles.some(
            (role) => role.name === 'superadmin',
        );
        await router.push({
            name: isSuperadmin ? 'superadmin-dashboard' : 'dashboard',
        });
    };

    return {
        email,
        password,
        rules,
        isLoading,
        error,
        login,
    };
}
