import React from 'react';
import { MessageSquare, MessagesSquare } from 'lucide-react';
import { Card, CardContent } from '@/Components/ui/card';
import { EmptyState } from '@/Components/ui/EmptyState';
import { __ } from '@/lib/i18n';

export interface TabCommentItem {
    id: number;
    body?: string | null;
    message?: string | null;
    user?: { id?: number; name?: string; avatar_url?: string | null } | null;
    created_at?: string | null;
}

export default function DiscussionsTab({ comments = [] }: { comments?: TabCommentItem[] }) {
    if (comments.length === 0) {
        return (
            <EmptyState
                icon={MessagesSquare}
                tone="friendly"
                title={__('general.no_discussions_yet')}
                description={__('general.empty_discussions_friendly')}
            />
        );
    }

    return (
        <div className="space-y-3">
            {comments.map((c) => (
                <Card key={c.id} className="rounded-xl border border-slate-200">
                    <CardContent className="flex items-start gap-3 p-4">
                        <div className="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-violet-100 text-violet-700 text-sm font-semibold">
                            {(c.user?.name ?? '?').substring(0, 2).toUpperCase()}
                        </div>
                        <div className="min-w-0 flex-1">
                            <div className="flex items-center gap-2">
                                <p className="font-medium text-slate-900">{c.user?.name ?? '—'}</p>
                                {c.created_at && (
                                    <span className="text-xs text-slate-400">{new Date(c.created_at).toLocaleDateString()}</span>
                                )}
                            </div>
                            <p className="mt-1 text-sm text-slate-600">{c.body ?? c.message ?? ''}</p>
                        </div>
                        <MessageSquare className="h-4 w-4 shrink-0 text-slate-300" />
                    </CardContent>
                </Card>
            ))}
        </div>
    );
}
