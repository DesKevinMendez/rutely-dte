/**
 * NIT and DUI are stored and sent to Hacienda as digits only. People read them with hyphens.
 */
export const formatIdentityDocument = (document: string): string =>
    document
        .replace(/^(\d{4})(\d{6})(\d{3})(\d)$/, '$1-$2-$3-$4')
        .replace(/^(\d{8})(\d)$/, '$1-$2');
