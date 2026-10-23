<script setup lang="ts">
import { IconEdit } from '@tabler/icons-vue';
import { BaseButton, Card, InfoItem } from 'ornito';
import useCompanyShow from '@/modules/superadmin/companies/composables/useCompanyShow';

const { company } = useCompanyShow();
</script>

<template>
    <div v-if="company" class="space-y-4">
        <Card title="Información de la empresa">
            <template #headerButtons>
                <BaseButton
                    :to="{
                        name: 'superadmin-company-profile-edit',
                        params: { id: company.id },
                    }"
                    variant="outline"
                    size="auto"
                    :icon="IconEdit"
                    icon-position="left"
                >
                    Editar perfil
                </BaseButton>
            </template>

            <div class="grid gap-x-4 gap-y-4 sm:grid-cols-2">
                <InfoItem label="Nombre de la empresa">
                    {{ company.name }}
                </InfoItem>
                <InfoItem label="Nombre comercial">
                    {{ company.commercial_name }}
                </InfoItem>
                <InfoItem label="NIT">{{ company.nit }}</InfoItem>
                <InfoItem label="NRC">
                    {{ company.nrc || 'No registrado' }}
                </InfoItem>
                <InfoItem label="Correo electrónico">
                    {{ company.email }}
                </InfoItem>
                <InfoItem label="Teléfono">
                    {{ company.phone }}
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
                    {{ company.environment }}
                </InfoItem>
            </div>
        </Card>
    </div>
</template>
