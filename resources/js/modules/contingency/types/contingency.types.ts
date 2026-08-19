export type CircuitState = 'CLOSED' | 'MANUAL_OPEN';

export interface ContingencyStatus {
    active: boolean;
    circuitState: CircuitState;
}

export interface ContingencyApiStatus {
    contingency_active: boolean;
    circuit_state: CircuitState;
}

export interface ContingencyUpdatePayload {
    active: boolean;
}
