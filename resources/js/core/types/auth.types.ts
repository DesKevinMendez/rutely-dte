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
    commercial_name: string;
    nit: string;
    nrc: string | null;
    email: string;
    phone: string;
    address: string;
    economic_activity_code: string;
    establishment_type: string;
    departament_id: string;
    municipality_id: string;
    district_id: string | null;
    mh_establishment_code: string;
    mh_pos_code: string;
    own_establishment_code: string;
    own_pos_code: string;
    environment: ApiEnvironment;
}
