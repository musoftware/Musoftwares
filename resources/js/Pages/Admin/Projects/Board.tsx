import React from 'react';
import { Head, Link } from '@inertiajs/react';
import {
    ChevronLeft, ChevronRight, CalendarDays, ListTodo, FileText, Paperclip,
    Wallet, PiggyBank, Clock, LayoutDashboard, User, Archive, Building2,
} from 'lucide-react';
import AdminSidebarLayout from '@/Layouts/AdminSidebarLayout';
import ProjectBoard, { type BoardCard } from '@/Pages/Client/Projects/Components/ProjectBoard';
import { Breadcrumbs } from '@/Components/ui/Breadcrumbs';
import { MetricCard } from '@/Components/ui/MetricCard';
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
    const todayStr = format(new Date(), 'yyyy-MM-dd');
    const prev = format(new Date(day.getTime() - 86400000), 'yyyy-MM-dd');
    const next = format(new Date(day.getTime() + 86400000), 'yyyy-MM-dd');
    const dateInputKey = `board-date-${project.id}`;

    const isToday = date === todayStr;

    const breadcrumbs = [
        { label: __('general.admin_dashboard'), href: route('admin.dashboard') },
        { label: __('general.projects'), href: route('admin.projects.index') },
        { label: project.name, href: route('admin.projects.board.index', project.id) },
        { label: format(day, 'EEE, MMM d, yyyy') },
    ];

    return (
        <AdminSidebarLayout title={`${project.name} · ${__('general.board')}`} header={`${project.name} — ${__('general.board')}`}>
            <Head title={`${project.name} · ${__('general.board')} · ${format(day, 'MMM d, yyyy')}`} />

            <div className="space-y-6 p-4 sm:p-6 lg:p-8">
                {/* Breadcrumbs */}
                <Breadcrumbs items={breadcrumbs} />

                {/* Hero header card */}
                <div className="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <div className="flex flex-col gap-4 p-6 lg:flex-row lg:items-center lg:justify-between">
                        <div className="min-w-0 flex-1">
                            <div className="mb-2 flex flex-wrap items-center gap-2">
                                <Link
                                    href={route('admin.projects.index')}
                                    className="inline-flex items-center gap-1 rounded-full bg-slate-100 px-2.5 py-0.5 text-[11px] font-medium text-slate-600 transition-colors hover:bg-slate-200"
                                >
                                    <LayoutDashboard className="h-3 w-3" /> {__('general.all_projects')}
                                </Link>
                                <span className={cn('rounded-full px-2.5 py-0.5 text-[11px] font-semibold capitalize ring-1 ring-inset', STATUS_STYLES[project.status ?? ''] ?? 'bg-slate-100 text-slate-600 ring-slate-200')}>
                                    {project.status ? __(STATUS_LABEL_KEY[project.status] ?? `general.status_${project.status}`) : __('general.draft')}
                                </span>
                                {project.archived && (
                                    <span className="inline-flex items-center gap-1 rounded-full bg-slate-200 px-2.5 py-0.5 text-[11px] font-medium text-slate-600">
                                        <Archive className="h-3 w-3" /> {__('general.archived')}
                                    </span>
                                )}
                            </div>

                            <h1 className="text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl">{project.name}</h1>

                            <div className="mt-2 flex flex-wrap items-center gap-x-4 gap-y-1 text-sm text-slate-500">
                                {project.client_name && (
                                    <span className="inline-flex items-center gap-1.5">
                                        <Building2 className="h-3.5 w-3.5 text-slate-400" />
                                        <span className="font-medium text-slate-700">{project.client_name}</span>
                                    </span>
                                )}
                                {project.owner_name && (
                                    <span className="inline-flex items-center gap-1.5">
                                        <User className="h-3.5 w-3.5 text-slate-400" />
                                        <span className="font-medium text-slate-700">{project.owner_name}</span>
                                    </span>
                                )}
                                {(project.date_start || project.date_end) && (
                                    <span className="inline-flex items-center gap-1.5">
                                        <Clock className="h-3.5 w-3.5 text-slate-400" />
                                        {project.date_start ? formatDate(project.date_start) : '—'} → {project.date_end ? formatDate(project.date_end) : '…'}
                                    </span>
                                )}
                            </div>

                            {project.description && (
                                <p className="mt-3 max-w-3xl text-sm text-slate-500 line-clamp-2">{project.description}</p>
                            )}
                        </div>

                        {/* Date navigator */}
                        <div className="flex items-center gap-2 self-start lg:self-center">
                            <Link
                                href={route('admin.projects.board', { project: project.id, date: prev })}
                                preserveScroll
                                aria-label={__('general.previous_day')}
                                className="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-600 transition-colors hover:bg-slate-50 hover:text-slate-900"
                            >
                                <ChevronLeft className="h-4 w-4" />
                            </Link>

                            <div className={cn(
                                'inline-flex h-10 items-center gap-2 rounded-xl border px-3 text-sm font-medium',
                                isToday
                                    ? 'border-slate-900 bg-slate-900 text-white shadow-sm'
                                    : 'border-slate-200 bg-white text-slate-700',
                            )}>
                                <CalendarDays className="h-4 w-4" />
                                <span>{format(day, 'EEE, MMM d, yyyy')}</span>
                                {isToday && (
                                    <span className="rounded-full bg-white/20 px-1.5 py-0 text-[10px] font-bold uppercase tracking-wider">{__('general.today')}</span>
                                )}
                            </div>

                            <Link
                                href={route('admin.projects.board', { project: project.id, date: next })}
                                preserveScroll
                                aria-label={__('general.next_day')}
                                className="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-600 transition-colors hover:bg-slate-50 hover:text-slate-900"
                            >
                                <ChevronRight className="h-4 w-4" />
                            </Link>

                            {/* Direct date jump */}
                            <form
                                className="hidden sm:block"
                                onSubmit={(e) => {
                                    e.preventDefault();
                                    const v = (document.getElementById(dateInputKey) as HTMLInputElement | null)?.value;
                                    if (v) {
                                        window.location.href = route('admin.projects.board', { project: project.id, date: v });
                                    }
                                }}
                            >
                                <input
                                    id={dateInputKey}
                                    type="date"
                                    defaultValue={date}
                                    className="h-10 rounded-xl border border-slate-200 bg-white px-2 text-xs text-slate-700 focus:border-slate-400 focus:outline-none focus:ring-1 focus:ring-slate-300"
                                />
                            </form>

                            {!isToday && (
                                <Link
                                    href={route('admin.projects.board', { project: project.id, date: todayStr })}
                                    preserveScroll
                                    className="inline-flex h-10 items-center rounded-xl border border-slate-200 bg-white px-3 text-xs font-semibold uppercase tracking-wide text-slate-700 transition-colors hover:bg-slate-50"
                                >
                                    {__('general.today')}
                                </Link>
                            )}
                        </div>
                    </div>

                    {/* KPI row */}
                    <div className="grid grid-cols-2 divide-slate-100 border-t border-slate-100 lg:grid-cols-4 lg:divide-x">
                        <BudgetItem
                            label={__('general.budget')}
                            value={project.budget}
                            currency={project.currency}
                            icon={PiggyBank}
                            color="text-emerald-600 bg-emerald-50"
                        />
                        <BudgetItem
                            label={__('general.total_paid')}
                            value={project.total_paid}
                            currency={project.currency}
                            icon={Wallet}
                            color="text-sky-600 bg-sky-50"
                        />
                        <BudgetItem
                            label={__('general.remaining_balance')}
                            value={project.project_balance}
                            currency={project.currency}
                            icon={Wallet}
                            color="text-violet-600 bg-violet-50"
                        />
                        <div className="flex items-center gap-3 p-4">
                            <div className={cn('flex h-10 w-10 items-center justify-center rounded-xl bg-amber-50 text-amber-600')}>
                                <Clock className="h-5 w-5" />
                            </div>
                            <div className="min-w-0 flex-1">
                                <p className="text-[11px] font-medium uppercase tracking-wide text-slate-500">{__('general.progress')}</p>
                                <p className="font-mono text-lg font-semibold text-slate-900">{Math.round(project.percentage)}%</p>
                                <div className="mt-1 h-1.5 w-full overflow-hidden rounded-full bg-slate-100">
                                    <div
                                        className="h-full rounded-full bg-amber-500 transition-all"
                                        style={{ width: `${Math.min(100, Math.max(0, project.percentage))}%` }}
                                    />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {/* Quick navigation row */}
                <div className="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">
                    <Link
                        href={route('admin.projects.board.index', project.id)}
                        className="group flex items-center gap-3 rounded-xl border border-slate-900 bg-slate-900 p-4 text-white shadow-sm"
                    >
                        <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-white/10">
                            <LayoutDashboard className="h-5 w-5" />
                        </div>
                        <div className="flex-1">
                            <p className="text-sm font-semibold">{__('general.board')}</p>
                            <p className="text-xs text-slate-300">{__('general.kanban_view')}</p>
                        </div>
                    </Link>

                    <QuickLink
                        href={route('admin.projects.contracts.index', project.id)}
                        icon={FileText}
                        label={__('general.contracts')}
                        accent="text-violet-700 bg-violet-50"
                    />
                    <QuickLink
                        href={route('admin.projects.reports.index', project.id)}
                        icon={FileText}
                        label={__('general.reports')}
                        value={project.counts.reports}
                        accent="text-emerald-700 bg-emerald-50"
                    />
                    <QuickLink
                        href={route('admin.projects.files.index', project.id)}
                        icon={Paperclip}
                        label={__('general.files')}
                        value={project.counts.files}
                        accent="text-amber-700 bg-amber-50"
                    />
                </div>

                {/* The professional per-day board */}
                <ProjectBoard
                    projectId={project.id}
                    date={date}
                    lanes={lanes}
                    initialCards={cards}
                    hideFuture={false}
                />

                {/* Footer hint */}
                <p className="text-center text-xs text-slate-400">
                    {__('general.board_persistence_hint')}
                </p>
            </div>
        </AdminSidebarLayout>
    );
}

function BudgetItem({
    label, value, currency, icon: Icon, color,
}: {
    label: string; value: string; currency: BoardProject['currency']; icon: React.ElementType; color: string;
}) {
    return (
        <div className="flex items-center gap-3 p-4">
            <div className={cn('flex h-10 w-10 items-center justify-center rounded-xl', color)}>
                <Icon className="h-5 w-5" />
            </div>
            <div className="min-w-0">
                <p className="text-[11px] font-medium uppercase tracking-wide text-slate-500">{label}</p>
                <p className="truncate font-mono text-base font-semibold text-slate-900" title={formatMoney(value, currency)}>{formatMoney(value, currency)}</p>
            </div>
        </div>
    );
}

function QuickLink({
    href, icon: Icon, label, value, accent,
}: {
    href: string; icon: React.ElementType; label: string; value?: number; accent: string;
}) {
    return (
        <Link
            href={href}
            className="group flex items-center gap-3 rounded-xl border border-slate-200 bg-white p-4 transition-all hover:-translate-y-0.5 hover:border-slate-300 hover:shadow-md"
        >
            <div className={cn('flex h-10 w-10 items-center justify-center rounded-lg', accent)}>
                <Icon className="h-5 w-5" />
            </div>
            <div className="flex-1">
                <p className="text-sm font-semibold text-slate-900">{label}</p>
                <p className="text-xs text-slate-500">{value} {label.toLowerCase()}</p>
            </div>
            <ChevronRight className="h-4 w-4 text-slate-300 transition-colors group-hover:text-slate-600" />
        </Link>
    );
}
