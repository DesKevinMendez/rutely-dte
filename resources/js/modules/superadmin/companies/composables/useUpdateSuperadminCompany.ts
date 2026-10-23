import { ref } from 'vue';
import { useRequest } from '@/core/composables/useRequest';
import type {
    Company,
    CompanyPayload,
    CompanyResponse,
} from '@/modules/superadmin/companies/types/company.types';

export default function useUpdateSuperadminCompany() {
    const { makeRequest } = useRequest();
    const isLoading = ref(false);
    const error = ref<string | null>(null);

    const updateCompany = async (
        companyId: string,
        payload: CompanyPayload,
    ): Promise<Company | null> => {
        if (isLoading.value) {
            return null;
        }

        isLoading.value = true;
        error.value = null;

        try {
            const response = await makeRequest<CompanyResponse, CompanyPayload>(
                `/api/v1/superadmin/companies/${encodeURIComponent(companyId)}`,
                { method: 'PATCH', body: payload },
            );
            const company = response.data.value?.data;

            if (response.statusCode.value !== 200 || !company) {
                error.value =
                    response.error.value ?? 'No se pudo actualizar la empresa.';

                return null;
            }

            return company;
        } finally {
            isLoading.value = false;
        }
    };

    return {
        updateCompany,
        isLoading,
        error,
    };
}
