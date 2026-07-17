import React, { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import { ArrowLeft, CalendarClock, Coffee, EyeOff, Flag, ListTodo, ExternalLink } from 'lucide-react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Card, CardContent } from '@/Components/ui/card';
import { EmptyState } from '@/Components/ui/EmptyState';
import { Sheet, SheetContent, SheetHeader, SheetTitle, SheetDescription, SheetTrigger } from '@/Components/ui/sheet';
import { formatDate } from '@/lib/utils';
import { __ } from '@/lib/i18n';

interface TaskItem {
    id: number;
    task_name: string;
    task_description?: string;
    due_date?: string | null;
    priority?: string | null;
}

interface Props {
    project: { id: number; name: string };
    tasks: TaskItem[];
    hideFuture: boolean;
}

const PRIORITY_STYLES: Record<string, string> = {
    high: 'bg-rose-100 text-rose-700',
    urgent: 'bg-rose-100 text-rose-700',
    normal: 'bg-slate-100 text-slate-600',
    low: 'bg-slate-100 text-slate-600',
};

export default function ProjectTasks({ project, tasks = [], hideFuture }: Props) {
    const [openId, setOpenId] = useState<number | null>(null);
    const [triggerEl, setTriggerEl] = useState<HTMLElement | null>(null);

    const open = (id: number, el: HTMLElement) => {
        setTriggerEl(el);
        setOpenId(id);
        const url = new URL(window.location.href);
        url.searchParams.set('task', String(id));
        window.history.replaceState({}, '', url.pathname + url.search + url.hash);
    };

    const close = () => {
        setOpenId(null);
        triggerEl?.focus();
        const url = new URL(window.location.href);
        if (url.searchParams.has('task')) {
            url.searchParams.delete('task');
            window.history.replaceState({}, '', url.pathname + url.search + url.hash);
        }
    };

    const active = openId != null ? tasks.find((t) => t.id === openId) ?? null : null;

    return (
        <AuthenticatedLayout>
            <Head title={`${project.name} · ${__('general.tasks')}`} />
            <div className="mx-auto w-full max-w-5xl space-y-6 px-4 py-8 sm:px-6 lg:px-8">
                <div>
                    <Link
                        href={route('client.projects.show', project.id)}
                        className="mb-1 inline-flex items-center gap-1 text-sm text-slate-500 hover:text-slate-800"
                    >
                        <ArrowLeft className="h-4 w-4" /> {project.name}
                    </Link>
                    <h1 className="flex items-center gap-2 text-2xl font-bold tracking-tight text-slate-900">
                        <ListTodo className="h-6 w-6 text-slate-400" /> {__('general.tasks')}
                    </h1>
                    {hideFuture && (
                        <p className="mt-2 inline-flex items-center gap-1.5 rounded-lg bg-amber-50 px-3 py-1.5 text-xs font-medium text-amber-700">
                            <EyeOff className="h-3.5 w-3.5" /> {__('general.future_tasks_are_hidden')}
                        </p>
                    )}
                </div>

                {tasks.length === 0 ? (
                    <EmptyState
                        icon={Coffee}
                        tone="friendly"
                        title={__('general.no_tasks')}
                        description={__('general.empty_tasks_friendly')}
                    />
                ) : (
                    <div className="space-y-3">
                        {tasks.map((task) => (
                            <Card key={task.id} className="rounded-xl border border-slate-200">
                                <CardContent className="p-4">
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
                                    <div className="mt-3 flex justify-end">
                                        <button
                                            type="button"
                                            ref={(el) => {
                                                if (el) (el as any).dataset.taskId = String(task.id);
                                            }}
                                            onClick={(e) => open(task.id, e.currentTarget)}
                                            className="rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-medium text-slate-700 transition-colors hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-indigo-300"
                                            aria-label={`Open ${task.task_name}`}
                                        >
                                            {__('general.open')}
                                        </button>
                                    </div>
                                </CardContent>
                            </Card>
                        ))}
                    </div>
                )}
            </div>

            <Sheet open={openId != null} onOpenChange={(o) => (o ? null : close())}>
                <SheetContent
                    side="right"
                    className="w-full gap-0 overflow-y-auto p-0 sm:max-w-md"
                >
                    {active && (
                        <>
                            <SheetHeader className="border-b border-slate-100 bg-slate-50/50 p-6 text-start">
                                <SheetTitle className="text-lg font-semibold text-slate-900">
                                    {active.task_name}
                                </SheetTitle>
                                <SheetDescription className="mt-1 text-sm text-slate-500">
                                    {active.due_date ? `${__('general.due_date')}: ${formatDate(active.due_date)}` : ''}
                                </SheetDescription>
                            </SheetHeader>
                            <div className="space-y-4 p-6">
                                {active.priority && (
                                    <div className="flex items-center gap-2 text-xs">
                                        <Flag className="h-3.5 w-3.5 text-slate-400" />
                                        <span
                                            className={`rounded-full px-2 py-0.5 font-semibold capitalize ${
                                                PRIORITY_STYLES[active.priority] ?? PRIORITY_STYLES.normal
                                            }`}
                                        >
                                            {active.priority}
                                        </span>
                                    </div>
                                )}
                                {active.task_description ? (
                                    <div className="prose prose-sm max-w-none text-slate-700">
                                        <p className="whitespace-pre-wrap">{active.task_description}</p>
                                    </div>
                                ) : (
                                    <p className="text-sm text-slate-400">{__('general.no_description')}</p>
                                )}
                                <Link
                                    href={route('client.projects.tasks.index', { project: project.id })}
                                    className="inline-flex items-center gap-1.5 text-xs text-slate-500 hover:text-slate-900"
                                >
                                    <ExternalLink className="h-3.5 w-3.5" /> {__('general.view_all')}
                                </Link>
                            </div>
                        </>
                    )}
                </SheetContent>
            </Sheet>
        </AuthenticatedLayout>
    );
}
