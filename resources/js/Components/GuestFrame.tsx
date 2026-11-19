import { ReactNode } from 'react';
import { Head, Link } from '@inertiajs/react';
import ApplicationLogo from '@/Components/ApplicationLogo';
import ThemeToggle from '@/Components/ThemeToggle';
import { cn } from '@/lib/utils';

/**
 * The plain page a guest sees from a link (an invoice to pay, a payment result):
 * the logo, a theme switch, a title, and the content. No sign-in needed.
 */
export default function GuestFrame({ title, hint, action, width = 'sm', children }: {
    title: string;
    hint?: string;
    action?: ReactNode;
    width?: 'sm' | 'lg';
    children: ReactNode;
}) {
    const max = width === 'lg' ? 'max-w-[1100px]' : 'max-w-[680px]';

    return (
        <div className="flex min-h-screen w-full flex-col overflow-x-clip bg-[#F8F8F8] font-sans text-[#111] dark:bg-zinc-950 dark:text-zinc-100">
            <Head title={title} />
            <header className={cn('mx-auto flex w-full items-center justify-between px-4 py-6', max)}>
                <Link href="/" className="flex items-center gap-2.5">
                    <span className="flex h-9 w-9 items-center justify-center rounded-lg bg-white p-1.5 dark:bg-zinc-900">
                        <ApplicationLogo className="h-full w-full object-contain" />
                    </span>
                    <span className="text-base font-semibold tracking-tight">Musoftware</span>
                </Link>
                <ThemeToggle className="h-9 w-9" />
            </header>
            <main className={cn('mx-auto w-full flex-1 px-4 pb-10', max)}>
                <div className="mb-6 flex flex-wrap items-end justify-between gap-4">
                    <div className="min-w-0">
                        <h1 className="text-2xl font-bold tracking-tight sm:text-3xl">{title}</h1>
                        {hint && <p className="mt-1 text-sm text-[#1d1d1f]/60 dark:text-zinc-400">{hint}</p>}
                    </div>
                    {action}
                </div>
                {children}
            </main>
        </div>
    );
}
