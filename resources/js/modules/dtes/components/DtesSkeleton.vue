<script setup lang="ts">
import { Card, TableSkeleton } from 'ornito';
import type { TableField } from 'ornito';

const columns: TableField<Record<string, unknown>>[] = [
    { label: 'Código Generación', key: 'generation' },
    { label: 'Nº Control', key: 'control' },
    { label: 'Tipo DTE', key: 'type' },
    { label: 'Receptor', key: 'receiver' },
    { label: 'Monto Total', key: 'amount' },
    { label: 'Estado', key: 'status' },
    { label: 'Fecha', key: 'date' },
];
</script>

<template>
    <div class="space-y-6" aria-busy="true" aria-label="Cargando DTEs">
        <div
            class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
        >
            <div class="space-y-2">
                <div class="h-7 w-96 max-w-full animate-pulse rounded bg-gray-200 dark:bg-gray-800" />
                <div class="h-4 w-[32rem] max-w-full animate-pulse rounded bg-gray-200 dark:bg-gray-800" />
            </div>
            <div class="flex gap-2.5">
                <div class="h-9 w-24 animate-pulse rounded-lg bg-gray-200 dark:bg-gray-800" />
                <div class="h-9 w-36 animate-pulse rounded-lg bg-gray-200 dark:bg-gray-800" />
            </div>
        </div>

        <Card title="Listado de Comprobantes Emitidos">
            <div class="mb-4 flex justify-end gap-2">
                <div class="h-8 w-44 animate-pulse rounded bg-gray-200 dark:bg-gray-800" />
                <div class="h-8 w-44 animate-pulse rounded bg-gray-200 dark:bg-gray-800" />
            </div>
            <TableSkeleton :columns="columns" :rows="8" />
        </Card>
    </div>
</template>
