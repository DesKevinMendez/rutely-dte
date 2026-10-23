import type { ApiResponse } from '@/core/types/api.types';

export interface Company {
    id: string;
    name: string;
    address: string;
    phone: string;
    nit: string;
    nrc: string | null;
    commercial_name: string;
    economic_activity_code: string;
    establishment_type: string;
    departament_id: string;
    municipality_id: string;
    district_id: string | null;
    email: string;
    mh_establishment_code: string;
    mh_pos_code: string;
    own_establishment_code: string;
    own_pos_code: string;
    is_onboarded: boolean | number | string;
    environment: string;
    created_at: string;
    updated_at: string;
}

export interface CompanyPayload {
    name: string;
    address: string;
    phone: string;
    nit: string;
    nrc: string | null;
    commercial_name: string;
    economic_activity_code: string;
    establishment_type: string;
    departament_id: string;
    municipality_id: string;
    district_id: string | null;
    email: string;
    mh_establishment_code: string;
    mh_pos_code: string;
    own_establishment_code: string;
    own_pos_code: string;
}

export type CompanyResponse = ApiResponse<Company>;
