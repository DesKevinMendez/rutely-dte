import { ref } from 'vue';
import type { ComputedRef } from 'vue';
import { useRequest } from '@/core/composables/useRequest';
import type { ApiResponse } from '@/core/types/api.types';
import type {
    ApiTokenStorePayload,
    ApiTokenStoreResult,
} from '../types/api-token.types';
import type { ApiTokenAbility } from '../types/api-token.types';

export function useApiTokensUi(tokensUrl: ComputedRef<string>) {
    const generatedToken = ref<string | null>(null);
    const error = ref<string | null>(null);
    const { post, isLoading } = useRequest();

    const createToken = async (
        name: string,
        abilities: ApiTokenAbility[],
    ): Promise<boolean> => {
        const payload: ApiTokenStorePayload = {
            name: name.trim(),
            abilities,
        };
        const response = await post<
            ApiResponse<ApiTokenStoreResult>,
            ApiTokenStorePayload
        >(tokensUrl.value, payload);

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
