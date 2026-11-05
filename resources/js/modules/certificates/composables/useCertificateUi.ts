import { computed, ref, watch } from 'vue';
import { useRequest } from '@/core/composables/useRequest';
import { useAuth } from '@/core/stores/auth';
import type { ApiResponse } from '@/core/types/api.types';
import type {
    CertificateApiEnvironment,
    CertificateApiMetadata,
    CertificateEnvironment,
    CertificateMetadata,
    CertificateStorePayload,
    CertificateUploadPayload,
} from '../types/certificate.types';

const fromApiEnvironment = (
    environment: CertificateApiEnvironment,
): CertificateEnvironment => (environment === '01' ? 'PRODUCCION' : 'PRUEBAS');

const mapMetadata = (
    metadata: CertificateApiMetadata,
): CertificateMetadata => ({
    id: metadata.id,
    nit: metadata.nit,
    environment: fromApiEnvironment(metadata.environment),
    active: metadata.active,
    updatedAt: metadata.updated_at,
});

export async function useCertificateUi() {
    const auth = useAuth();
    const apiEnvironment = computed(
        () => auth.user?.company?.environment ?? '00',
    );
    const selectedEnvironment = computed(() =>
        fromApiEnvironment(apiEnvironment.value),
    );
    const uploadSuccess = ref(false);
    const certificateMetadata = ref<CertificateMetadata | null>(null);
    const error = ref<string | null>(null);
    const isSubmitting = ref(false);
    const { get } = useRequest();
    const { post } = useRequest();
    let latestLoad = 0;

    const loadCertificate = async (
        environment: CertificateApiEnvironment,
    ): Promise<void> => {
        const load = ++latestLoad;
        const response = await get<ApiResponse<CertificateApiMetadata>>(
            `/api/v1/mh-certificates?environment=${environment}`,
        );

        if (load !== latestLoad) {
            return;
        }

        if (response.data.value) {
            certificateMetadata.value = mapMetadata(response.data.value.data);
        } else if (response.statusCode.value !== 404) {
            error.value =
                response.error.value ??
                'No se pudo cargar el certificado digital.';
        }
    };

    watch(apiEnvironment, (environment) => {
        certificateMetadata.value = null;
        error.value = null;
        uploadSuccess.value = false;

        void loadCertificate(environment);
    });

    const uploadCertificate = async (
        payload: CertificateUploadPayload,
    ): Promise<void> => {
        if (isSubmitting.value) {
            return;
        }

        const environment = apiEnvironment.value;
        isSubmitting.value = true;
        uploadSuccess.value = false;
        error.value = null;

        try {
            const requestPayload: CertificateStorePayload = {
                environment,
                certificadoXml: await payload.file.text(),
                passwordPri: payload.password.trim() || null,
            };
            const response = await post<
                ApiResponse<CertificateApiMetadata>,
                CertificateStorePayload
            >('/api/v1/mh-certificates', requestPayload);

            if (environment !== apiEnvironment.value) {
                return;
            }

            if (!response.data.value) {
                error.value =
                    response.error.value ??
                    'No se pudo guardar el certificado digital.';

                return;
            }

            // The upload response supersedes any pending metadata request.
            latestLoad++;
            certificateMetadata.value = mapMetadata(response.data.value.data);
            uploadSuccess.value = true;
            error.value = null;
        } catch {
            if (environment === apiEnvironment.value) {
                error.value = 'No se pudo guardar el certificado digital.';
            }
        } finally {
            isSubmitting.value = false;
        }
    };

    await loadCertificate(apiEnvironment.value);

    return {
        selectedEnvironment,
        uploadSuccess,
        certificateMetadata,
        isSubmitting,
        error,
        uploadCertificate,
    };
}
