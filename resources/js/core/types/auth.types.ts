export interface AuthUser {
    id: string;
    company_id: string | null;
    role: string;
    phone: string | null;
    name: string;
    email: string;
    email_verified_at: string | null;
    created_at: string;
    updated_at: string;
}
