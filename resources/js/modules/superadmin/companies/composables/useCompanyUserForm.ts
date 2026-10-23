import { reactive } from 'vue';
import * as yup from 'yup';
import type { CompanyUserPayload } from '@/modules/superadmin/companies/types/company-user.types';

export default function useCompanyUserForm() {
    const formData = reactive({
        name: '',
        email: '',
        phone: '',
    });

    const nameRules = yup
        .string()
        .required('El nombre es requerido.')
        .min(2, 'El nombre debe contener al menos 2 caracteres.')
        .max(255, 'El nombre no debe contener más de 255 caracteres.');
    const emailRules = yup
        .string()
        .required('El correo electrónico es requerido.')
        .max(255, 'El correo electrónico no debe contener más de 255 caracteres.')
        .email('Ingresa un correo electrónico válido.');
    const phoneRules = yup
        .string()
        .test(
            'phone-length',
            'El teléfono debe contener 8 dígitos después del prefijo +503.',
            (value) =>
                !value ||
                value.replace(/^\+503\s*/, '').replace(/\D/g, '').length === 8,
        )
        .max(30, 'El teléfono no debe contener más de 30 caracteres.');

    const buildPayload = (): CompanyUserPayload => {
        const payload: CompanyUserPayload = {
            name: formData.name.trim(),
            email: formData.email.trim(),
        };

        if (formData.phone.trim()) {
            payload.phone = formData.phone.trim();
        }

        return payload;
    };

    return {
        formData,
        nameRules,
        emailRules,
        phoneRules,
        buildPayload,
    };
}
