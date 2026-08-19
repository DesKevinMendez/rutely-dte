import { computed, reactive, watch } from 'vue';
import { useRouter } from 'vue-router';
import * as yup from 'yup';
import { useRequest } from '@/core/composables/useRequest';
import { useAuth } from '@/core/stores/auth';
import type {
    CompanyRequest,
    CompanyResponse,
    OnboardingForm,
    RegisterRequest,
    RegisterResponse,
} from '../types/Onboarding';

const requiredText = (message: string) => yup.string().required(message);

const rules = {
    userName: requiredText('El nombre del administrador es requerido.'),
    userEmail: yup
        .string()
        .required('El correo electrónico del administrador es requerido.')
        .email('Ingresá un correo electrónico válido.'),
    password: yup
        .string()
        .required('La contraseña es requerida.')
        .min(8, 'La contraseña debe tener al menos 8 caracteres.'),
    passwordConfirmation: yup
        .string()
        .required('La confirmación de contraseña es requerida.')
        .oneOf([yup.ref('password')], 'Las contraseñas no coinciden.'),
    name: requiredText('La razón social es requerida.'),
    commercialName: requiredText('El nombre comercial es requerido.'),
    nit: requiredText('El NIT es requerido.'),
    nrc: yup.string(),
    phone: requiredText('El teléfono es requerido.'),
    email: yup
        .string()
        .required('El correo electrónico es requerido.')
        .email('Ingresá un correo electrónico válido.'),
    address: requiredText('La dirección es requerida.'),
    economicActivityCode: requiredText('La actividad económica es requerida.'),
    establishmentType: requiredText('El tipo de establecimiento es requerido.'),
    departmentId: requiredText('El departamento es requerido.'),
    municipalityId: requiredText('El municipio es requerido.'),
    districtId: requiredText('El distrito es requerido.'),
    ownEstablishmentCode: requiredText('El código de establecimiento es requerido.'),
    ownPosCode: requiredText('El código de punto de venta es requerido.'),
};

const digitsOnly = (value: string): string => value.replace(/\D/g, '');

export default function useOnboarding() {
    const router = useRouter();
    const auth = useAuth();
    const { post, isLoading, error } = useRequest();
    const isAuthenticated = computed(() => auth.isAuthenticated);

    const form = reactive<OnboardingForm>({
        userName: '',
        userEmail: '',
        password: '',
        passwordConfirmation: '',
        name: '',
        commercialName: '',
        nit: '',
        nrc: '',
        phone: '',
        email: '',
        address: '',
        economicActivityCode: '',
        establishmentType: '',
        departmentId: '',
        municipalityId: '',
        districtId: '',
        ownEstablishmentCode: '',
        ownPosCode: '',
    });

    const municipalityUrl = computed(() => {
        if (!form.departmentId) {
            return '/api/v1/data/municipalities?per_page=100';
        }

        return `/api/v1/data/municipalities?per_page=100&filter[department_id]=${form.departmentId}`;
    });

    const districtUrl = computed(() => {
        if (!form.municipalityId) {
            return '/api/v1/data/districts?per_page=100';
        }

        return `/api/v1/data/districts?per_page=100&filter[municipality_id]=${form.municipalityId}`;
    });

    watch(
        () => form.departmentId,
        () => {
            form.municipalityId = '';
            form.districtId = '';
        },
    );

    watch(
        () => form.municipalityId,
        () => {
            form.districtId = '';
        },
    );

    const registerUser = async (): Promise<boolean> => {
        const payload: RegisterRequest = {
            name: form.userName,
            email: form.userEmail,
            phone: null,
            password: form.password,
            password_confirmation: form.passwordConfirmation,
        };

        const response = await post<RegisterResponse, RegisterRequest>('/api/v1/register', payload);
        const session = response.data.value?.data;

        if (!session?.token || !session.user) {
            return false;
        }

        auth.setSession(session.token, session.user);

        return true;
    };

    const createCompany = async (): Promise<string | null> => {
        const payload: CompanyRequest = {
            name: form.name,
            address: form.address,
            phone: form.phone,
            nit: digitsOnly(form.nit),
            nrc: digitsOnly(form.nrc) || null,
            commercial_name: form.commercialName,
            economic_activity_code: form.economicActivityCode,
            establishment_type: form.establishmentType,
            departament_id: form.departmentId,
            municipality_id: form.municipalityId,
            district_id: form.districtId || null,
            email: form.email,
            mh_establishment_code: form.ownEstablishmentCode,
            mh_pos_code: form.ownPosCode,
            own_establishment_code: form.ownEstablishmentCode,
            own_pos_code: form.ownPosCode,
        };

        const response = await post<CompanyResponse, CompanyRequest>('/api/v1/companies', payload);

        return response.data.value?.data.id ?? null;
    };

    const continueFlow = async (): Promise<void> => {
        if (isLoading.value) {
            return;
        }

        error.value = null;

        if (!auth.isAuthenticated && !(await registerUser())) {
            return;
        }

        const companyId = await createCompany();
        const currentUser = auth.user;

        if (!companyId || !currentUser) {
            return;
        }

        auth.setUser({
            ...currentUser,
            company_id: companyId,
        });

        await router.push({ name: 'dashboard' });
    };

    const goToLogin = async (): Promise<void> => {
        auth.clearSession();
        await router.push({ name: 'login' });
    };

    return {
        form,
        rules,
        municipalityUrl,
        districtUrl,
        isAuthenticated,
        isLoading,
        error,
        continueFlow,
        goToLogin,
    };
}
