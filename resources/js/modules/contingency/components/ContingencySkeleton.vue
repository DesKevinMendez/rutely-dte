<script setup lang="ts">
import { Card } from 'ornito';
</script>

<template>
    <div
        class="space-y-6"
        aria-busy="true"
        aria-label="Cargando contingencia"
    >
        <div class="space-y-2">
            <div class="h-7 w-96 max-w-full animate-pulse rounded bg-gray-200 dark:bg-gray-800" />
            <div class="h-4 w-[34rem] max-w-full animate-pulse rounded bg-gray-200 dark:bg-gray-800" />
        </div>

        <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
            <Card v-for="index in 2" :key="index">
                <div class="space-y-4">
                    <div class="h-4 w-40 animate-pulse rounded bg-gray-200 dark:bg-gray-800" />
                    <div class="h-7 w-56 animate-pulse rounded bg-gray-200 dark:bg-gray-800" />
                    <div class="h-px bg-gray-200 dark:bg-gray-800" />
                    <div class="h-4 w-full animate-pulse rounded bg-gray-200 dark:bg-gray-800" />
                    <div class="h-4 w-4/5 animate-pulse rounded bg-gray-200 dark:bg-gray-800" />
                </div>
            </Card>
        </div>

        <Card>
            <div class="space-y-3">
                <div class="h-5 w-60 animate-pulse rounded bg-gray-200 dark:bg-gray-800" />
                <div class="h-4 w-full animate-pulse rounded bg-gray-200 dark:bg-gray-800" />
                <div class="h-4 w-5/6 animate-pulse rounded bg-gray-200 dark:bg-gray-800" />
            </div>
        </Card>
    </div>
</template>
