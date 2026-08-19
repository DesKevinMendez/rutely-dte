export type CertificateEnvironment = 'PRUEBAS' | 'PRODUCCION';

export interface CertificateMetadata {
    nit: string;
    environment: CertificateEnvironment;
    active: boolean;
    updatedAt: string;
}

export interface CertificateUploadPayload {
    fileName: string;
    password: string;
}
