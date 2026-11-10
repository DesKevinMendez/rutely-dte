<script setup lang="ts">
import { Card } from 'ornito';
</script>

<template>
    <div
        class="space-y-4"
        aria-busy="true"
        aria-label="Cargando detalle del DTE"
    >
        <div class="flex gap-2 overflow-hidden pb-2">
            <div
                class="h-9 w-20 shrink-0 animate-pulse rounded bg-gray-200 dark:bg-gray-800"
            />
            <div
                class="h-9 w-16 shrink-0 animate-pulse rounded bg-gray-200 dark:bg-gray-800"
            />
            <div
                class="h-9 w-32 shrink-0 animate-pulse rounded bg-gray-200 dark:bg-gray-800"
            />
            <div
                class="h-9 w-28 shrink-0 animate-pulse rounded bg-gray-200 dark:bg-gray-800"
            />
        </div>

        <Card title="Comprobante">
            <div class="grid gap-4 sm:grid-cols-2">
                <div v-for="index in 8" :key="index" class="space-y-2">
                    <div
                        class="h-3 w-28 animate-pulse rounded bg-gray-200 dark:bg-gray-800"
                    />
                    <div
                        class="h-5 w-48 max-w-full animate-pulse rounded bg-gray-200 dark:bg-gray-800"
                    />
                </div>
            </div>
        </Card>

        <Card title="Hacienda">
            <div class="grid gap-4 sm:grid-cols-2">
                <div v-for="index in 4" :key="index" class="space-y-2">
                    <div
                        class="h-3 w-28 animate-pulse rounded bg-gray-200 dark:bg-gray-800"
                    />
                    <div
                        class="h-5 w-40 max-w-full animate-pulse rounded bg-gray-200 dark:bg-gray-800"
                    />
                </div>
            </div>
        </Card>
    </div>
</template>
