export type ServerValidationErrors = Record<string, string>;

export type SetServerErrors = (
    errors: ServerValidationErrors,
    message: string | null,
) => void;

export type VeeValidateSetErrors = (
    errors: Record<string, string | string[] | undefined>,
) => void;
