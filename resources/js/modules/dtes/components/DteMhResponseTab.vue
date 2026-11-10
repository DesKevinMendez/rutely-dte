<script setup lang="ts">
import { Card, InfoItem } from 'ornito';
import { computed } from 'vue';
import type { DteApiRecord } from '../types/dte.types';

const props = defineProps<{
    dte: DteApiRecord;
}>();

const mhResponse = computed(() =>
    props.dte.mh_transmission
        ? props.dte.mh_transmission.response_json
        : props.dte.mh_response_json,
);

const transmissionStatus = computed(() => {
    switch (props.dte.mh_transmission?.status) {
        case 'success':
            return 'Exitosa';
        case 'rejected':
            return 'Rechazada';
        case 'failed':
            return 'Fallida';
        case 'pending':
            return 'Pendiente';
        default:
            return 'No disponible';
    }
});
</script>

<template>
    <div class="space-y-4 pt-4">
        <Card title="Transmisión">
            <div v-if="dte.mh_transmission" class="grid gap-4 sm:grid-cols-2">
                <InfoItem label="Intento">{{
                    dte.mh_transmission.attempt
                }}</InfoItem>
                <InfoItem label="Estado de transmisión">{{
                    transmissionStatus
                }}</InfoItem>
                <InfoItem label="HTTP">{{
                    dte.mh_transmission.http_status ?? 'Sin respuesta HTTP'
                }}</InfoItem>
            </div>
            <p v-else class="text-sm text-gray-600 dark:text-gray-400">
                No hay un intento de transmisión guardado para este DTE.
            </p>
        </Card>

        <Card title="Respuesta de Hacienda">
            <div v-if="mhResponse" class="grid gap-4 sm:grid-cols-2">
                <InfoItem label="Versión">{{
                    mhResponse.version ?? 'No disponible'
                }}</InfoItem>
                <InfoItem label="Ambiente">{{
                    mhResponse.ambiente ?? 'No disponible'
                }}</InfoItem>
                <InfoItem label="Versión de aplicación">{{
                    mhResponse.versionApp ?? 'No disponible'
                }}</InfoItem>
                <InfoItem label="Estado">{{
                    mhResponse.estado ?? 'No disponible'
                }}</InfoItem>
                <InfoItem label="Código de generación"
                    ><span class="break-all">{{
                        mhResponse.codigoGeneracion ?? 'No disponible'
                    }}</span></InfoItem
                >
                <InfoItem label="Sello recibido"
                    ><span class="break-all">{{
                        mhResponse.selloRecibido ?? 'No recibido'
                    }}</span></InfoItem
                >
                <InfoItem label="Fecha de procesamiento">{{
                    mhResponse.fhProcesamiento ?? 'No disponible'
                }}</InfoItem>
                <InfoItem label="Clasificación del mensaje">{{
                    mhResponse.clasificaMsg ?? 'No disponible'
                }}</InfoItem>
                <InfoItem label="Código del mensaje">{{
                    mhResponse.codigoMsg ?? 'No disponible'
                }}</InfoItem>
                <InfoItem label="Descripción"
                    ><span class="break-words whitespace-pre-wrap">{{
                        mhResponse.descripcionMsg ?? 'Sin descripción'
                    }}</span></InfoItem
                >
                <InfoItem label="Observaciones">
                    <span
                        v-if="mhResponse.observaciones?.length"
                        class="break-words whitespace-pre-wrap"
                        >{{ mhResponse.observaciones.join('\n') }}</span
                    >
                    <span v-else>Sin observaciones</span>
                </InfoItem>
            </div>
            <p v-else class="text-sm text-gray-600 dark:text-gray-400">
                Hacienda no devolvió una respuesta para esta transmisión.
                {{ dte.mh_response_json?.descripcionMsg ?? '' }}
            </p>
        </Card>

        <Card v-if="mhResponse" title="Respuesta JSON íntegra">
            <pre
                class="overflow-x-auto text-xs break-all whitespace-pre-wrap text-gray-800 dark:text-gray-200"
                >{{ JSON.stringify(mhResponse, null, 2) }}</pre>
        </Card>
    </div>
</template>
