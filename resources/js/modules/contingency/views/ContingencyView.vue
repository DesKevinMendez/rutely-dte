<script setup lang="ts">
import ContingencyContent from '../components/ContingencyContent.vue';
import ContingencySkeleton from '../components/ContingencySkeleton.vue';
</script>

<template>
    <Suspense>
        <ContingencyContent />

        <template #fallback>
            <ContingencySkeleton />
        </template>
    </Suspense>
</template>
