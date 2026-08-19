import { ref } from 'vue';
import { useRequest } from '@/core/composables/useRequest';
import type { ApiResponse } from '@/core/types/api.types';
import type {
    DteDraft,
    DteStorePayload,
    DteStoreResult,
} from '../types/dte.types';

const toStorePayload = (draft: DteDraft): DteStorePayload => ({
    tipoDte: draft.tipoDte,
    receptor: {
        tipoDocumento: draft.tipoDocumento,
        numDocumento: draft.receptorDocumento.trim() || null,
        nombre: draft.receptorNombre.trim() || null,
        correo: draft.receptorCorreo.trim() || null,
    },
    items: draft.items.map((item) => ({
        descripcion: item.descripcion,
        cantidad: Number(item.cantidad),
        precioUni: Number(item.precioUni),
    })),
});

export function useDtesUi() {
    const filterTipoDte = ref('');
    const filterEstado = ref('');
    const error = ref<string | null>(null);
    const { post, isLoading } = useRequest();

    const createDte = async (draft: DteDraft): Promise<boolean> => {
        const response = await post<
            ApiResponse<DteStoreResult>,
            DteStorePayload
        >('/api/v1/dtes', toStorePayload(draft));

        if (!response.data.value) {
            error.value = response.error.value ?? 'No se pudo emitir el DTE.';

            return false;
        }

        error.value = null;

        return true;
    };

    return {
        filterTipoDte,
        filterEstado,
        isLoading,
        error,
        createDte,
    };
}
