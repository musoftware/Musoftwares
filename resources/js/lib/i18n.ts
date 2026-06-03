import translationsData from '../translations.json';

const translations: Record<string, any> = translationsData;

function getNestedValue(obj: any, path: string): any {
    return path.split('.').reduce((acc, part) => acc && acc[part], obj);
}

/**
 * Frontend translation helper.
 *
 * This reads from translations.json which is generated during build time
 * via `php artisan translations:export`.
 */
export function __(key: string, replacements?: Record<string, string | number>, fallback?: string): string {
    const locale = typeof document !== 'undefined' ? document.documentElement.lang || 'en' : 'en';
    const localeTranslations = translations[locale] || translations['en'] || {};

    let result = getNestedValue(localeTranslations, key);

    if (result === undefined) {
        // Check if it's a flat key with dots from the root JSON file
        if (localeTranslations[key] !== undefined) {
            result = localeTranslations[key];
        } else {
            result = fallback || key; // Fallback to raw key or fallback
        }
    }

    if (replacements && typeof result === 'string') {
        Object.entries(replacements).forEach(([k, v]) => {
            result = result.replace(new RegExp(`:${k}`, 'gi'), String(v));
        });
    }

    return String(result);
}
