import React from 'react';
import { CalendarClock, Coffee, Flag, ListTodo } from 'lucide-react';
import { Card, CardContent } from '@/Components/ui/card';
import { EmptyState } from '@/Components/ui/EmptyState';
import { __ } from '@/lib/i18n';
import { formatDate } from '@/lib/utils';

export interface TabTaskItem {
    id: number;
    task_name: string;
    task_description?: string | null;
    due_date?: string | null;
    priority?: string | null;
    status?: string | null;
}

const PRIORITY_STYLES: Record<string, string> = {
    high: 'bg-rose-100 text-rose-700',
    urgent: 'bg-rose-100 text-rose-700',
    normal: 'bg-slate-100 text-slate-600',
    low: 'bg-slate-100 text-slate-600',
};

export default function TasksTab({ tasks = [] }: { tasks?: TabTaskItem[] }) {
    if (tasks.length === 0) {
        return (
            <EmptyState
                icon={Coffee}
                tone="friendly"
                title={__('general.no_tasks')}
                description={__('general.empty_tasks_friendly')}
            />
        );
    }

    return (
        <div className="space-y-3">
            {tasks.map((task) => (
                <Card key={task.id} className="rounded-xl border border-slate-200">
                    <CardContent className="p-4">
                        <div className="flex items-start justify-between gap-4">
                            <div className="flex items-start gap-3 min-w-0">
                                <div className="mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-indigo-50 text-indigo-600">
                                    <ListTodo className="h-4 w-4" />
                                </div>
                                <div className="min-w-0">
                                    <p className="font-semibold text-slate-900">{task.task_name}</p>
                                    {task.task_description && (
                                        <p className="mt-1 line-clamp-2 text-sm text-slate-500">{task.task_description}</p>
                                    )}
                                </div>
                            </div>
                            <div className="flex shrink-0 flex-col items-end gap-2">
                                {task.priority && (
                                    <span
                                        className={`inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs font-semibold capitalize ${
                                            PRIORITY_STYLES[task.priority] ?? PRIORITY_STYLES.normal
                                        }`}
                                    >
                                        <Flag className="h-3 w-3" /> {task.priority}
                                    </span>
                                )}
                                {task.due_date && (
                                    <span className="inline-flex items-center gap-1 text-xs text-slate-400">
                                        <CalendarClock className="h-3.5 w-3.5" /> {formatDate(task.due_date)}
                                    </span>
                                )}
                            </div>
                        </div>
                    </CardContent>
                </Card>
            ))}
        </div>
    );
}
