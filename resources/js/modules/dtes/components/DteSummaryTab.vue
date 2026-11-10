<script setup lang="ts">
import { Badge, Card, InfoItem } from 'ornito';
import type { BadgeVariant } from 'ornito';
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

const typeLabels: Record<string, string> = {
    '01': 'Factura',
    '03': 'Comprobante de crédito fiscal',
    '05': 'Nota de crédito',
    '14': 'Factura de sujeto excluido',
};

const statusVariant = (status: string): BadgeVariant => {
    switch (status) {
        case 'RECIBIDO':
        case 'PROCESADO':
            return 'success';
        case 'RECHAZADO':
            return 'danger';
        case 'CONTINGENCIA':
        case 'FIRMADO':
            return 'warning';
        default:
            return 'neutral';
    }
};

const formatTimestamp = (value: string): string =>
    new Date(value).toLocaleString('es-SV', {
        timeZone: 'America/El_Salvador',
    });
</script>

<template>
    <div class="space-y-4 pt-4">
        <Card title="Comprobante">
            <div class="grid gap-4 sm:grid-cols-2">
                <InfoItem label="Estado">
                    <Badge :variant="statusVariant(dte.status)" text="sm">{{
                        dte.status
                    }}</Badge>
                </InfoItem>
                <InfoItem label="Tipo DTE"
                    >{{ typeLabels[dte.dte_type] ?? dte.dte_type }} ({{
                        dte.dte_type
                    }})</InfoItem
                >
                <InfoItem label="Código de generación"
                    ><span class="break-all">{{
                        dte.generation_code
                    }}</span></InfoItem
                >
                <InfoItem label="Número de control"
                    ><span class="break-all">{{
                        dte.control_number
                    }}</span></InfoItem
                >
                <InfoItem label="Fecha y hora de emisión">
                    {{
                        dte.original_json?.identificacion?.fecEmi ??
                        'No disponible'
                    }}
                    {{ dte.original_json?.identificacion?.horEmi ?? '' }}
                </InfoItem>
                <InfoItem label="Monto total"
                    >${{ (dte.total_amount / 100).toFixed(2) }}</InfoItem
                >
                <InfoItem label="Receptor">{{
                    dte.original_json?.receptor?.nombre ?? 'Cliente General'
                }}</InfoItem>
                <InfoItem label="Documento del receptor">{{
                    dte.receiver_document ?? 'No disponible'
                }}</InfoItem>
                <InfoItem label="NIT del emisor">{{ dte.issuer_nit }}</InfoItem>
                <InfoItem label="Ambiente">{{
                    dte.environment === '00'
                        ? 'Pruebas (00)'
                        : dte.environment === '01'
                          ? 'Producción (01)'
                          : dte.environment
                }}</InfoItem>
                <InfoItem label="Versión del DTE">{{ dte.version }}</InfoItem>
            </div>
        </Card>

        <Card title="Hacienda">
            <div v-if="mhResponse" class="grid gap-4 sm:grid-cols-2">
                <InfoItem label="Estado de la respuesta">
                    <Badge
                        :variant="
                            statusVariant(mhResponse.estado ?? dte.status)
                        "
                        text="sm"
                    >
                        {{ mhResponse.estado ?? 'No disponible' }}
                    </Badge>
                </InfoItem>
                <InfoItem label="Fecha de procesamiento">{{
                    mhResponse.fhProcesamiento ?? 'No disponible'
                }}</InfoItem>
                <InfoItem label="Sello de recepción"
                    ><span class="break-all">{{
                        mhResponse.selloRecibido ?? 'No recibido'
                    }}</span></InfoItem
                >
                <InfoItem label="Descripción"
                    ><span class="break-words whitespace-pre-wrap">{{
                        mhResponse.descripcionMsg ?? 'Sin descripción'
                    }}</span></InfoItem
                >
            </div>
            <p v-else class="text-sm text-gray-600 dark:text-gray-400">
                Hacienda no devolvió una respuesta para esta transmisión.
                {{ dte.mh_response_json?.descripcionMsg ?? '' }}
            </p>
        </Card>

        <Card title="Registro">
            <div class="grid gap-4 sm:grid-cols-2">
                <InfoItem label="ID del DTE"
                    ><span class="break-all">{{ dte.id }}</span></InfoItem
                >
                <InfoItem label="ID de empresa"
                    ><span class="break-all">{{
                        dte.company_id
                    }}</span></InfoItem
                >
                <InfoItem label="ID de receptor"
                    ><span class="break-all">{{
                        dte.receiver_id ?? 'No disponible'
                    }}</span></InfoItem
                >
                <InfoItem label="ID de contingencia"
                    ><span class="break-all">{{
                        dte.contingency_event_id ?? 'No aplica'
                    }}</span></InfoItem
                >
                <InfoItem label="Creado">{{
                    formatTimestamp(dte.created_at)
                }}</InfoItem>
                <InfoItem label="Actualizado">{{
                    formatTimestamp(dte.updated_at)
                }}</InfoItem>
                <InfoItem label="PDF"
                    ><span class="break-all">{{
                        dte.pdf_url ?? 'No disponible'
                    }}</span></InfoItem
                >
                <InfoItem label="Observaciones guardadas"
                    ><span class="break-words whitespace-pre-wrap">{{
                        dte.observations ?? 'Sin observaciones'
                    }}</span></InfoItem
                >
            </div>
        </Card>
    </div>
</template>
