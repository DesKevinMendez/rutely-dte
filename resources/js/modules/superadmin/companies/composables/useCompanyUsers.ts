import { useMessages } from 'ornito';
import { computed, ref } from 'vue';
import type { ComputedRef } from 'vue';
import { useRequest } from '@/core/composables/useRequest';
import type {
    CompanyUser,
    CompanyUserPayload,
    CompanyUserResponse,
    CompanyUsersResponse,
} from '@/modules/superadmin/companies/types/company-user.types';

export default function useCompanyUsers(companyId: ComputedRef<string>) {
    const { pushMessage } = useMessages();
    const { get, post } = useRequest();
    const users = ref<CompanyUser[]>([]);
    const isLoading = ref(false);
    const error = ref<string | null>(null);
    const isFormModalOpen = ref(false);
    const isSaving = ref(false);
    const formError = ref<string | null>(null);
    const usersUrl = computed(
        () =>
            `/api/v1/superadmin/companies/${encodeURIComponent(companyId.value)}/users`,
    );
    let latestRequestId = 0;

    const loadUsers = async (): Promise<void> => {
        if (!companyId.value) {
            return;
        }

        const requestId = ++latestRequestId;
        isLoading.value = true;
        error.value = null;

        try {
            const response = await get<CompanyUsersResponse>(
                `${usersUrl.value}?per_page=100`,
            );

            if (requestId !== latestRequestId) {
                return;
            }

            users.value = response.data.value?.data ?? [];
            error.value = response.error.value;
        } finally {
            if (requestId === latestRequestId) {
                isLoading.value = false;
            }
        }
    };

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
            const response = await post<CompanyUserResponse, CompanyUserPayload>(
                usersUrl.value,
                payload,
            );

            if (response.statusCode.value !== 201 || !response.data.value?.data) {
                formError.value =
                    response.error.value ?? 'No se pudo crear el usuario.';

                return;
            }

            pushMessage('Usuario creado correctamente.');
            isFormModalOpen.value = false;
            await loadUsers();
        } finally {
            isSaving.value = false;
        }
    };

    return {
        users,
        isLoading,
        error,
        isFormModalOpen,
        isSaving,
        formError,
        loadUsers,
        openCreateModal,
        closeFormModal,
        saveUser,
    };
}
