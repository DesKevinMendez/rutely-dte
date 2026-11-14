<script setup lang="ts">
import { Card, InfoItem } from 'ornito';
import { formatIdentityDocument } from '@/core/composables/formatIdentityDocument';
import { formatSalvadoranPhone } from '@/core/composables/formatSalvadoranPhone';
import { useProfileUi } from '../composables/useProfileUi';

const { company } = useProfileUi();
</script>

<template>
    <div v-if="company" class="space-y-4">
        <Card title="Datos de empresa">
            <div class="grid gap-x-4 gap-y-4 sm:grid-cols-2">
                <InfoItem label="Nombre de la empresa">
                    {{ company.name }}
                </InfoItem>
                <InfoItem label="Nombre comercial">
                    {{ company.commercial_name }}
                </InfoItem>
                <InfoItem label="NIT">
                    {{ formatIdentityDocument(company.nit) }}
                </InfoItem>
                <InfoItem label="NRC">
                    {{ company.nrc || 'No registrado' }}
                </InfoItem>
                <InfoItem label="Correo electrónico">
                    {{ company.email }}
                </InfoItem>
                <InfoItem label="Teléfono">
                    {{ formatSalvadoranPhone(company.phone) }}
                </InfoItem>
                <InfoItem label="Dirección" column>
                    {{ company.address }}
                </InfoItem>
            </div>
        </Card>

        <Card title="Configuración de facturación electrónica">
            <div class="grid gap-x-4 gap-y-4 sm:grid-cols-2">
                <InfoItem label="Código de actividad económica">
                    {{ company.economic_activity_code }}
                </InfoItem>
                <InfoItem label="Tipo de establecimiento">
                    {{ company.establishment_type }}
                </InfoItem>
                <InfoItem label="Código de establecimiento MH">
                    {{ company.mh_establishment_code }}
                </InfoItem>
                <InfoItem label="Código de punto de venta MH">
                    {{ company.mh_pos_code }}
                </InfoItem>
                <InfoItem label="Código propio de establecimiento">
                    {{ company.own_establishment_code }}
                </InfoItem>
                <InfoItem label="Código propio de punto de venta">
                    {{ company.own_pos_code }}
                </InfoItem>
                <InfoItem label="Ambiente">
                    {{
                        company.environment === '01' ? 'Producción' : 'Pruebas'
                    }}
                </InfoItem>
            </div>
        </Card>
    </div>
    <Card v-else> Aún no tienes una empresa asociada. </Card>
</template>
