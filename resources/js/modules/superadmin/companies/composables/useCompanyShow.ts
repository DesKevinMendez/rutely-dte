import { ref } from 'vue';
import { useRequest } from '@/core/composables/useRequest';
import type {
    Company,
    CompanyResponse,
} from '@/modules/superadmin/companies/types/company.types';

const company = ref<Company | null>(null);
const isLoading = ref(false);
const error = ref<string | null>(null);
let latestRequestId = 0;

export default function useCompanyShow() {
    const { get } = useRequest();

    const loadCompany = async (companyId: string): Promise<void> => {
        const requestId = ++latestRequestId;

        company.value = null;
        error.value = null;
        isLoading.value = true;

        try {
            const response = await get<CompanyResponse>(
                `/api/v1/superadmin/companies/${encodeURIComponent(companyId)}`,
            );

            if (requestId !== latestRequestId) {
                return;
            }

            company.value = response.data.value?.data ?? null;
            error.value = response.error.value;

            if (!company.value && !error.value) {
                error.value = 'No se encontró la empresa.';
            }
        } finally {
            if (requestId === latestRequestId) {
                isLoading.value = false;
            }
        }
    };

    return {
        company,
        isLoading,
        error,
        loadCompany,
    };
}
