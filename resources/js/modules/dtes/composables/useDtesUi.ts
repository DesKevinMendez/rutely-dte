import { computed, ref, watch } from 'vue';
import type { DteDraft, DteRecord } from '../types/dte.types';

const mockRecords: DteRecord[] = [
    {
        id: '1',
        codigoGeneracion: '0A3E4D62-5B19-4B8F-8C28-001284A0DTE1',
        numeroControl: 'DTE-01-M001P001-000000000000184',
        tipoDte: '01',
        receptorNombre: 'Cliente General',
        receptorDocumento: '0614-280390-112-1',
        montoTotal: 125.5,
        estado: 'PROCESADO',
        createdAt: '2026-08-18T15:32:00-06:00',
    },
    {
        id: '2',
        codigoGeneracion: '5D6CA7C1-100B-4A0C-A5A1-001284A0DTE2',
        numeroControl: 'DTE-03-M001P001-000000000000183',
        tipoDte: '03',
        receptorNombre: 'Servicios Centroamericanos, S.A. de C.V.',
        receptorDocumento: '0614-110221-101-7',
        montoTotal: 847.5,
        estado: 'PROCESADO',
        createdAt: '2026-08-18T14:17:00-06:00',
    },
    {
        id: '3',
        codigoGeneracion: '9BDB60B8-A8E6-4C19-97C8-001284A0DTE3',
        numeroControl: 'DTE-01-M001P001-000000000000182',
        tipoDte: '01',
        receptorNombre: 'Consumidor Final',
        receptorDocumento: 'N/A',
        montoTotal: 38.25,
        estado: 'CONTINGENCIA',
        createdAt: '2026-08-18T13:48:00-06:00',
    },
    {
        id: '4',
        codigoGeneracion: 'C66ECF67-B5D7-47C6-909C-001284A0DTE4',
        numeroControl: 'DTE-05-M001P001-000000000000019',
        tipoDte: '05',
        receptorNombre: 'Distribuidora Cuscatlán',
        receptorDocumento: '0614-090518-102-4',
        montoTotal: 92,
        estado: 'INVALIDADO',
        createdAt: '2026-08-17T16:25:00-06:00',
    },
    {
        id: '5',
        codigoGeneracion: 'B2FE0C09-FE9D-47BE-878D-001284A0DTE5',
        numeroControl: 'DTE-14-M001P001-000000000000011',
        tipoDte: '14',
        receptorNombre: 'Proveedor Independiente',
        receptorDocumento: '04876543-2',
        montoTotal: 310,
        estado: 'RECHAZADO',
        createdAt: '2026-08-17T11:04:00-06:00',
    },
];

export function useDtesUi() {
    const records = ref<DteRecord[]>([...mockRecords]);
    const filterTipoDte = ref('');
    const filterEstado = ref('');
    const searchQuery = ref('');
    const currentPage = ref(1);
    const perPage = ref(10);

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

    const addRecord = (draft: DteDraft): void => {
        const sequence = String(records.value.length + 185).padStart(15, '0');
        const id = String(Date.now());

        records.value.unshift({
            id,
            codigoGeneracion: `DEMO-${id.slice(-8)}-RUTELY-DTE`,
            numeroControl: `DTE-${draft.tipoDte}-M001P001-${sequence}`,
            tipoDte: draft.tipoDte,
            receptorNombre: draft.receptorNombre || 'Cliente General',
            receptorDocumento: draft.receptorDocumento || 'N/A',
            montoTotal: draft.montoTotal,
            estado: 'PROCESADO',
            createdAt: new Date().toISOString(),
        });
    };

    const goToPage = (page: number): void => {
        currentPage.value = Math.min(Math.max(page, 1), totalPages.value);
    };

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
        addRecord,
        goToPage,
    };
}
