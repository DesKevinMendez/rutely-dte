<script setup lang="ts">
import DashboardContent from '../components/DashboardContent.vue';
import DashboardSkeleton from '../components/DashboardSkeleton.vue';
</script>

<template>
    <Suspense>
        <DashboardContent />

        <template #fallback>
            <DashboardSkeleton />
        </template>
    </Suspense>
</template>
