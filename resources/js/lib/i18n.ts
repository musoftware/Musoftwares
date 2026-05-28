/**
 * Frontend translation helper.
 *
 * For now, this is a passthrough function that returns the key as-is.
 * When a full i18n system (e.g., laravel-vue-i18n or a custom Inertia
 * shared-prop translator) is integrated, this function will be replaced
 * with actual locale-aware lookups.
 *
 * Usage:
 *   import { __ } from '@/lib/i18n';
 *   <button>{__('Save Changes')}</button>
 */
export function __(key: string, replacements?: Record<string, string | number>): string {
    let result = key;

    if (replacements) {
        Object.entries(replacements).forEach(([k, v]) => {
            result = result.replace(`:${k}`, String(v));
        });
    }

    return result;
}
