<script setup lang="ts">
import { Card } from 'ornito';
</script>

<template>
    <div class="space-y-6" aria-busy="true" aria-label="Cargando dashboard">
        <div
            class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
        >
            <div class="space-y-2">
                <div class="h-7 w-80 max-w-full animate-pulse rounded bg-gray-200 dark:bg-gray-800" />
                <div class="h-4 w-96 max-w-full animate-pulse rounded bg-gray-200 dark:bg-gray-800" />
                <div class="h-4 w-48 animate-pulse rounded bg-gray-200 dark:bg-gray-800" />
            </div>
            <div class="h-9 w-36 animate-pulse rounded-lg bg-gray-200 dark:bg-gray-800" />
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <Card v-for="index in 4" :key="index">
                <div class="space-y-4">
                    <div class="h-4 w-28 animate-pulse rounded bg-gray-200 dark:bg-gray-800" />
                    <div class="h-8 w-24 animate-pulse rounded bg-gray-200 dark:bg-gray-800" />
                    <div class="h-3 w-36 animate-pulse rounded bg-gray-200 dark:bg-gray-800" />
                </div>
            </Card>
        </div>

        <div class="ml-auto h-3 w-48 animate-pulse rounded bg-gray-200 dark:bg-gray-800" />
    </div>
</template>
