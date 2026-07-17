import React from 'react';
import { formatDate } from '@/lib/utils';
import { cn } from '@/lib/utils';
import { CalendarClock, Flag, ListTodo } from 'lucide-react';
import { EmptyState } from '@/Components/ui/EmptyState';
import { __ } from '@/lib/i18n';

export interface TabTask {
    id: number;
    task_name: string;
    task_description?: string | null;
    due_date?: string | null;
    priority?: string | null;
}

interface Props {
    tasks: TabTask[];
}

const PRIORITY_STYLES: Record<string, string> = {
    high: 'bg-rose-100 text-rose-700',
    urgent: 'bg-rose-100 text-rose-700',
    normal: 'bg-slate-100 text-slate-600',
    low: 'bg-slate-100 text-slate-600',
};

export function ProjectTasksTab({ tasks = [] }: Props) {
    if (!tasks.length) {
        return (
            <EmptyState
                icon={ListTodo}
                tone="friendly"
                title={__('general.no_tasks_in_this_project') || __('general.no_tasks')}
                description={__('general.no_tasks_desc')}
            />
        );
    }

    return (
        <ul className="space-y-3">
            {tasks.map((task) => (
                <li
                    key={task.id}
                    className="rounded-xl border border-slate-200 bg-white p-4 transition-colors hover:border-slate-300"
                >
                    <div className="flex items-start justify-between gap-4">
                        <div className="min-w-0">
                            <p className="font-semibold text-slate-900">{task.task_name}</p>
                            {task.task_description && (
                                <p className="mt-1 line-clamp-2 text-sm text-slate-500">
                                    {task.task_description}
                                </p>
                            )}
                        </div>
                        <div className="flex shrink-0 flex-col items-end gap-2">
                            {task.priority && (
                                <span
                                    className={cn(
                                        'inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs font-semibold capitalize',
                                        PRIORITY_STYLES[task.priority] ?? PRIORITY_STYLES.normal,
                                    )}
                                >
                                    <Flag className="h-3 w-3" aria-hidden="true" />
                                    {task.priority}
                                </span>
                            )}
                            {task.due_date && (
                                <span className="inline-flex items-center gap-1 text-xs text-slate-400">
                                    <CalendarClock className="h-3.5 w-3.5" aria-hidden="true" />
                                    {formatDate(task.due_date)}
                                </span>
                            )}
                        </div>
                    </div>
                </li>
            ))}
        </ul>
    );
}

export default ProjectTasksTab;
