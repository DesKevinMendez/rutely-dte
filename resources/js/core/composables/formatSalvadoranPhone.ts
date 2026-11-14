/** Display Salvadoran phone numbers in the local, readable format. */
export const formatSalvadoranPhone = (
    phone: string | null | undefined,
): string => {
    const value = phone?.trim() ?? '';

    if (!value) {
        return '';
    }

    const digits = value.replace(/\D/g, '');
    const localNumber = digits.startsWith('503') ? digits.slice(3) : digits;

    if (localNumber.length !== 8) {
        return value;
    }

    return `+503 ${localNumber.slice(0, 4)} ${localNumber.slice(4)}`;
};
