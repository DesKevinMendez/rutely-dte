export type DteStatus =
    | 'BORRADOR'
    | 'FIRMADO'
    | 'PROCESADO'
    | 'RECHAZADO'
    | 'CONTINGENCIA'
    | 'INVALIDADO';

export interface DteApiRecord {
    id: string;
    generation_code: string;
    control_number: string;
    dte_type: string;
    status: DteStatus;
    receiver_document: string | null;
    total_amount: number;
    original_json: {
        receptor?: {
            nombre?: string | null;
            numDocumento?: string | null;
        } | null;
    } | null;
    created_at: string;
}

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
    tipoDocumento: string;
    receptorNombre: string;
    receptorDocumento: string;
    receptorCorreo: string;
    items: DteItem[];
    montoTotal: number;
}

export interface DteStorePayload {
    tipoDte: string;
    receptor: {
        tipoDocumento: string;
        numDocumento: string | null;
        nombre: string | null;
        correo: string | null;
    };
    items: Array<{
        descripcion: string;
        cantidad: number;
        precioUni: number;
    }>;
}

export interface DteStoreResult {
    record: DteApiRecord;
    mh_result: {
        estado?: string;
        selloRecibido?: string;
        observaciones?: string[];
    };
}
