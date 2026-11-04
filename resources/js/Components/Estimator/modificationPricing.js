import { LifeBuoy, Bug, PencilRuler, FilePlus2, Puzzle, Plug, Zap, ShieldCheck, ServerCog, Type, Clock } from 'lucide-react';

/** Hourly rate (USD) for work that does not fit one of the fixed items. */
export const HOURLY_RATE_USD = 10;

/**
 * Prices (USD) for changes to a project the client already has.
 * `per`: 'item' = priced per unit (bug, screen, feature), 'once' = one fixed job.
 * `days`: work days per unit, used for the timeline.
 */
export const CHANGE_TYPES = [
    { id: 'rescue', per: 'once', price: 60, days: 2, icon: LifeBuoy },
    { id: 'fix_bug', per: 'item', price: 15, days: 0.5, icon: Bug },
    { id: 'edit_screen', per: 'item', price: 12, days: 0.5, icon: PencilRuler },
    { id: 'new_screen', per: 'item', price: 20, days: 1, icon: FilePlus2 },
    { id: 'new_feature', per: 'item', price: 40, days: 2, icon: Puzzle },
    { id: 'integration', per: 'item', price: 35, days: 1.5, icon: Plug },
    { id: 'content', per: 'item', price: 5, days: 0.2, icon: Type },
    { id: 'speed', per: 'once', price: 30, days: 1, icon: Zap },
    { id: 'security', per: 'once', price: 35, days: 1, icon: ShieldCheck },
    { id: 'server_move', per: 'once', price: 35, days: 1, icon: ServerCog },
    { id: 'custom_hours', per: 'item', price: HOURLY_RATE_USD, days: 1 / 8, icon: Clock },
];

/** One-time fee to read and set up an unknown codebase before any change. */
export const CODE_REVIEW_FEE = 20;
export const MIN_TOTAL_USD = 25;
export const MAX_ITEMS = 50;

export const SYSTEM_TYPES = ['web', 'landing', 'mobile', 'desktop'];
export const TECH_STACKS = ['wordpress', 'laravel', 'react', 'flutter', 'dotnet', 'unknown'];
export const CODE_ACCESS = ['yes', 'no', 'unsure'];

/** Show a USD amount as USD or converted to EGP. */
export function formatMoney(usdAmount, isUsd, exchangeRate) {
    if (isUsd) {
        return `$${usdAmount.toLocaleString()}`;
    }
    return `${Math.round(usdAmount * exchangeRate).toLocaleString()} EGP`;
}

export const clampQty =(value) => Math.max(0, Math.min(MAX_ITEMS, parseInt(value, 10) || 0));

/**
 * Pure: turn { changeId: qty } into the line items, total and timeline.
 */
export function calculateModification(quantities, exchangeRate) {
    const items = CHANGE_TYPES
        .filter((type) => (quantities[type.id] || 0) > 0)
        .map((type) => {
            const qty = quantities[type.id];
            return { id: type.id, per: type.per, qty, subtotal: type.price * qty, days: type.days * qty };
        });

    const changesUsd = items.reduce((sum, item) => sum + item.subtotal, 0);
    const totalUsd = items.length === 0 ? 0 : Math.max(MIN_TOTAL_USD, changesUsd + CODE_REVIEW_FEE);
    const workDays = items.reduce((sum, item) => sum + item.days, 0);

    return {
        items,
        totalUsd,
        totalEgp: Math.round(totalUsd * exchangeRate),
        estimatedDays: items.length === 0 ? 0 : Math.max(1, Math.ceil(1 + workDays)),
    };
}
