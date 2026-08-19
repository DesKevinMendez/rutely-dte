<template>
    <div class="animate-pulse space-y-6">
        <div
            class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
        >
            <div class="space-y-2">
                <div class="h-7 w-44 rounded bg-gray-200 dark:bg-gray-800" />
                <div class="h-4 w-96 max-w-full rounded bg-gray-100 dark:bg-gray-800" />
            </div>
            <div class="h-10 w-32 rounded-lg bg-gray-200 dark:bg-gray-800" />
        </div>

        <div
            class="overflow-hidden rounded-xl border border-gray-200 dark:border-gray-800"
        >
            <div
                class="border-b border-gray-200 px-5 py-4 dark:border-gray-800"
            >
                <div class="h-5 w-36 rounded bg-gray-200 dark:bg-gray-800" />
            </div>
            <div class="space-y-4 p-5">
                <div
                    v-for="row in 4"
                    :key="row"
                    class="grid grid-cols-4 gap-6"
                >
                    <div class="h-4 rounded bg-gray-100 dark:bg-gray-800" />
                    <div class="h-4 rounded bg-gray-100 dark:bg-gray-800" />
                    <div class="h-4 rounded bg-gray-100 dark:bg-gray-800" />
                    <div class="h-4 rounded bg-gray-100 dark:bg-gray-800" />
                </div>
            </div>
        </div>
    </div>
</template>
