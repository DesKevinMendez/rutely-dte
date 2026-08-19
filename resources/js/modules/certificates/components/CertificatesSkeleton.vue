<script setup lang="ts">
import { Card } from 'ornito';
</script>

<template>
    <div class="space-y-6" aria-busy="true" aria-label="Cargando certificados">
        <div class="space-y-2">
            <div
                class="h-7 w-80 animate-pulse rounded bg-gray-200 dark:bg-gray-800"
            />
            <div
                class="h-4 w-[34rem] max-w-full animate-pulse rounded bg-gray-200 dark:bg-gray-800"
            />
        </div>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <div class="space-y-6 lg:col-span-1">
                <Card v-for="index in 2" :key="index">
                    <div class="space-y-4">
                        <div
                            class="h-5 w-40 animate-pulse rounded bg-gray-200 dark:bg-gray-800"
                        />
                        <div
                            class="h-4 w-full animate-pulse rounded bg-gray-200 dark:bg-gray-800"
                        />
                        <div
                            class="h-4 w-4/5 animate-pulse rounded bg-gray-200 dark:bg-gray-800"
                        />
                        <div
                            class="h-20 w-full animate-pulse rounded-lg bg-gray-200 dark:bg-gray-800"
                        />
                    </div>
                </Card>
            </div>
            <div class="lg:col-span-2">
                <Card>
                    <div class="space-y-5">
                        <div
                            class="h-5 w-56 animate-pulse rounded bg-gray-200 dark:bg-gray-800"
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
                            class="h-40 animate-pulse rounded-xl bg-gray-200 dark:bg-gray-800"
                        />
                        <div
                            class="h-10 animate-pulse rounded-lg bg-gray-200 dark:bg-gray-800"
                        />
                    </div>
                </Card>
            </div>
        </div>
    </div>
</template>
