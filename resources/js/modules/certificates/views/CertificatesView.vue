<script setup lang="ts">
import CertificatesContent from '../components/CertificatesContent.vue';
import CertificatesSkeleton from '../components/CertificatesSkeleton.vue';
</script>

<template>
    <Suspense>
        <CertificatesContent />

        <template #fallback>
            <CertificatesSkeleton />
        </template>
    </Suspense>
</template>
