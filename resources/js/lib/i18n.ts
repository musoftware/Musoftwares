import translationsData from '../translations.json';

const translations: Record<string, any> = (translationsData && typeof translationsData === 'object') ? translationsData : {};

function getNestedValue(obj: any, path: string): any {
    if (!obj || typeof obj !== 'object') return undefined;
    return path.split('.').reduce((acc, part) => (acc && typeof acc === 'object' ? acc[part] : undefined), obj);
}

/**
 * Synchronize html element attributes (lang & dir) with the application locale.
 */
export function syncDocumentDirection(locale?: string): void {
    if (typeof document === 'undefined') return;
    const currentLocale = locale || 'en';
    document.documentElement.lang = currentLocale;
    document.documentElement.dir = currentLocale === 'ar' ? 'rtl' : 'ltr';
}

/**
 * Frontend translation helper.
 *
 * This reads from translations.json which is generated during build time
 * via `php artisan translations:export`.
 */
export function __(key: string, replacements?: Record<string, string | number>, fallback?: string, overrideLocale?: string): string {
    if (!key || typeof key !== 'string') return '';

    const locale = overrideLocale || (typeof document !== 'undefined' ? document.documentElement.lang || 'en' : 'en');
    const localeTranslations = (translations && typeof translations === 'object' && translations[locale]) ? translations[locale] : (translations?.['en'] || {});

    let result = getNestedValue(localeTranslations, key);

    if (result === undefined) {
        // Check if it's a flat key with dots from the root JSON file
        if (localeTranslations && localeTranslations[key] !== undefined) {
            result = localeTranslations[key];
        } else {
            result = fallback || key; // Fallback to raw key or fallback
        }
    }

    if (replacements && typeof replacements === 'object' && typeof result === 'string') {
        Object.entries(replacements).forEach(([k, v]) => {
            result = result.replace(new RegExp(`:${k}`, 'gi'), String(v));
        });
    }

    return String(result);
}
