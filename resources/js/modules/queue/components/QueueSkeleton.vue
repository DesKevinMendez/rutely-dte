<script setup lang="ts">
import { Card, TableSkeleton } from 'ornito';
import type { TableField } from 'ornito';

const columns: TableField<Record<string, unknown>>[] = [
    { label: 'ID Transmisión', key: 'id' },
    { label: 'DTE / Recurso', key: 'resource' },
    { label: 'Operación', key: 'operation' },
    { label: 'Intento', key: 'attempt' },
    { label: 'HTTP', key: 'http' },
    { label: 'Estado Cola', key: 'status' },
    { label: 'Último Error', key: 'error' },
    { label: 'Creado', key: 'created' },
];
</script>

<template>
    <div class="space-y-6" aria-busy="true" aria-label="Cargando cola">
        <div
            class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
        >
            <div class="space-y-2">
                <div class="h-7 w-[30rem] max-w-full animate-pulse rounded bg-gray-200 dark:bg-gray-800" />
                <div class="h-4 w-[34rem] max-w-full animate-pulse rounded bg-gray-200 dark:bg-gray-800" />
            </div>
            <div class="flex gap-2.5">
                <div class="h-9 w-32 animate-pulse rounded-lg bg-gray-200 dark:bg-gray-800" />
                <div class="h-9 w-40 animate-pulse rounded-lg bg-gray-200 dark:bg-gray-800" />
            </div>
        </div>

        <Card title="Transmisiones Pendientes y Fallidas">
            <TableSkeleton :columns="columns" :rows="8" />
        </Card>

        <div class="ml-auto h-3 w-48 animate-pulse rounded bg-gray-200 dark:bg-gray-800" />
    </div>
</template>
