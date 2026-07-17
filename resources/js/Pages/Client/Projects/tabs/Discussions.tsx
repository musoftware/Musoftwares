import React from 'react';
import { formatDate } from '@/lib/utils';
import { MessageSquare } from 'lucide-react';
import { EmptyState } from '@/Components/ui/EmptyState';
import { __ } from '@/lib/i18n';

export interface TabDiscussion {
    id: number;
    body: string;
    user_id: number;
    user?: { id: number; name: string; avatar_url?: string | null };
    created_at: string;
}

interface Props {
    discussions: TabDiscussion[];
}

export function ProjectDiscussionsTab({ discussions = [] }: Props) {
    if (!discussions.length) {
        return (
            <EmptyState
                icon={MessageSquare}
                tone="friendly"
                title={__('general.no_discussions_yet') || 'No discussions yet'}
                description={
                    __('general.start_the_conversation') ||
                    'Start the conversation — your team and admins will see your updates here.'
                }
            />
        );
    }

    return (
        <ol className="space-y-3">
            {discussions.map((item) => (
                <li key={item.id} className="flex gap-3 rounded-xl border border-slate-200 bg-white p-4">
                    <div
                        className="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-slate-100 text-sm font-semibold text-slate-600"
                        aria-hidden="true"
                    >
                        {(item.user?.name || '?').slice(0, 1).toUpperCase()}
                    </div>
                    <div className="min-w-0 flex-1">
                        <div className="flex flex-wrap items-baseline gap-2">
                            <span className="text-sm font-semibold text-slate-900">
                                {item.user?.name || __('general.unknown_user')}
                            </span>
                            <span className="text-xs text-slate-400">
                                {item.created_at ? formatDate(item.created_at) : ''}
                            </span>
                        </div>
                        <p className="mt-1 whitespace-pre-line text-sm leading-relaxed text-slate-700">
                            {item.body}
                        </p>
                    </div>
                </li>
            ))}
        </ol>
    );
}

export default ProjectDiscussionsTab;
