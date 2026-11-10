<script setup lang="ts">
import { Card } from 'ornito';
import type { DteApiRecord } from '../types/dte.types';

defineProps<{
    dte: DteApiRecord;
}>();
</script>

<template>
    <div class="space-y-4 pt-4">
        <Card title="Documento original enviado a Hacienda">
            <pre
                class="overflow-x-auto text-xs break-all whitespace-pre-wrap text-gray-800 dark:text-gray-200"
                >{{ JSON.stringify(dte.original_json, null, 2) }}</pre>
        </Card>
        <Card title="Documento firmado">
            <pre
                v-if="dte.signed_json"
                class="overflow-x-auto text-xs break-all whitespace-pre-wrap text-gray-800 dark:text-gray-200"
                >{{ dte.signed_json }}</pre>
            <p v-else class="text-sm text-gray-600 dark:text-gray-400">
                Este DTE no tiene un documento firmado guardado.
            </p>
        </Card>
    </div>
</template>
