<script setup lang="ts">
import ApiTokensContent from '../components/ApiTokensContent.vue';
import ApiTokensSkeleton from '../components/ApiTokensSkeleton.vue';
</script>

<template>
    <Suspense>
        <ApiTokensContent />

        <template #fallback>
            <ApiTokensSkeleton />
        </template>
    </Suspense>
</template>
