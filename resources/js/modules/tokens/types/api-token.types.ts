export const API_TOKEN_ABILITY_OPTIONS = [
    { value: 'create:dte', label: 'Crear DTE' },
    { value: 'list:dte', label: 'Listar DTE' },
] as const;

export type ApiTokenAbility = (typeof API_TOKEN_ABILITY_OPTIONS)[number]['value'];

export interface ApiTokenApiRecord {
    id: number;
    name: string;
    abilities: ApiTokenAbility[];
    token_preview: string;
    last_used_at: string | null;
    created_at: string;
}

export interface ApiTokenStorePayload {
    name: string;
    abilities: ApiTokenAbility[];
}

export interface ApiTokenStoreResult {
    record: ApiTokenApiRecord;
    plain_text_token: string;
}
