import { ref } from 'vue';
import { useRequest } from '@/core/composables/useRequest';
import type { ApiResponse } from '@/core/types/api.types';
import type {
    DteDraft,
    DteStorePayload,
    DteStoreResult,
} from '../types/dte.types';

const getTodayDate = (): string => {
    const dateParts = new Intl.DateTimeFormat('en-CA', {
        timeZone: 'America/El_Salvador',
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
    }).formatToParts(new Date());
    const parts = Object.fromEntries(
        dateParts.map(({ type, value }) => [type, value]),
    );

    return `${parts.year}-${parts.month}-${parts.day}`;
};

const toReceiver = (draft: DteDraft): DteStorePayload['receptor'] => {
    const numDocumento = draft.receptorDocumento.trim() || null;
    const nombre = draft.receptorNombre.trim() || null;
    const correo = draft.receptorCorreo.trim() || null;

    if (!numDocumento && !nombre && !correo) {
        return undefined;
    }

    return {
        tipoDocumento: numDocumento ? draft.tipoDocumento : null,
        numDocumento,
        nombre,
        correo,
    };
};

const toStorePayload = (draft: DteDraft): DteStorePayload => ({
    tipoDte: draft.tipoDte,
    receptor: toReceiver(draft),
    items: draft.items.map((item) => ({
        descripcion: item.descripcion,
        cantidad: Number(item.cantidad),
        precioUni: Number(item.precioUni),
    })),
});

export function useDtesUi() {
    const filterTipoDte = ref('');
    const filterEstado = ref('');
    const filterFecha = ref(getTodayDate());
    const { post, isLoading } = useRequest();

    const resetFilters = (): void => {
        filterTipoDte.value = '';
        filterEstado.value = '';
        filterFecha.value = getTodayDate();
    };

    const createDte = async (
        draft: DteDraft,
    ): Promise<{
        success: boolean;
        validationErrors: Record<string, string>;
        serverError: string | null;
    }> => {
        const response = await post<
            ApiResponse<DteStoreResult>,
            DteStorePayload
        >('/api/v1/dtes', toStorePayload(draft));

        if (!response.data.value) {
            const validationErrors = response.validationErrors.value;
            const isValidationResponse =
                Object.keys(validationErrors).length > 0 &&
                response.statusCode.value !== 400;

            return {
                success: false,
                validationErrors,
                serverError: isValidationResponse
                    ? null
                    : (response.error.value ?? 'No se pudo emitir el DTE.'),
            };
        }

        return { success: true, validationErrors: {}, serverError: null };
    };

    return {
        filterTipoDte,
        filterEstado,
        filterFecha,
        resetFilters,
        isLoading,
        createDte,
    };
}
