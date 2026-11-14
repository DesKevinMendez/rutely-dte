import { useMessages, useTable } from 'ornito';
import { computed, ref } from 'vue';
import type { ComputedRef } from 'vue';
import { useRequest } from '@/core/composables/useRequest';
import type {
    CompanyUserPayload,
    CompanyUserResponse,
} from '@/modules/superadmin/companies/types/company-user.types';

export default function useCompanyUsers(companyId: ComputedRef<string>) {
    const { pushMessage } = useMessages();
    const { post } = useRequest();
    const { refreshTable } = useTable();
    const isFormModalOpen = ref(false);
    const isSaving = ref(false);
    const formError = ref<string | null>(null);
    const usersUrl = computed(
        () =>
            `/api/v1/superadmin/companies/${encodeURIComponent(companyId.value)}/users`,
    );

    const openCreateModal = (): void => {
        formError.value = null;
        isFormModalOpen.value = true;
    };

    const closeFormModal = (): void => {
        if (isSaving.value) {
            return;
        }

        isFormModalOpen.value = false;
    };

    const saveUser = async (payload: CompanyUserPayload): Promise<void> => {
        if (isSaving.value) {
            return;
        }

        isSaving.value = true;
        formError.value = null;

        try {
            const response = await post<
                CompanyUserResponse,
                CompanyUserPayload
            >(usersUrl.value, payload);

            if (
                response.statusCode.value !== 201 ||
                !response.data.value?.data
            ) {
                formError.value =
                    response.error.value ?? 'No se pudo crear el usuario.';

                return;
            }

            pushMessage('Usuario creado correctamente.');
            isFormModalOpen.value = false;
            refreshTable();
        } finally {
            isSaving.value = false;
        }
    };

    return {
        usersUrl,
        isFormModalOpen,
        isSaving,
        formError,
        openCreateModal,
        closeFormModal,
        saveUser,
    };
}
