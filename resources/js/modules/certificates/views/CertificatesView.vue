<script setup lang="ts">
import CertificateRequirementsCard from '../components/CertificateRequirementsCard.vue';
import CertificateStatusCard from '../components/CertificateStatusCard.vue';
import CertificateUploadForm from '../components/CertificateUploadForm.vue';
import { useCertificateUi } from '../composables/useCertificateUi';

const {
    selectedEnvironment,
    uploadSuccess,
    certificateMetadata,
    changeEnvironment,
    uploadCertificate,
} = useCertificateUi();
</script>

<template>
    <div class="space-y-6">
        <div>
            <div
                class="mb-2 inline-flex rounded-full border border-primary-200 bg-primary-50 px-2.5 py-1 text-xs font-semibold text-primary-700 dark:border-primary-800 dark:bg-primary-950/40 dark:text-primary-300"
            >
                Simulación UI
            </div>
            <h1
                class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white"
            >
                Certificados Digitales (MH)
            </h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                Gestión y carga del certificado de firma electrónica del
                Ministerio de Hacienda de El Salvador
            </p>
        </div>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <div class="space-y-6 lg:col-span-1">
                <CertificateStatusCard
                    :metadata="certificateMetadata"
                    :selected-environment="selectedEnvironment"
                />
                <CertificateRequirementsCard />
            </div>
            <div class="lg:col-span-2">
                <CertificateUploadForm
                    :selected-environment="selectedEnvironment"
                    :upload-success="uploadSuccess"
                    @environment-change="changeEnvironment"
                    @upload="uploadCertificate"
                />
            </div>
        </div>
    </div>
</template>
