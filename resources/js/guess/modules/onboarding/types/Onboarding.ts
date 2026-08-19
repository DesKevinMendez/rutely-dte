import type { AuthUser } from '@/core/types/auth.types';

export interface OnboardingForm {
    userName: string;
    userEmail: string;
    password: string;
    passwordConfirmation: string;
    name: string;
    commercialName: string;
    nit: string;
    nrc: string;
    phone: string;
    email: string;
    address: string;
    economicActivityCode: string;
    establishmentType: string;
    departmentId: string;
    municipalityId: string;
    districtId: string;
    ownEstablishmentCode: string;
    ownPosCode: string;
}

export interface RegisterRequest {
    name: string;
    email: string;
    phone: string | null;
    password: string;
    password_confirmation: string;
}

export interface RegisterResponse {
    data: {
        token: string;
        user: AuthUser;
    };
}

export interface CompanyRequest {
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

export interface CompanyResponse {
    data: {
        id: string;
    };
}
