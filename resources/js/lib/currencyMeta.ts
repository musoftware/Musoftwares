export const FLAGS_BY_CODE: Record<string, string> = {
    EGP: '🇪🇬',
    USD: '🇺🇸',
    SAR: '🇸🇦',
    EUR: '🇪🇺',
    GBP: '🇬🇧',
    AED: '🇦🇪',
    MAD: '🇲🇦',
    IQD: '🇮🇶',
};

export interface CurrencyMeta {
    code: string;
    flag: string;
}

export function getCurrencyMeta(code?: string | null): CurrencyMeta {
    const normalizedCode = code?.trim().toUpperCase() ?? '';

    return {
        code: normalizedCode || '—',
        flag: FLAGS_BY_CODE[normalizedCode] ?? '🌐',
    };
}

export const CURRENCY_SECTIONS: { code: string; flag: string }[] = Object.entries(FLAGS_BY_CODE).map(
    ([code, flag]) => ({ code, flag }),
);
