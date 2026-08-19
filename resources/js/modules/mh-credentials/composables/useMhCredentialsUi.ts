import { computed, ref } from 'vue';
import type {
    MhCredentialsMetadata,
    MhCredentialsPayload,
    MhEnvironment,
} from '../types/mh-credentials.types';

export function useMhCredentialsUi() {
    const selectedEnvironment = ref<MhEnvironment>('PRUEBAS');
    const saveSuccess = ref(false);
    const metadataByEnvironment = ref<
        Record<MhEnvironment, MhCredentialsMetadata | null>
    >({
        PRUEBAS: {
            nit: '0614-280390-112-1',
            environment: 'PRUEBAS',
            active: true,
            updatedAt: '2026-08-18T10:40:00-06:00',
        },
        PRODUCCION: null,
    });

    const credentialsMetadata = computed(
        () => metadataByEnvironment.value[selectedEnvironment.value],
    );

    const changeEnvironment = (environment: MhEnvironment): void => {
        selectedEnvironment.value = environment;
        saveSuccess.value = false;
    };

    const saveCredentials = (payload: MhCredentialsPayload): void => {
        const normalizedNit = payload.nit.replace(/\D/g, '');

        if (normalizedNit.length !== 14 || !payload.password.trim()) {
            return;
        }

        const formattedNit = `${normalizedNit.slice(0, 4)}-${normalizedNit.slice(4, 10)}-${normalizedNit.slice(10, 13)}-${normalizedNit.slice(13)}`;
        metadataByEnvironment.value[selectedEnvironment.value] = {
            nit: formattedNit,
            environment: selectedEnvironment.value,
            active: true,
            updatedAt: new Date().toISOString(),
        };
        saveSuccess.value = true;
    };

    return {
        selectedEnvironment,
        saveSuccess,
        credentialsMetadata,
        changeEnvironment,
        saveCredentials,
    };
}
