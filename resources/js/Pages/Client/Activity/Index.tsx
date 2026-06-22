import React, { useState } from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router } from '@inertiajs/react';
import { ActivityFeed, ActivityEventItem } from '@/Components/ui/ActivityFeed';
import { cn } from '@/lib/utils';
import { Activity, Filter } from 'lucide-react';
import { __ } from '@/lib/i18n';

interface PaginatedActivities {
    data: ActivityEventItem[];
    current_page: number;
    last_page: number;
    total: number;
    per_page: number;
    next_page_url: string | null;
    prev_page_url: string | null;
}

interface Props {
    activities: PaginatedActivities;
    filters: {
        workspace?: string;
        event?: string;
    };
}

const WORKSPACES = [
    { key: '',            label: 'All Activity' },
    { key: 'erp',        label: 'ERP' },
    { key: 'marketplace',label: 'Marketplace' },
    { key: 'freelance',  label: 'Freelance' },
    { key: 'booking',    label: 'Booking' },
    { key: 'system',     label: 'System' },
];

export default function ActivityIndex({ activities, filters }: Props) {
    const [activeWorkspace, setActiveWorkspace] = useState(filters.workspace ?? '');

    const filterBy = (workspace: string) => {
        setActiveWorkspace(workspace);
        router.get(route('activity.index'), { workspace: workspace || undefined }, {
            preserveState: true,
            preserveScroll: true,
        });
    };

    return (
        <AuthenticatedLayout header={undefined}>
            <Head title={__('general.activity_log')} />

            <div className="min-h-screen bg-slate-50/50">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 py-10">

                    {/* Header */}
                    <div className="flex items-start justify-between mb-8">
                        <div>
                            <div className="flex items-center gap-2.5 mb-1">
                                <div className="w-8 h-8 bg-indigo-600 rounded-lg flex items-center justify-center">
                                    <Activity className="w-4 h-4 text-white" />
                                </div>
                                <h1 className="text-2xl font-bold text-slate-900">{__('general.activity_log')}</h1>
                            </div>
                            <p className="text-sm text-slate-500 ms-10">
                                {activities.total} events across the platform
                            </p>
                        </div>
                    </div>

                    {/* Workspace filter tabs */}
                    <div className="bg-white border border-slate-200/60 rounded-2xl p-1 flex flex-wrap gap-1 mb-8 shadow-sm">
                        {WORKSPACES.map(ws => (
                            <button
                                key={ws.key}
                                onClick={() => filterBy(ws.key)}
                                className={cn(
                                    'px-4 py-2 rounded-xl text-sm font-semibold transition-all',
                                    activeWorkspace === ws.key
                                        ? 'bg-slate-900 text-white shadow-sm'
                                        : 'text-slate-500 hover:text-slate-800 hover:bg-slate-100'
                                )}
                            >
                                {ws.label}
                            </button>
                        ))}
                    </div>

                    {/* Activity feed */}
                    <div className="bg-white border border-slate-200/60 rounded-2xl p-6 shadow-sm">
                        <ActivityFeed
                            items={activities.data}
                            showWorkspace={!activeWorkspace}
                        />

                        {/* Pagination */}
                        {activities.last_page > 1 && (
                            <div className="flex items-center justify-end gap-4 pt-6 mt-6 border-t border-slate-100">
                                <p className="text-sm text-slate-500">
                                    Page {activities.current_page} of {activities.last_page}
                                </p>
                                <div className="flex gap-2">
                                    {activities.prev_page_url && (
                                        <Link
                                            href={activities.prev_page_url}
                                            className="px-4 py-2 text-sm font-semibold text-slate-600 bg-slate-100 rounded-xl hover:bg-slate-200 transition-colors"
                                        >
                                            ← Previous
                                        </Link>
                                    )}
                                    {activities.next_page_url && (
                                        <Link
                                            href={activities.next_page_url}
                                            className="px-4 py-2 text-sm font-semibold text-white bg-indigo-600 rounded-xl hover:bg-indigo-700 transition-colors"
                                        >
                                            Next →
                                        </Link>
                                    )}
                                </div>
                            </div>
                        )}
                    </div>

                </div>
            </div>
        </AuthenticatedLayout>
    );
}
