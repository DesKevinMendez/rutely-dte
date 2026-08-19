import { ref } from 'vue';
import * as yup from 'yup';

const emailRule = yup.string().required('El correo electrónico es requerido.').email('Ingresá un correo electrónico válido.');

export default function useRecovery() {
    const email = ref('');
    const successMessage = ref<string | null>(null);

    const submit = (): void => {
        successMessage.value = `Las instrucciones de recuperación se enviarán a ${email.value}.`;
    };

    return {
        email,
        emailRule,
        successMessage,
        submit,
    };
}
