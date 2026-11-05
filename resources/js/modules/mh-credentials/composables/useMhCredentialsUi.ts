import { computed, ref, watch } from 'vue';
import { useRequest } from '@/core/composables/useRequest';
import { useAuth } from '@/core/stores/auth';
import type { ApiResponse } from '@/core/types/api.types';
import type {
    MhApiEnvironment,
    MhCredentialsApiMetadata,
    MhCredentialsMetadata,
    MhCredentialsPayload,
    MhCredentialsStorePayload,
    MhEnvironment,
} from '../types/mh-credentials.types';

const fromApiEnvironment = (environment: MhApiEnvironment): MhEnvironment =>
    environment === '01' ? 'PRODUCCION' : 'PRUEBAS';

const mapMetadata = (
    metadata: MhCredentialsApiMetadata,
): MhCredentialsMetadata => ({
    id: metadata.id,
    nit: metadata.nit,
    environment: fromApiEnvironment(metadata.environment),
    active: metadata.active,
    loginStatus: metadata.login_status,
    updatedAt: metadata.updated_at,
});

export async function useMhCredentialsUi() {
    const auth = useAuth();
    const apiEnvironment = computed(
        () => auth.user?.company?.environment ?? '00',
    );
    const selectedEnvironment = computed(() =>
        fromApiEnvironment(apiEnvironment.value),
    );
    const saveSuccess = ref(false);
    const saveError = ref<string | null>(null);
    const credentialsMetadata = ref<MhCredentialsMetadata | null>(null);
    const error = ref<string | null>(null);
    const { get } = useRequest();
    const { post, isLoading: isSubmitting } = useRequest();
    let latestLoad = 0;

    const loadCredentials = async (
        environment: MhApiEnvironment,
    ): Promise<void> => {
        const load = ++latestLoad;
        const response = await get<ApiResponse<MhCredentialsApiMetadata>>(
            `/api/v1/mh-credentials?environment=${environment}`,
        );

        if (load !== latestLoad) {
            return;
        }

        if (response.data.value) {
            credentialsMetadata.value = mapMetadata(response.data.value.data);
        } else if (response.statusCode.value !== 404) {
            error.value =
                response.error.value ??
                'No se pudieron cargar las credenciales MH.';
        }
    };

    watch(apiEnvironment, (environment) => {
        credentialsMetadata.value = null;
        error.value = null;
        saveSuccess.value = false;
        saveError.value = null;

        void loadCredentials(environment);
    });

    const saveCredentials = async (
        payload: MhCredentialsPayload,
    ): Promise<void> => {
        if (isSubmitting.value) {
            return;
        }

        const environment = apiEnvironment.value;
        saveSuccess.value = false;
        saveError.value = null;

        const normalizedNit = payload.nit.replace(/\s/g, '');
        const requestPayload: MhCredentialsStorePayload = {
            environment,
            nit: normalizedNit,
            pwd: payload.password,
        };
        const response = await post<
            ApiResponse<MhCredentialsApiMetadata>,
            MhCredentialsStorePayload
        >('/api/v1/mh-credentials', requestPayload);

        if (environment !== apiEnvironment.value) {
            return;
        }

        if (!response.data.value) {
            saveError.value =
                response.error.value ??
                'No se pudieron guardar las credenciales MH.';

            return;
        }

        // The save response supersedes any pending metadata request.
        latestLoad++;
        credentialsMetadata.value = mapMetadata(response.data.value.data);
        saveSuccess.value = true;
        error.value = null;
    };

    await loadCredentials(apiEnvironment.value);

    return {
        selectedEnvironment,
        saveSuccess,
        saveError,
        credentialsMetadata,
        isSubmitting,
        error,
        saveCredentials,
    };
}
