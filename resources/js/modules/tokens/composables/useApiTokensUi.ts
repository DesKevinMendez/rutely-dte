import { ref } from 'vue';
import { useRequest } from '@/core/composables/useRequest';
import type { ApiResponse, PaginatedApiResponse } from '@/core/types/api.types';
import type {
    ApiTokenApiRecord,
    ApiTokenStorePayload,
    ApiTokenStoreResult,
} from '../types/api-token.types';

export async function useApiTokensUi() {
    const tokens = ref<ApiTokenApiRecord[]>([]);
    const generatedToken = ref<string | null>(null);
    const error = ref<string | null>(null);
    const { get, post, isLoading } = useRequest();

    const loadTokens = async (): Promise<boolean> => {
        const response = await get<PaginatedApiResponse<ApiTokenApiRecord>>(
            '/api/v1/tokens?per_page=100',
        );

        if (!response.data.value) {
            error.value =
                response.error.value ?? 'No se pudieron cargar los tokens.';

            return false;
        }

        tokens.value = response.data.value.data;
        error.value = null;

        return true;
    };

    const createToken = async (name: string): Promise<boolean> => {
        const payload: ApiTokenStorePayload = {
            name: name.trim(),
        };
        const response = await post<
            ApiResponse<ApiTokenStoreResult>,
            ApiTokenStorePayload
        >('/api/v1/tokens', payload);

        if (!response.data.value) {
            error.value =
                response.error.value ?? 'No se pudo crear el token de API.';

            return false;
        }

        tokens.value = [response.data.value.data.record, ...tokens.value];
        generatedToken.value = response.data.value.data.plain_text_token;
        error.value = null;

        return true;
    };

    const clearGeneratedToken = (): void => {
        generatedToken.value = null;
    };

    await loadTokens();

    return {
        tokens,
        generatedToken,
        isLoading,
        error,
        createToken,
        clearGeneratedToken,
    };
}
