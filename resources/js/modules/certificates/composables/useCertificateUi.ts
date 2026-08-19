import { computed, ref } from 'vue';
import { useRequest } from '@/core/composables/useRequest';
import type { ApiResponse } from '@/core/types/api.types';
import type {
    CertificateApiEnvironment,
    CertificateApiMetadata,
    CertificateEnvironment,
    CertificateMetadata,
    CertificateStorePayload,
    CertificateUploadPayload,
} from '../types/certificate.types';

const toApiEnvironment = (
    environment: CertificateEnvironment,
): CertificateApiEnvironment => (environment === 'PRODUCCION' ? '01' : '00');

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
    const selectedEnvironment = ref<CertificateEnvironment>('PRUEBAS');
    const uploadSuccess = ref(false);
    const metadataByEnvironment = ref<
        Record<CertificateEnvironment, CertificateMetadata | null>
    >({
        PRUEBAS: null,
        PRODUCCION: null,
    });
    const error = ref<string | null>(null);
    const { get, post, isLoading } = useRequest();

    const certificateMetadata = computed(
        () => metadataByEnvironment.value[selectedEnvironment.value],
    );

    const loadCertificate = async (
        environment: CertificateEnvironment = selectedEnvironment.value,
    ): Promise<boolean> => {
        const apiEnvironment = toApiEnvironment(environment);
        const response = await get<ApiResponse<CertificateApiMetadata>>(
            `/api/v1/mh-certificates?environment=${apiEnvironment}`,
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
            response.error.value ?? 'No se pudo cargar el certificado digital.';

        return false;
    };

    const changeEnvironment = async (
        environment: CertificateEnvironment,
    ): Promise<void> => {
        selectedEnvironment.value = environment;
        uploadSuccess.value = false;
        await loadCertificate(environment);
    };

    const uploadCertificate = async (
        payload: CertificateUploadPayload,
    ): Promise<boolean> => {
        const requestPayload: CertificateStorePayload = {
            environment: toApiEnvironment(selectedEnvironment.value),
            certificadoXml: await payload.file.text(),
            passwordPri: payload.password.trim() || null,
        };
        const response = await post<
            ApiResponse<CertificateApiMetadata>,
            CertificateStorePayload
        >('/api/v1/mh-certificates', requestPayload);

        if (!response.data.value) {
            uploadSuccess.value = false;
            error.value =
                response.error.value ??
                'No se pudo guardar el certificado digital.';

            return false;
        }

        metadataByEnvironment.value[selectedEnvironment.value] = mapMetadata(
            response.data.value.data,
        );
        uploadSuccess.value = true;
        error.value = null;

        return true;
    };

    await loadCertificate();

    return {
        selectedEnvironment,
        uploadSuccess,
        certificateMetadata,
        isLoading,
        error,
        changeEnvironment,
        uploadCertificate,
    };
}
