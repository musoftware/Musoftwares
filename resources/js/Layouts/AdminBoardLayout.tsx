import React, { PropsWithChildren } from 'react';
import { Head } from '@inertiajs/react';
import { useInertiaNotifications } from '@/hooks/useInertiaNotifications';

interface AdminBoardLayoutProps extends PropsWithChildren {
    title?: string;
}

/**
 * Minimal standalone shell for the project board workspace.
 * No admin sidebar, no admin chrome — the BoardTopNav IS the page header.
 * Provides a clean canvas focused 100% on the board.
 */
export default function AdminBoardLayout({ title, children }: AdminBoardLayoutProps) {
    useInertiaNotifications();

    return (
        <div className="min-h-screen bg-slate-50">
            {title && <Head title={title} />}
            {children}
        </div>
    );
}