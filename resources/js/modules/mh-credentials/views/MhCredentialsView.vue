<script setup lang="ts">
import MhCredentialsContent from '../components/MhCredentialsContent.vue';
import MhCredentialsSkeleton from '../components/MhCredentialsSkeleton.vue';
</script>

<template>
    <Suspense>
        <MhCredentialsContent />

        <template #fallback>
            <MhCredentialsSkeleton />
        </template>
    </Suspense>
</template>
