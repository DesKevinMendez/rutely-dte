<script setup lang="ts">
import DtesContent from '../components/DtesContent.vue';
import DtesSkeleton from '../components/DtesSkeleton.vue';
</script>

<template>
    <Suspense>
        <DtesContent />

        <template #fallback>
            <DtesSkeleton />
        </template>
    </Suspense>
</template>
