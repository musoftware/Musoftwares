export const KNOWN_CURRENCY_CODES = ['EGP', 'USD', 'SAR', 'EUR', 'GBP', 'AED', 'MAD', 'IQD'] as const;

export interface CurrencyMeta {
    code: string;
}

export function getCurrencyMeta(code?: string | null): CurrencyMeta {
    const normalizedCode = code?.trim().toUpperCase() ?? '';

    return {
        code: normalizedCode || '—',
    };
}

export const CURRENCY_SECTIONS: { code: string }[] = KNOWN_CURRENCY_CODES.map((code) => ({ code }));
