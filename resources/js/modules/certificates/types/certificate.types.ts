export type CertificateEnvironment = 'PRUEBAS' | 'PRODUCCION';
export type CertificateApiEnvironment = '00' | '01';

export interface CertificateApiMetadata {
    id: string;
    nit: string;
    environment: CertificateApiEnvironment;
    active: boolean;
    updated_at: string | null;
}

export interface CertificateMetadata {
    id: string;
    nit: string;
    environment: CertificateEnvironment;
    active: boolean;
    updatedAt: string | null;
}

export interface CertificateUploadPayload {
    file: File;
    password: string;
}

export interface CertificateStorePayload {
    environment: CertificateApiEnvironment;
    certificadoXml: string;
    passwordPri: string | null;
}
