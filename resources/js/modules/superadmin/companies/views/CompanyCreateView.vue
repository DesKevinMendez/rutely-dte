<script setup lang="ts">
import { Breadcrumb, ViewHeader } from 'ornito';
import OnboardingForm from '@/guess/modules/onboarding/components/OnboardingForm.vue';

const breadcrumbs = [
    { label: 'Empresas', to: '/superadmin/companies' },
    { label: 'Nueva empresa' },
];
</script>

<template>
    <div class="w-full">
        <Breadcrumb :items="breadcrumbs" />

        <div class="space-y-6">
            <ViewHeader
                title="Nueva empresa"
                subtitle="Completá los datos fiscales y comerciales para registrar la empresa."
            />

            <OnboardingForm mode="superadmin" />
        </div>
    </div>
</template>
