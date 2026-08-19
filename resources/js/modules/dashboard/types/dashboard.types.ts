export interface DashboardApiMetrics {
    total: number;
    processed: number;
    rejected: number;
    invalidated: number;
    total_amount: number;
    pending_transmissions: number;
}

export interface DashboardRecentDte {
    id: string;
    generation_code: string;
    control_number: string;
    dte_type: string;
    status: string;
    total_amount: number;
    created_at: string;
}

export interface DashboardApiData {
    metrics: DashboardApiMetrics;
    recent_dtes: DashboardRecentDte[];
}

export interface DteDashboardMetrics {
    totalEmitidos: number;
    totalMonto: number;
    procesadosCount: number;
    rechazadosCount: number;
    invalidatedCount: number;
    pendingTransmissionsCount: number;
}
