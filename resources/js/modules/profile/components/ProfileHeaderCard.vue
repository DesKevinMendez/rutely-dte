<script setup lang="ts">
import { Card } from 'ornito';
import type { ProfileSummary } from '../types/profile.types';

defineProps<{
    profile: ProfileSummary;
}>();
</script>

<template>
    <Card>
        <div class="flex items-center gap-4">
            <div class="flex h-16 w-16 shrink-0 items-center justify-center rounded-full bg-gray-200 dark:bg-gray-700">
                <span class="text-xl font-semibold text-gray-900 dark:text-white">{{ profile.initials }}</span>
            </div>
            <div>
                <div class="text-lg font-bold text-gray-900 dark:text-white">{{ profile.name }}</div>
                <span class="mt-1 inline-block rounded-full bg-primary-100 px-2.5 py-0.5 text-xs font-semibold text-primary-700 dark:bg-primary-900/30 dark:text-primary-300">
                    {{ profile.role }}
                </span>
            </div>
        </div>
    </Card>
</template>
