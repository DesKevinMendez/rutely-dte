export interface ApiTokenApiRecord {
    id: number;
    name: string;
    last_used_at: string | null;
    created_at: string;
}

export interface ApiTokenStorePayload {
    name: string;
}

export interface ApiTokenStoreResult {
    record: ApiTokenApiRecord;
    plain_text_token: string;
}
