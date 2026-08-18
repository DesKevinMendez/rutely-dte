import { computed, reactive, watch } from 'vue';
import { useForm } from 'vee-validate';
import { useRouter } from 'vue-router';
import * as yup from 'yup';
import { useAuth } from '@/core/stores/auth';
import type { OnboardingForm } from '../types/Onboarding';

const requiredText = (message: string) => yup.string().required(message);

const rules = {
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

const requiredFields = [
    'name',
    'commercialName',
    'nit',
    'phone',
    'email',
    'address',
    'economicActivityCode',
    'establishmentType',
    'departmentId',
    'municipalityId',
    'districtId',
    'ownEstablishmentCode',
    'ownPosCode',
] as const;

export default function useOnboarding() {
    const router = useRouter();
    const auth = useAuth();

    const { defineField, setFieldValue, validateField } = useForm<OnboardingForm>({
        validationSchema: yup.object(rules),
        initialValues: {
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
        },
    });

    const [name] = defineField('name');
    const [commercialName] = defineField('commercialName');
    const [nit] = defineField('nit');
    const [nrc] = defineField('nrc');
    const [phone] = defineField('phone');
    const [email] = defineField('email');
    const [address] = defineField('address');
    const [economicActivityCode] = defineField('economicActivityCode');
    const [establishmentType] = defineField('establishmentType');
    const [departmentId] = defineField('departmentId');
    const [municipalityId] = defineField('municipalityId');
    const [districtId] = defineField('districtId');
    const [ownEstablishmentCode] = defineField('ownEstablishmentCode');
    const [ownPosCode] = defineField('ownPosCode');

    const form = reactive({
        name,
        commercialName,
        nit,
        nrc,
        phone,
        email,
        address,
        economicActivityCode,
        establishmentType,
        departmentId,
        municipalityId,
        districtId,
        ownEstablishmentCode,
        ownPosCode,
    }) as OnboardingForm;

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
            setFieldValue('municipalityId', '');
            setFieldValue('districtId', '');
        },
    );

    watch(
        () => form.municipalityId,
        () => {
            setFieldValue('districtId', '');
        },
    );

    const validateRequiredFields = async (): Promise<boolean> => {
        const results = await Promise.all(requiredFields.map((field) => validateField(field)));

        return results.every((result) => result.valid);
    };

    const continueFlow = async (): Promise<void> => {
        await validateRequiredFields();
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
        continueFlow,
        goToLogin,
    };
}
