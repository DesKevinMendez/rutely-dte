<script setup lang="ts">
import { Alert } from 'ornito';
import { useCertificateUi } from '../composables/useCertificateUi';
import CertificateRequirementsCard from './CertificateRequirementsCard.vue';
import CertificateStatusCard from './CertificateStatusCard.vue';
import CertificateUploadForm from './CertificateUploadForm.vue';

const {
    selectedEnvironment,
    uploadSuccess,
    certificateMetadata,
    isLoading,
    error,
    changeEnvironment,
    uploadCertificate,
} = await useCertificateUi();
</script>

<template>
    <div class="space-y-6">
        <div>
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

        <Alert v-if="error" type="danger">
            {{ error }}
        </Alert>

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
                    :is-submitting="isLoading"
                    @environment-change="changeEnvironment"
                    @upload="uploadCertificate"
                />
            </div>
        </div>
    </div>
</template>
