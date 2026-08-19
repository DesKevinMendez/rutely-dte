export type MhEnvironment = 'PRUEBAS' | 'PRODUCCION';

export interface MhCredentialsMetadata {
    nit: string;
    environment: MhEnvironment;
    active: boolean;
    updatedAt: string;
}

export interface MhCredentialsPayload {
    nit: string;
    password: string;
}
