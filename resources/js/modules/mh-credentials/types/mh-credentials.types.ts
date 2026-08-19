export type MhEnvironment = 'PRUEBAS' | 'PRODUCCION';
export type MhApiEnvironment = '00' | '01';

export interface MhCredentialsApiMetadata {
    id: string;
    nit: string;
    environment: MhApiEnvironment;
    active: boolean;
    updated_at: string | null;
}

export interface MhCredentialsMetadata {
    id: string;
    nit: string;
    environment: MhEnvironment;
    active: boolean;
    updatedAt: string | null;
}

export interface MhCredentialsPayload {
    nit: string;
    password: string;
}

export interface MhCredentialsStorePayload {
    environment: MhApiEnvironment;
    nit: string;
    pwd: string;
}
