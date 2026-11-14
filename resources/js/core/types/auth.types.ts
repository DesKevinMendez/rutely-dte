import type { ApiEnvironment } from './environment.types';

export interface AuthUser {
    id: string;
    company_id: string | null;
    company?: AuthCompany | null;
    roles: Array<{ name: string }>;
    phone: string | null;
    name: string;
    email: string;
    email_verified_at?: string | null;
    created_at: string;
    updated_at: string;
}

export interface AuthCompany {
    id: string;
    name: string;
    environment: ApiEnvironment;
}
