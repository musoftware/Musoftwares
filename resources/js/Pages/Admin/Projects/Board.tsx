import React, { useCallback, useMemo, useState } from 'react';
import {
    CalendarDays, Wallet, PiggyBank, Clock, LayoutDashboard,
} from 'lucide-react';
import { router } from '@inertiajs/react';
import AdminBoardLayout from '@/Layouts/AdminBoardLayout';
import ProjectBoard, { type BoardCard } from '@/Pages/Client/Projects/Components/ProjectBoard';
import BoardTopNav, { type BoardFilter } from './Components/BoardTopNav';
import { format, parseISO } from 'date-fns';
import { cn } from '@/lib/utils';
import { formatMoney, formatDate } from '@/lib/utils';
import { __ } from '@/lib/i18n';
import type { BoardProject } from '@/types/project';

interface Props {
    project: BoardProject;
    date: string;
    lanes: string[];
    cards: BoardCard[];
}

const STATUS_STYLES: Record<string, string> = {
    open: 'bg-emerald-100 text-emerald-700 ring-emerald-200',
    hold_on: 'bg-amber-100 text-amber-700 ring-amber-200',
    closed: 'bg-slate-200 text-slate-700 ring-slate-300',
};

const STATUS_LABEL_KEY: Record<string, string> = {
    open: 'general.status_open',
    hold_on: 'general.status_hold_on',
    closed: 'general.status_closed',
};

export default function AdminProjectBoard({ project, date, lanes, cards }: Props) {
    const day = parseISO(date);
    const [filter, setFilter] = useState<BoardFilter>('all');

    const triggerAddNote = useCallback(() => {
        const btn = document.querySelector<HTMLButtonElement>('[data-board-add-note]');
        if (btn) {
            btn.scrollIntoView({ behavior: 'smooth', block: 'center' });
            window.setTimeout(() => btn.click(), 220);
        }
    }, []);

    const handleAdd = useCallback(
        (kind: 'note' | 'task' | 'todo' | 'file' | 'report' | 'card') => {
            if (kind === 'note') {
                triggerAddNote();
                return;
            }
            const routeName =
                kind === 'task' ? 'admin.projects.tasks.index' :
                kind === 'todo' ? 'admin.projects.tasks.index' :
                kind === 'file' ? 'admin.projects.files.index' :
                kind === 'report' ? 'admin.projects.reports.create' :
                'admin.projects.board.index';
            try {
                router.visit(route(routeName, project.id));
            } catch {
                router.visit(route('admin.projects.board.index', project.id));
            }
        },
        [project.id, triggerAddNote],
    );

    const counts = useMemo(
        () => ({
            all: cards.length,
            note: cards.filter((c) => c.type === 'note').length,
            task: cards.filter((c) => c.type === 'task').length,
            report: cards.filter((c) => c.type === 'report').length,
        }),
        [cards],
    );

    return (
        <AdminBoardLayout
            title={`${project.name} · ${__('general.board')}`}
        >
            {/* Sticky custom top nav — uses same visual language as the Client Board */}
            <BoardTopNav
                project={{
                    id: project.id,
                    name: project.name,
                    status: project.status ?? undefined,
                    archived: project.archived,
                }}
                activeFilter={filter}
                onFilterChange={setFilter}
                counts={counts}
                date={date}
                onAdd={handleAdd}
            />

            <div className="mx-auto w-full max-w-7xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">
                {/* Compact summary strip */}
                <div className="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <div className="flex flex-col gap-4 p-5 lg:flex-row lg:items-center lg:justify-between">
                        <div className="min-w-0 flex-1">
                            <div className="mb-2 flex flex-wrap items-center gap-2">
                                <span className="inline-flex items-center gap-1 rounded-full bg-slate-100 px-2.5 py-0.5 text-[11px] font-medium text-slate-600">
                                    <LayoutDashboard className="h-3 w-3" /> {__('general.board')}
                                </span>
                                <span className={cn('rounded-full px-2.5 py-0.5 text-[11px] font-semibold capitalize ring-1 ring-inset', STATUS_STYLES[project.status ?? ''] ?? 'bg-slate-100 text-slate-600 ring-slate-200')}>
                                    {project.status ? __(STATUS_LABEL_KEY[project.status] ?? `general.status_${project.status}`) : __('general.draft')}
                                </span>
                                <span className="inline-flex items-center gap-1.5 text-xs text-slate-500">
                                    <CalendarDays className="h-3 w-3 text-slate-400" />
                                    {format(day, 'EEEE, MMMM d, yyyy')}
                                </span>
                            </div>
                            <h1 className="text-xl font-bold tracking-tight text-slate-900 sm:text-2xl">{project.name}</h1>
                            {project.description && (
                                <p className="mt-1 max-w-3xl text-sm text-slate-500 line-clamp-1">{project.description}</p>
                            )}
                            {(project.date_start || project.date_end) && (
                                <p className="mt-1 inline-flex items-center gap-1.5 text-xs text-slate-500">
                                    <Clock className="h-3 w-3 text-slate-400" />
                                    {project.date_start ? formatDate(project.date_start) : '—'} → {project.date_end ? formatDate(project.date_end) : '…'}
                                </p>
                            )}
                        </div>

                        <div className="grid w-full grid-cols-2 gap-2 lg:w-auto lg:grid-cols-4">
                            <SummaryItem
                                label={__('general.budget')}
                                value={formatMoney(project.budget, project.currency)}
                                icon={PiggyBank}
                                tone="text-emerald-600 bg-emerald-50"
                            />
                            <SummaryItem
                                label={__('general.total_paid')}
                                value={formatMoney(project.total_paid, project.currency)}
                                icon={Wallet}
                                tone="text-sky-600 bg-sky-50"
                            />
                            <SummaryItem
                                label={__('general.remaining_balance')}
                                value={formatMoney(project.project_balance, project.currency)}
                                icon={Wallet}
                                tone="text-violet-600 bg-violet-50"
                            />
                            <SummaryItem
                                label={__('general.progress')}
                                value={`${Math.round(project.percentage)}%`}
                                icon={Clock}
                                tone="text-amber-600 bg-amber-50"
                                progress={project.percentage}
                            />
                        </div>
                    </div>
                </div>

                {/* The shared per-day board, controlled by the top-nav filter */}
                <ProjectBoard
                    projectId={project.id}
                    date={date}
                    lanes={lanes}
                    initialCards={cards}
                    hideFuture={false}
                    externalFilter={
                        filter === 'note' || filter === 'task' || filter === 'report' || filter === 'all'
                            ? filter
                            : 'all'
                    }
                />

                <p className="text-center text-xs text-slate-400">
                    {__('general.board_persistence_hint')}
                </p>
            </div>
        </AdminBoardLayout>
    );
}

function SummaryItem({
    label, value, icon: Icon, tone, progress,
}: {
    label: string;
    value: string;
    icon: React.ElementType;
    tone: string;
    progress?: number;
}) {
    return (
        <div className="flex items-center gap-2.5 rounded-xl border border-slate-100 bg-white p-2.5">
            <div className={cn('flex h-8 w-8 shrink-0 items-center justify-center rounded-lg', tone)}>
                <Icon className="h-4 w-4" />
            </div>
            <div className="min-w-0 flex-1">
                <p className="text-[10px] font-medium uppercase tracking-wide text-slate-500">{label}</p>
                <p className="truncate font-mono text-sm font-semibold text-slate-900">{value}</p>
                {typeof progress === 'number' && (
                    <div className="mt-1 h-1 w-full overflow-hidden rounded-full bg-slate-100">
                        <div
                            className="h-full rounded-full bg-amber-500 transition-all"
                            style={{ width: `${Math.min(100, Math.max(0, progress))}%` }}
                        />
                    </div>
                )}
            </div>
        </div>
    );
}