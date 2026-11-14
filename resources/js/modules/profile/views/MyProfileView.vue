<script setup lang="ts">
import { Card, InfoItem } from 'ornito';
import { formatIdentityDocument } from '@/core/composables/formatIdentityDocument';
import { formatSalvadoranPhone } from '@/core/composables/formatSalvadoranPhone';
import ProfileHeaderCard from '../components/ProfileHeaderCard.vue';
import { useProfileUi } from '../composables/useProfileUi';

const { profile, isAdmin, company } = useProfileUi();
</script>

<template>
    <div class="space-y-6">
        <div>
            <h1
                class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white"
            >
                Mi Perfil
            </h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                Información de tu cuenta de administrador
            </p>
        </div>

        <ProfileHeaderCard :profile="profile" />

        <Card title="Información de Contacto">
            <div class="divide-y divide-gray-200 dark:divide-gray-800">
                <div class="flex items-center justify-between gap-4 py-3">
                    <span class="text-sm text-gray-500 dark:text-gray-400"
                        >Correo Electrónico</span
                    >
                    <span
                        class="text-right text-sm font-medium text-gray-900 dark:text-white"
                        >{{ profile.email }}</span
                    >
                </div>
                <div class="flex items-center justify-between gap-4 py-3">
                    <span class="text-sm text-gray-500 dark:text-gray-400"
                        >Teléfono</span
                    >
                    <span
                        class="text-right text-sm font-medium text-gray-900 dark:text-white"
                        >{{ profile.phone }}</span
                    >
                </div>
            </div>
        </Card>

        <Card v-if="isAdmin" title="Datos de empresa">
            <div v-if="company" class="grid gap-x-4 gap-y-4 sm:grid-cols-2">
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
            <p v-else class="text-sm text-gray-500 dark:text-gray-400">
                Aún no tienes una empresa asociada.
            </p>
        </Card>
    </div>
</template>
