import type { ApiResponse } from '@/core/types/api.types';

export interface CompanyUser {
    id: string;
    company_id: string;
    name: string;
    email: string;
    phone: string | null;
    status: boolean;
}

export interface CompanyUserPayload {
    name: string;
    email: string;
    phone?: string;
}

export type CompanyUserResponse = ApiResponse<CompanyUser>;
