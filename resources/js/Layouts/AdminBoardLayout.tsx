import React, { PropsWithChildren } from 'react';
import { Head } from '@inertiajs/react';
import { useInertiaNotifications } from '@/hooks/useInertiaNotifications';
import BoardNoticesRail from '@/Components/Admin/BoardNoticesRail';
import NoticesManager from '@/Components/Admin/NoticesManager';

interface AdminBoardLayoutProps extends PropsWithChildren {
    title?: string;
}

/**
 * Standalone shell for the project board workspace.
 * No admin sidebar, no admin chrome — the BoardTopNav IS the page header.
 * Renders the BoardNoticesRail on the left when there are due-today notices,
 * leaving the board canvas free to scroll horizontally inside the flex-1 column.
 * Mounts the NoticesManager modal so it can be opened from the rail or the top nav.
 */
export default function AdminBoardLayout({ title, children }: AdminBoardLayoutProps) {
    useInertiaNotifications();

    return (
        <div className="flex min-h-screen bg-slate-50">
            {title && <Head title={title} />}
            <BoardNoticesRail />
            <div className="flex-1 min-w-0">{children}</div>
            <NoticesManager />
        </div>
    );
}