import { computed, ref, watch } from 'vue';
import type { Ref } from 'vue';
import { useRequest } from '@/core/composables/useRequest';

type CorrelativeResponse = { data: { year: number; next_value: number } };

export default function useDteCorrelative(companyId: Readonly<Ref<string>>) {
    const { get } = useRequest();
    const { makeRequest } = useRequest();
    const nextValue = ref<number | null>(null);
    const fiscalYear = ref<number | null>(null);
    const error = ref<string | null>(null);
    const saved = ref(false);
    const isLoading = ref(false);
    const isSaving = ref(false);
    const url = computed(
        () =>
            `/api/v1/superadmin/companies/${encodeURIComponent(companyId.value)}/dte-correlative`,
    );
    let latestRequestId = 0;

    watch(
        companyId,
        async (id) => {
            const requestId = ++latestRequestId;
            nextValue.value = null;
            fiscalYear.value = null;
            error.value = null;
            saved.value = false;

            if (!id) {
                isLoading.value = false;

                return;
            }

            isLoading.value = true;

            try {
                const response = await get<CorrelativeResponse>(url.value);

                if (requestId !== latestRequestId) {
                    return;
                }

                nextValue.value = response.data.value?.data.next_value ?? null;
                fiscalYear.value = response.data.value?.data.year ?? null;
                error.value = response.error.value;
            } finally {
                if (requestId === latestRequestId) {
                    isLoading.value = false;
                }
            }
        },
        { immediate: true },
    );

    const saveCorrelative = async (): Promise<void> => {
        if (!companyId.value || nextValue.value === null || isSaving.value) {
            return;
        }

        const currentCompanyId = companyId.value;
        isSaving.value = true;
        error.value = null;
        saved.value = false;

        try {
            const response = await makeRequest<CorrelativeResponse>(url.value, {
                method: 'PATCH',
                body: { next_value: nextValue.value },
            });

            if (companyId.value !== currentCompanyId) {
                return;
            }

            error.value = response.error.value;

            if (response.data.value) {
                nextValue.value = response.data.value.data.next_value;
                fiscalYear.value = response.data.value.data.year;
                saved.value = true;
            }
        } finally {
            isSaving.value = false;
        }
    };

    return {
        nextValue,
        fiscalYear,
        error,
        saved,
        isLoading,
        isSaving,
        saveCorrelative,
    };
}
