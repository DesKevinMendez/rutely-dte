import { ref } from 'vue';
import { useRequest } from '@/core/composables/useRequest';
import type { ApiResponse } from '@/core/types/api.types';
import type {
    ApiTokenStorePayload,
    ApiTokenStoreResult,
} from '../types/api-token.types';

export function useApiTokensUi() {
    const generatedToken = ref<string | null>(null);
    const error = ref<string | null>(null);
    const { post, isLoading } = useRequest();

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

        generatedToken.value = response.data.value.data.plain_text_token;
        error.value = null;

        return true;
    };

    const clearGeneratedToken = (): void => {
        generatedToken.value = null;
    };

    return {
        generatedToken,
        isLoading,
        error,
        createToken,
        clearGeneratedToken,
    };
}
