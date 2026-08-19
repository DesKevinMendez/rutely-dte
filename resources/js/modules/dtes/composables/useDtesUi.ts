import { computed, ref, watch } from 'vue';
import { useRequest } from '@/core/composables/useRequest';
import type { ApiResponse, PaginatedApiResponse } from '@/core/types/api.types';
import type {
    DteApiRecord,
    DteDraft,
    DteRecord,
    DteStorePayload,
    DteStoreResult,
} from '../types/dte.types';

const mapRecord = (record: DteApiRecord): DteRecord => ({
    id: record.id,
    codigoGeneracion: record.generation_code,
    numeroControl: record.control_number,
    tipoDte: record.dte_type,
    receptorNombre:
        record.original_json?.receptor?.nombre?.trim() || 'Cliente General',
    receptorDocumento:
        record.receiver_document ||
        record.original_json?.receptor?.numDocumento ||
        'N/A',
    montoTotal: record.total_amount / 100,
    estado: record.status,
    createdAt: record.created_at,
});

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

export async function useDtesUi() {
    const records = ref<DteRecord[]>([]);
    const filterTipoDte = ref('');
    const filterEstado = ref('');
    const searchQuery = ref('');
    const currentPage = ref(1);
    const perPage = ref(10);
    const error = ref<string | null>(null);
    const { get, post, isLoading } = useRequest();

    const filteredRecords = computed(() => {
        const query = searchQuery.value.trim().toLowerCase();

        return records.value.filter((record) => {
            const matchesType =
                !filterTipoDte.value || record.tipoDte === filterTipoDte.value;
            const matchesStatus =
                !filterEstado.value || record.estado === filterEstado.value;
            const matchesQuery =
                !query ||
                [
                    record.codigoGeneracion,
                    record.numeroControl,
                    record.receptorNombre,
                    record.receptorDocumento,
                ].some((value) => value.toLowerCase().includes(query));

            return matchesType && matchesStatus && matchesQuery;
        });
    });

    const totalPages = computed(() =>
        Math.max(1, Math.ceil(filteredRecords.value.length / perPage.value)),
    );
    const paginatedRecords = computed(() => {
        const start = (currentPage.value - 1) * perPage.value;

        return filteredRecords.value.slice(start, start + perPage.value);
    });

    watch([filterTipoDte, filterEstado, searchQuery, perPage], () => {
        currentPage.value = 1;
    });

    const loadDtes = async (): Promise<boolean> => {
        const response = await get<PaginatedApiResponse<DteApiRecord>>(
            '/api/v1/dtes?per_page=100',
        );

        if (response.data.value) {
            records.value = response.data.value.data.map(mapRecord);
            error.value = null;

            return true;
        }

        error.value = response.error.value ?? 'No se pudieron cargar los DTEs.';

        return false;
    };

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
        await loadDtes();

        return true;
    };

    const goToPage = (page: number): void => {
        currentPage.value = Math.min(Math.max(page, 1), totalPages.value);
    };

    await loadDtes();

    return {
        records,
        filterTipoDte,
        filterEstado,
        searchQuery,
        currentPage,
        perPage,
        filteredRecords,
        paginatedRecords,
        totalPages,
        isLoading,
        error,
        refreshDtes: loadDtes,
        createDte,
        goToPage,
    };
}
