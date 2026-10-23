import type { AuthUser } from '@/core/types/auth.types';
import type { CompanyPayload } from '@/modules/superadmin/companies/types/company.types';

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

export type CompanyRequest = CompanyPayload;

export interface CompanyResponse {
    data: {
        id: string;
    };
}
