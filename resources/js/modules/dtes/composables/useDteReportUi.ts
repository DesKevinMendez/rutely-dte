import { ref } from 'vue';
import { useRequest } from '@/core/composables/useRequest';

const workbookContentType =
    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';

export function useDteReportUi() {
    const downloadError = ref<string | null>(null);
    const { makeRequest, isLoading: isDownloading } = useRequest();

    const downloadReport = async (filters: string): Promise<void> => {
        if (isDownloading.value) {
            return;
        }

        downloadError.value = null;

        const url = filters
            ? `/api/v1/dtes/export?${filters}`
            : '/api/v1/dtes/export';
        const response = await makeRequest<Blob>(url, {
            responseType: 'blob',
        });
        const file = response.data.value;

        if (!(file instanceof Blob) || file.type !== workbookContentType) {
            downloadError.value =
                Object.values(response.validationErrors.value)[0] ??
                (response.statusCode.value === 422
                    ? response.error.value
                    : null) ??
                'No se pudo descargar el reporte. Inténtalo de nuevo.';

            return;
        }

        const objectUrl = URL.createObjectURL(file);
        const params = new URLSearchParams(filters);
        const date = params.get('filter[date]');
        const from = params.get('filter[from]');
        const to = params.get('filter[to]');
        const period = from && to ? `${from}_${to}` : date;
        const link = document.createElement('a');
        link.href = objectUrl;
        link.download = period
            ? `Reporte-DTE-${period}.xlsx`
            : 'Reporte-DTE.xlsx';
        document.body.appendChild(link);
        link.click();
        link.remove();
        setTimeout(() => URL.revokeObjectURL(objectUrl), 1000);
    };

    return { downloadError, isDownloading, downloadReport };
}
