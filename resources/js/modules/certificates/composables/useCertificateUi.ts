import { computed, ref } from 'vue';
import type {
    CertificateEnvironment,
    CertificateMetadata,
    CertificateUploadPayload,
} from '../types/certificate.types';

export function useCertificateUi() {
    const selectedEnvironment = ref<CertificateEnvironment>('PRUEBAS');
    const uploadSuccess = ref(false);
    const metadataByEnvironment = ref<
        Record<CertificateEnvironment, CertificateMetadata | null>
    >({
        PRUEBAS: {
            nit: '0614-280390-112-1',
            environment: 'PRUEBAS',
            active: true,
            updatedAt: '2026-08-17T15:20:00-06:00',
        },
        PRODUCCION: null,
    });

    const certificateMetadata = computed(
        () => metadataByEnvironment.value[selectedEnvironment.value],
    );

    const changeEnvironment = (environment: CertificateEnvironment): void => {
        selectedEnvironment.value = environment;
        uploadSuccess.value = false;
    };

    const uploadCertificate = (payload: CertificateUploadPayload): void => {
        if (!payload.fileName) {
            return;
        }

        metadataByEnvironment.value[selectedEnvironment.value] = {
            nit: '0614-280390-112-1',
            environment: selectedEnvironment.value,
            active: true,
            updatedAt: new Date().toISOString(),
        };
        uploadSuccess.value = true;
    };

    return {
        selectedEnvironment,
        uploadSuccess,
        certificateMetadata,
        changeEnvironment,
        uploadCertificate,
    };
}
