import { ReactNode } from 'react';
import { Head } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import SafeLink from '@/Components/SafeLink';
import { cn } from '@/lib/utils';

/**
 * The frame every client page shares, so the whole client area reads the same way:
 * a plain title, one line saying what the page is for, at most one main action, then sections.
 */
export default function ClientPage({ title, hint, action, width = 'md', children }: {
    title: string;
    hint?: string;
    action?: ReactNode;
    width?: 'sm' | 'md' | 'lg';
    children: ReactNode;
}) {
    const max = { sm: 'max-w-[680px]', md: 'max-w-[880px]', lg: 'max-w-[1100px]' }[width];

    return (
        <AuthenticatedLayout>
            <Head title={title} />
            <div className={cn('mx-auto w-full px-4 py-6 sm:px-6 sm:py-10', max)}>
                <div className="flex flex-wrap items-end justify-between gap-4">
                    <div className="min-w-0">
                        <h1 className="text-2xl font-bold tracking-tight sm:text-3xl">{title}</h1>
                        {hint && <p className="mt-1 text-sm text-[#1d1d1f]/60 dark:text-zinc-400">{hint}</p>}
                    </div>
                    {action}
                </div>
                <div className="mt-6 space-y-8">{children}</div>
            </div>
        </AuthenticatedLayout>
    );
}

/** A titled group on a client page. */
export function Section({ title, action, children }: { title?: string; action?: ReactNode; children: ReactNode }) {
    return (
        <section>
            {(title || action) && (
                <div className="mb-3 flex items-center justify-between gap-3">
                    {title && <h2 className="text-lg font-bold">{title}</h2>}
                    {action}
                </div>
            )}
            {children}
        </section>
    );
}

export function Card({ className, children }: { className?: string; children: ReactNode }) {
    return <div className={cn('rounded-[20px] border border-black/5 bg-white p-4 shadow-sm dark:border-white/10 dark:bg-zinc-900 sm:p-5', className)}>{children}</div>;
}

/** The one main button of a page or card. */
export function PrimaryAction({ href, onClick, icon, children, className }: { href?: string; onClick?: () => void; icon?: ReactNode; children: ReactNode; className?: string }) {
    const cls = cn('inline-flex h-11 items-center justify-center gap-2 rounded-full bg-[#0071e3] px-6 text-sm font-semibold text-white shadow-sm transition-colors hover:bg-[#0077ed]', className);
    return href ? <SafeLink href={href} className={cls}>{icon}{children}</SafeLink> : <button type="button" onClick={onClick} className={cls}>{icon}{children}</button>;
}

export function TextAction({ href, onClick, children, danger = false }: { href?: string; onClick?: () => void; children: ReactNode; danger?: boolean }) {
    const cls = cn('text-sm font-semibold hover:underline', danger ? 'text-rose-600' : 'text-[#0071e3]');
    return href ? <SafeLink href={href} className={cls}>{children}</SafeLink> : <button type="button" onClick={onClick} className={cls}>{children}</button>;
}

export function EmptyNote({ icon, children, action }: { icon?: ReactNode; children: ReactNode; action?: ReactNode }) {
    return (
        <Card className="flex flex-wrap items-center justify-between gap-3">
            <span className="flex items-center gap-3 text-sm text-[#1d1d1f]/60 dark:text-zinc-400">{icon}{children}</span>
            {action}
        </Card>
    );
}
