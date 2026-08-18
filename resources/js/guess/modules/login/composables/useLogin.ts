import { useForm } from 'vee-validate';
import { useRouter } from 'vue-router';
import * as yup from 'yup';
import { useRequest } from '@/core/composables/useRequest';
import { useAuth } from '@/core/stores/auth';
import type { LoginRequest, LoginResponse } from '../types/Login';

interface LoginFormValues {
    email: string;
    password: string;
}

const rules = {
    email: yup
        .string()
        .required('El correo electrónico es requerido.')
        .email('Ingresá un correo electrónico válido.'),
    password: yup
        .string()
        .required('La contraseña es requerida.'),
};

const fields = ['email', 'password'] as const;

export default function useLogin() {
    const router = useRouter();
    const auth = useAuth();
    const { post, isLoading, error } = useRequest();

    const { defineField, validateField } = useForm<LoginFormValues>({
        validationSchema: yup.object(rules),
        initialValues: {
            email: '',
            password: '',
        },
    });

    const [email] = defineField('email');
    const [password] = defineField('password');

    const validateCredentials = async (): Promise<boolean> => {
        const results = await Promise.all(fields.map((field) => validateField(field)));

        return results.every((result) => result.valid);
    };

    const login = async (): Promise<void> => {
        if (isLoading.value) {
            return;
        }

        error.value = null;

        if (!(await validateCredentials())) {
            return;
        }

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

        auth.setSession(session.token, session.user);

        await router.push({
            name: session.user.company_id ? 'dashboard' : 'onboarding',
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
