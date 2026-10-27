import React, { ReactNode, useSyncExternalStore } from 'react';
import { Eye, EyeOff } from 'lucide-react';
import { __ } from '@/lib/i18n';

/**
 * Money on admin pages can be hidden, so a screen can be shown or shared without the figures.
 * Hidden by default; the choice is kept in this browser and shared by every amount on the page.
 */
const KEY = 'admin_hide_amounts';
const EVENT = 'admin-hide-amounts';

function isHidden(): boolean {
    try {
        return localStorage.getItem(KEY) !== '0';
    } catch {
        return true;
    }
}

function subscribe(callback: () => void): () => void {
    window.addEventListener(EVENT, callback);
    window.addEventListener('storage', callback);
    return () => {
        window.removeEventListener(EVENT, callback);
        window.removeEventListener('storage', callback);
    };
}

export function useHiddenAmounts(): [boolean, () => void] {
    const hidden = useSyncExternalStore(subscribe, isHidden, () => true);
    const toggle = () => {
        try {
            localStorage.setItem(KEY, hidden ? '0' : '1');
        } catch {
            // private window: the toggle still works until the page is left
        }
        window.dispatchEvent(new Event(EVENT));
    };

    return [hidden, toggle];
}

export function PrivateAmount({ children }: { children: ReactNode }) {
    const [hidden] = useHiddenAmounts();

    return hidden ? <span aria-label={__('admin.amount_hidden')} title={__('admin.amount_hidden')}>••••••</span> : <>{children}</>;
}

export function HideAmountsToggle({ className = '' }: { className?: string }) {
    const [hidden, toggle] = useHiddenAmounts();
    const Icon = hidden ? Eye : EyeOff;

    return (
        <button
            type="button"
            onClick={toggle}
            aria-pressed={!hidden}
            className={`inline-flex h-9 items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 text-sm font-medium text-slate-700 hover:bg-slate-50 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-200 ${className}`}
        >
            <Icon className="h-4 w-4" />
            {hidden ? __('admin.show_amounts') : __('admin.hide_amounts')}
        </button>
    );
}
