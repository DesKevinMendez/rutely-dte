<script setup lang="ts">
import { useRouter } from 'vue-router';
import OnboardingForm from '@/guess/modules/onboarding/components/OnboardingForm.vue';
import useCompanyShow from '@/modules/superadmin/companies/composables/useCompanyShow';
import useUpdateSuperadminCompany from '@/modules/superadmin/companies/composables/useUpdateSuperadminCompany';
import type { CompanyPayload } from '@/modules/superadmin/companies/types/company.types';

const { company } = useCompanyShow();
const router = useRouter();
const {
    updateCompany: saveCompany,
    isLoading: isSaving,
    error,
} = useUpdateSuperadminCompany();

const updateCompany = async (payload: CompanyPayload): Promise<void> => {
    if (!company.value) {
        return;
    }

    const updatedCompany = await saveCompany(company.value.id, payload);

    if (!updatedCompany) {
        return;
    }

    company.value = updatedCompany;
    await router.push({
        name: 'superadmin-company-profile',
        params: { id: updatedCompany.id },
    });
};
</script>

<template>
    <OnboardingForm
        v-if="company"
        mode="superadmin-edit"
        :initial-company="company"
        :is-saving="isSaving"
        :submission-error="error"
        :cancel-to="{
            name: 'superadmin-company-profile',
            params: { id: company.id },
        }"
        @submit="updateCompany"
    />
</template>
