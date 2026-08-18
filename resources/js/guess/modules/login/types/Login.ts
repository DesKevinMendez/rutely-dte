import type { AuthUser } from '@/core/types/auth.types';

export interface LoginRequest {
    email: string;
    password: string;
    device_name: string;
}

export interface LoginResponse {
    data: {
        token: string;
        user: AuthUser;
    };
}
