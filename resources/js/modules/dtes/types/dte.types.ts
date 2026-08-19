export type DteStatus =
    'PROCESADO' | 'RECHAZADO' | 'CONTINGENCIA' | 'INVALIDADO';

export interface DteRecord {
    id: string;
    codigoGeneracion: string;
    numeroControl: string;
    tipoDte: string;
    receptorNombre: string;
    receptorDocumento: string;
    montoTotal: number;
    estado: DteStatus;
    createdAt: string;
}

export interface DteItem {
    descripcion: string;
    cantidad: number;
    precioUni: number;
}

export interface DteDraft {
    tipoDte: string;
    receptorNombre: string;
    receptorDocumento: string;
    receptorCorreo: string;
    items: DteItem[];
    montoTotal: number;
}
