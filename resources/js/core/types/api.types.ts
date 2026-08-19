export interface ApiResponse<T> {
    data: T;
    message?: string;
}

export interface PaginationMeta {
    total: number;
    per_page: number;
    current_page: number;
    last_page: number;
    from: number | null;
    to: number | null;
}

export interface PaginatedApiResponse<T> {
    data: T[];
    pagination: PaginationMeta;
}
