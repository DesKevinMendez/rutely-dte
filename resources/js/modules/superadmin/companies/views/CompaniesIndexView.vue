<script setup lang="ts">
import { IconEye, IconPlus } from '@tabler/icons-vue';
import {
    BaseButton,
    Card,
    DataTable,
    IconButton,
    ViewHeader,
} from 'ornito';
import type { TableField } from 'ornito';
import { useRouter } from 'vue-router';
import type { Company } from '@/modules/superadmin/companies/types/company.types';

const router = useRouter();

const createCompany = async (): Promise<void> => {
    await router.push({ name: 'superadmin-company-create' });
};

const columns: TableField<Company>[] = [
    { label: 'Empresa', key: 'name' },
    { label: 'Nombre comercial', key: 'commercial_name' },
    { label: 'NIT', key: 'nit' },
    { label: 'Correo', key: 'email' },
    { label: 'Teléfono', key: 'phone' },
];
</script>

<template>
    <div class="space-y-6">
        <ViewHeader
            title="Empresas"
            subtitle="Consulta las empresas registradas y su estado de configuración."
        >
            <template #right>
                <BaseButton
                    variant="primary"
                    size="auto"
                    :icon="IconPlus"
                    icon-position="left"
                    @click="createCompany"
                >
                    Nueva empresa
                </BaseButton>
            </template>
        </ViewHeader>

        <Card>
            <DataTable
                url="/api/v1/superadmin/companies"
                :columns="columns"
                search-by="filter[name]"
                search-placeholder="Buscar empresa..."
                actions-label="Opciones"
            >
                <template #actions="{ row }">
                    <IconButton
                        :icon="IconEye"
                        :to="{
                            name: 'superadmin-company-profile',
                            params: { id: row.id },
                        }"
                        color="primary"
                        rounded
                        outline
                        aria-label="Ver perfil de la empresa"
                    />
                </template>
            </DataTable>
        </Card>
    </div>
</template>
