<script setup lang="ts">
import { Card } from 'ornito';
</script>

<template>
    <div
        class="space-y-6"
        aria-busy="true"
        aria-label="Cargando credenciales MH"
    >
        <div class="space-y-2">
            <div
                class="h-7 w-96 max-w-full animate-pulse rounded bg-gray-200 dark:bg-gray-800"
            />
            <div
                class="h-4 w-[36rem] max-w-full animate-pulse rounded bg-gray-200 dark:bg-gray-800"
            />
        </div>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <div class="lg:col-span-1">
                <Card>
                    <div class="space-y-4">
                        <div
                            class="h-5 w-44 animate-pulse rounded bg-gray-200 dark:bg-gray-800"
                        />
                        <div
                            class="h-4 w-full animate-pulse rounded bg-gray-200 dark:bg-gray-800"
                        />
                        <div
                            class="h-28 w-full animate-pulse rounded-lg bg-gray-200 dark:bg-gray-800"
                        />
                    </div>
                </Card>
            </div>
            <div class="lg:col-span-2">
                <Card>
                    <div class="space-y-5">
                        <div
                            class="h-5 w-52 animate-pulse rounded bg-gray-200 dark:bg-gray-800"
                        />
                        <div
                            class="h-16 w-full animate-pulse rounded-lg bg-gray-200 dark:bg-gray-800"
                        />
                        <div class="grid grid-cols-2 gap-3">
                            <div
                                class="h-16 animate-pulse rounded-lg bg-gray-200 dark:bg-gray-800"
                            />
                            <div
                                class="h-16 animate-pulse rounded-lg bg-gray-200 dark:bg-gray-800"
                            />
                        </div>
                        <div
                            class="h-10 w-full animate-pulse rounded-lg bg-gray-200 dark:bg-gray-800"
                        />
                        <div
                            class="h-10 w-full animate-pulse rounded-lg bg-gray-200 dark:bg-gray-800"
                        />
                    </div>
                </Card>
            </div>
        </div>
    </div>
</template>
