import React from 'react';
import { Link } from '@inertiajs/react';
import { AlertCircle, Archive, ArchiveRestore, Edit, LayoutDashboard, ListTodo, FileText, Paperclip, Trash2, User, Wallet } from 'lucide-react';
import { Avatar, AvatarFallback } from '@/Components/ui/avatar';
import { Button } from '@/Components/ui/button';
import { Card, CardContent } from '@/Components/ui/card';
import { Checkbox } from '@/Components/ui/checkbox';
import { cn, formatMoney, formatDate } from '@/lib/utils';
import type { Project } from '@/types/project';

const STATUS_STYLES: Record<string, string> = {
    open: 'bg-emerald-100 text-emerald-700',
    hold_on: 'bg-amber-100 text-amber-700',
    closed: 'bg-slate-200 text-slate-700',
};

function initials(name?: string | null): string {
    if (!name) return '?';
    return name
        .split(/\s+/)
        .filter(Boolean)
        .slice(0, 2)
        .map((p) => p[0]?.toUpperCase() ?? '')
        .join('');
}

export interface ProjectCardProps {
    project: Project;
    isSelected: boolean;
    onSelect: (id: number) => void;
    onEdit: (project: Project) => void;
    onOpenBoard: (project: Project) => void;
    onAnalyze: (project: Project) => void;
    onArchive: (id: number) => void;
    onRestore: (id: number) => void;
    onDelete: (id: number) => void;
    onShowUnpaidInvoices: (project: Project) => void;
}

export function ProjectCard({
    project,
    isSelected,
    onSelect,
    onEdit,
    onOpenBoard,
    onAnalyze,
    onArchive,
    onRestore,
    onDelete,
    onShowUnpaidInvoices,
}: ProjectCardProps) {
    const status = project.status ?? 'open';
    const unpaid = project.counts?.invoices_unpaid ?? 0;
    const percentage = Math.min(100, Math.max(0, project.percentage ?? 0));

    return (
        <Card
            className={cn(
                'group flex flex-col overflow-hidden rounded-xl border border-slate-200 shadow-sm transition-shadow hover:shadow-md',
                isSelected && 'ring-2 ring-indigo-500',
            )}
        >
            <CardContent className="flex flex-1 flex-col p-5">
                <div className="mb-3 flex items-start justify-between gap-2">
                    <div className="flex min-w-0 items-center gap-2">
                        <Checkbox
                            checked={isSelected}
                            onCheckedChange={() => onSelect(project.id)}
                            aria-label={__('general.select_project', { name: project.project_name })}
                            className={cn(
                                'shrink-0 transition-opacity',
                                isSelected ? 'opacity-100' : 'opacity-0 group-hover:opacity-100',
                            )}
                        />
                        <Link
                            href={route('admin.projects.board.index', project.id)}
                            className={cn(
                                'truncate text-lg font-semibold text-slate-900 hover:underline',
                                project.archived && 'opacity-60',
                            )}
                        >
                            {project.project_name}
                        </Link>
                    </div>
                    {project.archived ? (
                        <span className="shrink-0 rounded-full bg-slate-200 px-2 py-0.5 text-xs font-semibold text-slate-700">
                            {__('general.archived')}
                        </span>
                    ) : (
                        <span className={cn('shrink-0 rounded-full px-2 py-0.5 text-xs font-semibold capitalize', STATUS_STYLES[status] ?? 'bg-slate-100 text-slate-600')}>
                            {status.replace('_', ' ')}
                        </span>
                    )}
                </div>

                {/* Client / owner row */}
                <div className="mb-4 flex items-center gap-3">
                    {project.client ? (
                        <Avatar className="h-9 w-9 border border-slate-200" size="lg">
                            <AvatarFallback className="bg-slate-50 text-xs font-semibold text-slate-700">
                                {initials(project.client.name)}
                            </AvatarFallback>
                        </Avatar>
                    ) : (
                        <Avatar className="h-9 w-9 border border-slate-200" size="lg">
                            <AvatarFallback className="bg-slate-50 text-slate-400">
                                <User className="h-4 w-4" />
                            </AvatarFallback>
                        </Avatar>
                    )}
                    <div className="flex min-w-0 flex-col text-sm">
                        <span className="truncate font-semibold text-slate-900">{project.client?.name ?? __('general.unknown')}</span>
                        <span className="truncate text-xs text-slate-500">
                            {project.owner ? `${__('general.owner')}: ${project.owner.name}` : project.client?.email}
                        </span>
                    </div>
                </div>

                {/* Progress */}
                <div className="mb-4">
                    <div className="mb-1 flex items-center justify-between text-xs text-slate-500">
                        <span>{__('general.progress')}</span>
                        <span className="font-semibold text-slate-700">{Math.round(percentage)}%</span>
                    </div>
                    <div className="h-2 w-full overflow-hidden rounded-full bg-slate-100">
                        <div className="h-full rounded-full bg-emerald-500" style={{ width: `${percentage}%` }} />
                    </div>
                </div>

                {/* Cost / Paid / Pending stat tiles */}
                <div className="mb-4 grid grid-cols-3 gap-2 text-sm">
                    <div className="rounded-lg bg-rose-50 p-2">
                        <p className="text-[10px] uppercase tracking-wide text-rose-500/70">{__('general.cost')}</p>
                        <p className="font-mono text-xs font-semibold text-rose-700">{formatMoney(project.cost, project.currency)}</p>
                    </div>
                    <div className="rounded-lg bg-emerald-50 p-2">
                        <p className="text-[10px] uppercase tracking-wide text-emerald-500/70">{__('general.paid_invoices')}</p>
                        <p className="font-mono text-xs font-semibold text-emerald-700">{formatMoney(project.paid_invoices, project.currency)}</p>
                    </div>
                    <div className="rounded-lg bg-amber-50 p-2">
                        <p className="text-[10px] uppercase tracking-wide text-amber-500/70">{__('general.pending_invoices')}</p>
                        <p className="font-mono text-xs font-semibold text-amber-700">{formatMoney(project.pending_invoices, project.currency)}</p>
                    </div>
                </div>

                {unpaid > 0 && (
                    <div
                        className="mb-4 flex w-fit items-center gap-1 rounded border border-red-200 bg-red-50 px-2 py-0.5 text-xs font-semibold text-red-700 cursor-pointer hover:bg-red-100 transition-colors"
                        title={__('general.unpaid_invoices_count', { count: unpaid })}
                        onClick={() => onShowUnpaidInvoices(project)}
                    >
                        <AlertCircle className="h-3 w-3" /> {unpaid} {__('general.unpaid_dues')}
                    </div>
                )}

                {(project.date_start || project.date_end) && (
                    <p className="mb-3 text-xs text-slate-400">
                        {formatDate(project.date_start)} → {project.date_end ? formatDate(project.date_end) : '…'}
                    </p>
                )}

                {/* Counts footer */}
                <div className="mt-auto flex items-center gap-3 border-t border-slate-100 pt-3 text-xs text-slate-500">
                    <span className="inline-flex items-center gap-1" title={__('general.tasks')}><ListTodo className="h-3.5 w-3.5" /> {project.counts?.tasks ?? 0}</span>
                    <span className="inline-flex items-center gap-1" title={__('general.reports')}><FileText className="h-3.5 w-3.5" /> {project.counts?.reports ?? 0}</span>
                    <span className="inline-flex items-center gap-1" title={__('general.files')}><Paperclip className="h-3.5 w-3.5" /> {project.counts?.files ?? 0}</span>
                </div>

                {/* Action buttons */}
                <div className="mt-3 flex items-center justify-end gap-1.5">
                    <Button variant="outline" size="sm" onClick={() => onOpenBoard(project)} aria-label={__('general.board')} title={__('general.board')}>
                        <LayoutDashboard className="h-3.5 w-3.5" />
                    </Button>
                    <Button variant="outline" size="sm" onClick={() => onAnalyze(project)} aria-label={__('general.cost_analysis')} title={__('general.cost_analysis')}>
                        <Wallet className="h-3.5 w-3.5" />
                    </Button>
                    <Button variant="outline" size="sm" onClick={() => onEdit(project)} aria-label={__('general.edit')} title={__('general.edit')}>
                        <Edit className="h-3.5 w-3.5" />
                    </Button>
                    {project.archived ? (
                        <Button variant="outline" size="sm" onClick={() => onRestore(project.id)} aria-label={__('general.restore')} title={__('general.restore')}>
                            <ArchiveRestore className="h-3.5 w-3.5" />
                        </Button>
                    ) : (
                        <Button variant="outline" size="sm" onClick={() => onArchive(project.id)} aria-label={__('general.archive')} title={__('general.archive')}>
                            <Archive className="h-3.5 w-3.5" />
                        </Button>
                    )}
                    <Button variant="destructive" size="sm" onClick={() => onDelete(project.id)} aria-label={__('general.delete')} title={__('general.delete')}>
                        <Trash2 className="h-3.5 w-3.5" />
                    </Button>
                </div>
            </CardContent>
        </Card>
    );
}

export default ProjectCard;
