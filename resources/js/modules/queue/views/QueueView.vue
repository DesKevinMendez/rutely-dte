<script setup lang="ts">
import QueueContent from '../components/QueueContent.vue';
import QueueSkeleton from '../components/QueueSkeleton.vue';
</script>

<template>
    <Suspense>
        <QueueContent />

        <template #fallback>
            <QueueSkeleton />
        </template>
    </Suspense>
</template>
