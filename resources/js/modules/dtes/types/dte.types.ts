export type DteStatus =
    | 'BORRADOR'
    | 'FIRMADO'
    | 'RECIBIDO'
    | 'PROCESADO'
    | 'RECHAZADO'
    | 'DESCONOCIDO'
    | 'CONTINGENCIA'
    | 'INVALIDADO';

export interface MhResponse {
    version?: number | null;
    ambiente?: string | null;
    versionApp?: number | null;
    estado?: string | null;
    codigoGeneracion?: string | null;
    selloRecibido?: string | null;
    fhProcesamiento?: string | null;
    clasificaMsg?: string | null;
    codigoMsg?: string | null;
    descripcionMsg?: string | null;
    observaciones?: string[] | null;
    [key: string]: unknown;
}

export interface DteDocumentItem {
    numItem: number;
    descripcion: string;
    codigo?: string | null;
    cantidad: number;
    precioUni: number;
    montoDescu?: number;
    ventaGravada?: number;
    ivaItem?: number;
    [key: string]: unknown;
}

export interface DteApiRecord {
    id: string;
    company_id: string;
    generation_code: string;
    control_number: string;
    dte_type: string;
    version: string;
    environment: string;
    status: DteStatus;
    issuer_nit: string;
    receiver_document: string | null;
    total_amount: number;
    original_json: {
        identificacion?: {
            fecEmi?: string | null;
            horEmi?: string | null;
        } | null;
        receptor?: {
            nombre?: string | null;
            tipoDocumento?: string | null;
            numDocumento?: string | null;
        } | null;
        cuerpoDocumento?: DteDocumentItem[] | null;
        [key: string]: unknown;
    } | null;
    signed_json: string | null;
    received_seal: string | null;
    pdf_url: string | null;
    observations: string | null;
    mh_response_json: MhResponse | null;
    mh_transmission?: {
        attempt: number;
        http_status: number | null;
        status: string;
        response_json: MhResponse | null;
    };
    receiver_id: string | null;
    contingency_event_id: string | null;
    created_at: string;
    updated_at: string;
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
    receptor?: {
        tipoDocumento: string | null;
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
    } | null;
}
