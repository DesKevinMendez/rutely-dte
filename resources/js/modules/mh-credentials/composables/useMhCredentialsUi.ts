import { computed, ref } from 'vue';
import { useRequest } from '@/core/composables/useRequest';
import type { ApiResponse } from '@/core/types/api.types';
import type {
    MhApiEnvironment,
    MhCredentialsApiMetadata,
    MhCredentialsMetadata,
    MhCredentialsPayload,
    MhCredentialsStorePayload,
    MhEnvironment,
} from '../types/mh-credentials.types';

const toApiEnvironment = (environment: MhEnvironment): MhApiEnvironment =>
    environment === 'PRODUCCION' ? '01' : '00';

const fromApiEnvironment = (environment: MhApiEnvironment): MhEnvironment =>
    environment === '01' ? 'PRODUCCION' : 'PRUEBAS';

const mapMetadata = (
    metadata: MhCredentialsApiMetadata,
): MhCredentialsMetadata => ({
    id: metadata.id,
    nit: metadata.nit,
    environment: fromApiEnvironment(metadata.environment),
    active: metadata.active,
    updatedAt: metadata.updated_at,
});

export async function useMhCredentialsUi() {
    const selectedEnvironment = ref<MhEnvironment>('PRUEBAS');
    const saveSuccess = ref(false);
    const metadataByEnvironment = ref<
        Record<MhEnvironment, MhCredentialsMetadata | null>
    >({
        PRUEBAS: null,
        PRODUCCION: null,
    });
    const error = ref<string | null>(null);
    const { get, post, isLoading } = useRequest();

    const credentialsMetadata = computed(
        () => metadataByEnvironment.value[selectedEnvironment.value],
    );

    const loadCredentials = async (
        environment: MhEnvironment = selectedEnvironment.value,
    ): Promise<boolean> => {
        const apiEnvironment = toApiEnvironment(environment);
        const response = await get<ApiResponse<MhCredentialsApiMetadata>>(
            `/api/v1/mh-credentials?environment=${apiEnvironment}`,
        );

        if (response.data.value) {
            metadataByEnvironment.value[environment] = mapMetadata(
                response.data.value.data,
            );
            error.value = null;

            return true;
        }

        if (response.statusCode.value === 404) {
            metadataByEnvironment.value[environment] = null;
            error.value = null;

            return true;
        }

        error.value =
            response.error.value ?? 'No se pudieron cargar las credenciales MH.';

        return false;
    };

    const changeEnvironment = async (
        environment: MhEnvironment,
    ): Promise<void> => {
        selectedEnvironment.value = environment;
        saveSuccess.value = false;
        await loadCredentials(environment);
    };

    const saveCredentials = async (
        payload: MhCredentialsPayload,
    ): Promise<boolean> => {
        const normalizedNit = payload.nit.replace(/\s/g, '');
        const requestPayload: MhCredentialsStorePayload = {
            environment: toApiEnvironment(selectedEnvironment.value),
            nit: normalizedNit,
            pwd: payload.password,
        };
        const response = await post<
            ApiResponse<MhCredentialsApiMetadata>,
            MhCredentialsStorePayload
        >('/api/v1/mh-credentials', requestPayload);

        if (!response.data.value) {
            saveSuccess.value = false;
            error.value =
                response.error.value ?? 'No se pudieron guardar las credenciales MH.';

            return false;
        }

        metadataByEnvironment.value[selectedEnvironment.value] = mapMetadata(
            response.data.value.data,
        );
        saveSuccess.value = true;
        error.value = null;

        return true;
    };

    await loadCredentials();

    return {
        selectedEnvironment,
        saveSuccess,
        credentialsMetadata,
        isLoading,
        error,
        changeEnvironment,
        saveCredentials,
    };
}
