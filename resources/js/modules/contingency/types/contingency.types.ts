export type CircuitState = 'CLOSED' | 'OPEN';

export interface ContingencyStatus {
    active: boolean;
    circuitState: CircuitState;
}
