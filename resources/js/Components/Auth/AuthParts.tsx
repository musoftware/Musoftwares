import { ButtonHTMLAttributes, ReactNode } from 'react';
import { Loader2 } from 'lucide-react';
import { __ } from '@/lib/i18n';
import { cn } from '@/lib/utils';

/** Page title and one optional short line under it. */
export function AuthHeader({ title, subtitle }: { title: string; subtitle?: ReactNode }) {
    return (
        <div className="space-y-2">
            <h1 className="text-[26px] font-semibold leading-tight tracking-tight text-[#111] dark:text-white">{title}</h1>
            {subtitle && <p className="text-[15px] leading-relaxed text-[#555] dark:text-zinc-400">{subtitle}</p>}
        </div>
    );
}

/** A calm green note, used after the server confirms something (link sent, password reset). */
export function AuthStatus({ children }: { children?: ReactNode }) {
    if (!children) return null;

    return (
        <div role="status" className="calm-fade rounded-xl bg-[#E9F6EC] px-4 py-3 text-sm text-[#1E6B34] dark:bg-emerald-950/40 dark:text-emerald-300">
            {children}
        </div>
    );
}

type SubmitProps = ButtonHTMLAttributes<HTMLButtonElement> & { busy: boolean; busyLabel: string; label: string };

/** The one main action on a guest page. Keeps its width while busy so nothing jumps. */
export function AuthSubmit({ busy, busyLabel, label, className, ...button }: SubmitProps) {
    return (
        <button
            type="submit"
            disabled={busy}
            aria-busy={busy}
            className={cn(
                'calm-press flex h-12 w-full items-center justify-center gap-2 rounded-xl bg-[#111] text-[15px] font-medium text-white',
                'hover:bg-[#2a2a2a] disabled:cursor-wait disabled:opacity-80 dark:bg-white dark:text-[#111] dark:hover:bg-zinc-200',
                'focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#0071e3]',
                className,
            )}
            {...button}
        >
            {busy && <Loader2 className="h-4 w-4 animate-spin" />}
            <span>{busy ? busyLabel : label}</span>
        </button>
    );
}

/** "Continue with Google" and the thin "or" line under it. */
export function GoogleSignIn({ href }: { href: string }) {
    return (
        <>
            <a
                href={href}
                className="calm-press flex h-12 w-full items-center justify-center gap-3 rounded-xl border border-black/10 bg-white text-[15px] font-medium text-[#111] hover:bg-[#F8F8F8] dark:border-white/15 dark:bg-zinc-900 dark:text-white dark:hover:bg-zinc-800"
            >
                <GoogleMark />
                <span>{__('general.continue_with_google')}</span>
            </a>
            <div className="flex items-center gap-4 text-[13px] text-[#999] dark:text-zinc-500" aria-hidden="true">
                <span className="h-px flex-1 bg-black/[0.08] dark:bg-white/10" />
                {__('general.or_continue_with')}
                <span className="h-px flex-1 bg-black/[0.08] dark:bg-white/10" />
            </div>
        </>
    );
}

/** The small line at the bottom of the card, like "No account? Sign up". */
export function AuthFooter({ children }: { children: ReactNode }) {
    return <div className="pt-2 text-center text-sm text-[#555] dark:text-zinc-400">{children}</div>;
}

export const authLinkClass = 'font-medium text-[#0071e3] hover:underline underline-offset-4';

function GoogleMark() {
    return (
        <svg className="h-[18px] w-[18px]" viewBox="0 0 24 24" aria-hidden="true">
            <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4" />
            <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853" />
            <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05" />
            <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335" />
        </svg>
    );
}
