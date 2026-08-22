import React from 'react';
import { Head, Link } from '@inertiajs/react';
import { 
    CalendarDays, FileText, FolderKanban, ListTodo, 
    Paperclip, Plus, ArrowRight, ArrowUpRight, Sparkles 
} from 'lucide-react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { ProjectBudgetRow } from '@/Components/ProjectBudgetRow';
import { __ } from '@/lib/i18n';
import { formatDate } from '@/lib/utils';

interface ProjectItem {
    id: number;
    name: string;
    status: string;
    archived: boolean;
    percentage: number;
    date_start: string | null;
    date_end: string | null;
    budget: string;
    total_paid: string;
    hide_future_tasks: boolean;
    currency: { currency: string; symbol: string; string_format?: string } | null;
    counts: { tasks: number; reports: number; files: number };
}

interface Props {
    projects: { data: ProjectItem[]; links: any[]; meta?: any };
}

export default function ProjectsIndex({ projects }: Props) {
    const list = projects?.data ?? [];
    const totalTasks = list.reduce((s, p) => s + (p.counts?.tasks ?? 0), 0);
    const totalReports = list.reduce((s, p) => s + (p.counts?.reports ?? 0), 0);

    return (
        <AuthenticatedLayout>
            <Head title={`${__('general.my_projects')} — Musoftwares Studio`} />

            <div className="w-full bg-[#f5f5f7] text-[#1d1d1f] min-h-[calc(100vh-68px)] font-sans antialiased selection:bg-[#0071e3]/20 selection:text-[#0071e3]">
                
                {/* Hero Header */}
                <div className="w-full bg-white border-b border-black/5 py-8 px-6 sm:px-10">
                    <div className="max-w-[1400px] mx-auto flex flex-col md:flex-row md:items-center justify-between gap-6">
                        <div className="space-y-2">
                            <div className="flex items-center gap-3">
                                <span className="px-3 py-1 bg-[#0071e3]/10 text-[#0071e3] text-xs font-semibold rounded-full border border-[#0071e3]/20 flex items-center gap-1.5">
                                    <FolderKanban className="w-3.5 h-3.5" />
                                    Active Workspaces
                                </span>
                                <span className="text-xs font-sans text-[#1d1d1f]/60 font-medium">
                                    {list.length} Project Deliveries
                                </span>
                            </div>
                            <h1 className="text-2xl sm:text-3xl font-bold tracking-tight text-[#1d1d1f] font-sans">
                                {__('general.my_projects')}
                            </h1>
                            <p className="text-xs sm:text-sm text-[#1d1d1f]/60 font-sans max-w-2xl">
                                {__('general.projects_portal_intro')}
                            </p>
                        </div>

                        <div className="flex items-center gap-3 shrink-0">
                            <Link
                                href={route('client.projects.create-new')}
                                className="px-5 py-2.5 bg-[#0071e3] hover:bg-[#0077ed] text-white text-xs font-semibold rounded-[980px] transition-all flex items-center gap-2 shadow-sm shadow-blue-500/20 cursor-pointer"
                            >
                                <Plus className="w-4 h-4" />
                                <span>{__('general.new_project') || 'New Project'}</span>
                            </Link>
                        </div>
                    </div>
                </div>

                {/* Main Content */}
                <div className="max-w-[1400px] mx-auto px-6 sm:px-10 py-8 space-y-8">
                    
                    {/* View Switcher Pills */}
                    <div className="flex items-center gap-2 border-b border-black/5 pb-4">
                        <Link
                            href={route('client.projects.index')}
                            className="px-4 py-2 rounded-full text-xs font-semibold bg-[#1d1d1f] text-white shadow-xs"
                        >
                            {__('general.projects_list') || 'Projects List'}
                        </Link>
                        <Link
                            href={route('client.projects.all-projects-board.index')}
                            className="px-4 py-2 rounded-full text-xs font-semibold bg-white text-[#1d1d1f]/70 border border-black/5 hover:bg-[#f5f5f7] hover:text-[#1d1d1f] transition-colors flex items-center gap-1.5"
                        >
                            <CalendarDays className="w-3.5 h-3.5" />
                            <span>{__('general.all_projects_board') || 'All Projects Board'}</span>
                        </Link>
                    </div>

                    {/* 3-Pillar Summary Bento Grid */}
                    <div className="grid grid-cols-1 sm:grid-cols-3 gap-6">
                        
                        <div className="bg-white border border-black/5 rounded-[24px] p-6 shadow-sm flex items-center justify-between group hover:border-[#0071e3]/30 hover:shadow-md transition-all">
                            <div>
                                <span className="text-[11px] font-mono font-bold uppercase tracking-wider text-[#1d1d1f]/50 block mb-1">
                                    {__('general.total_projects')}
                                </span>
                                <span className="text-3xl font-bold text-[#1d1d1f] font-sans tracking-tight">
                                    {list.length}
                                </span>
                            </div>
                            <div className="w-12 h-12 rounded-2xl bg-[#0071e3]/10 text-[#0071e3] flex items-center justify-center">
                                <FolderKanban className="w-6 h-6" />
                            </div>
                        </div>

                        <div className="bg-white border border-black/5 rounded-[24px] p-6 shadow-sm flex items-center justify-between group hover:border-emerald-500/30 hover:shadow-md transition-all">
                            <div>
                                <span className="text-[11px] font-mono font-bold uppercase tracking-wider text-[#1d1d1f]/50 block mb-1">
                                    {__('general.open_tasks')}
                                </span>
                                <span className="text-3xl font-bold text-[#1d1d1f] font-sans tracking-tight">
                                    {totalTasks}
                                </span>
                            </div>
                            <div className="w-12 h-12 rounded-2xl bg-emerald-500/10 text-emerald-600 flex items-center justify-center">
                                <ListTodo className="w-6 h-6" />
                            </div>
                        </div>

                        <div className="bg-white border border-black/5 rounded-[24px] p-6 shadow-sm flex items-center justify-between group hover:border-amber-500/30 hover:shadow-md transition-all">
                            <div>
                                <span className="text-[11px] font-mono font-bold uppercase tracking-wider text-[#1d1d1f]/50 block mb-1">
                                    {__('general.reports')}
                                </span>
                                <span className="text-3xl font-bold text-[#1d1d1f] font-sans tracking-tight">
                                    {totalReports}
                                </span>
                            </div>
                            <div className="w-12 h-12 rounded-2xl bg-amber-500/10 text-amber-600 flex items-center justify-center">
                                <FileText className="w-6 h-6" />
                            </div>
                        </div>

                    </div>

                    {/* Projects Grid */}
                    {list.length === 0 ? (
                        <div className="bg-white border border-black/5 rounded-[24px] p-12 text-center shadow-sm max-w-xl mx-auto">
                            <div className="w-14 h-14 rounded-2xl bg-[#0071e3]/10 flex items-center justify-center text-[#0071e3] mx-auto mb-4">
                                <FolderKanban className="w-7 h-7" />
                            </div>
                            <h3 className="text-base font-bold text-[#1d1d1f] font-sans">
                                {__('general.no_projects_yet')}
                            </h3>
                            <p className="text-xs text-[#1d1d1f]/60 max-w-md mx-auto mt-1.5 mb-6 leading-relaxed">
                                {__('general.no_projects_yet_desc')}
                            </p>
                            <Link
                                href={route('client.projects.create-new')}
                                className="px-6 py-2.5 bg-[#0071e3] hover:bg-[#0077ed] text-white text-xs font-semibold rounded-[980px] shadow-sm shadow-blue-500/20 transition-all inline-flex items-center gap-2"
                            >
                                <Plus className="w-4 h-4" />
                                <span>{__('general.new_project') || 'New Project'}</span>
                            </Link>
                        </div>
                    ) : (
                        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                            {list.map((project) => {
                                const isOpen = project.status === 'open';
                                const isHold = project.status === 'hold_on';
                                return (
                                    <div
                                        key={project.id}
                                        className="bg-white border border-black/5 rounded-[24px] p-6 shadow-sm flex flex-col justify-between group hover:border-[#0071e3]/30 hover:shadow-md transition-all relative overflow-hidden"
                                    >
                                        <div>
                                            <div className="flex items-start justify-between gap-3 mb-4">
                                                <Link
                                                    href={route('client.projects.show', project.id)}
                                                    className="text-base font-bold text-[#1d1d1f] font-sans hover:text-[#0071e3] transition-colors leading-snug line-clamp-2"
                                                >
                                                    {project.name}
                                                </Link>
                                                <span className={`shrink-0 rounded-full px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-wider font-mono border capitalize ${
                                                    isOpen
                                                        ? 'bg-emerald-50 text-emerald-700 border-emerald-200/60'
                                                        : isHold
                                                            ? 'bg-amber-50 text-amber-700 border-amber-200/60'
                                                            : 'bg-[#f5f5f7] text-[#1d1d1f]/70 border-black/5'
                                                }`}>
                                                    {project.status?.replace('_', ' ')}
                                                </span>
                                            </div>

                                            {/* Progress Bar */}
                                            <div className="mb-4 space-y-1.5">
                                                <div className="flex items-center justify-between text-[11px] text-[#1d1d1f]/60 font-medium">
                                                    <span>{__('general.progress')}</span>
                                                    <span className="font-bold text-[#1d1d1f]">{Math.round(project.percentage)}%</span>
                                                </div>
                                                <div className="h-1.5 w-full overflow-hidden rounded-full bg-[#f5f5f7]">
                                                    <div
                                                        className="h-full rounded-full bg-[#0071e3] transition-all duration-500"
                                                        style={{ width: `${Math.min(100, Math.max(0, project.percentage))}%` }}
                                                    />
                                                </div>
                                            </div>

                                            <div className="mb-4 p-3 bg-[#f5f5f7] rounded-[16px] border border-black/5">
                                                <ProjectBudgetRow
                                                    budget={project.budget}
                                                    totalPaid={project.total_paid}
                                                    currency={project.currency}
                                                />
                                            </div>

                                            {project.date_start && (
                                                <p className="mb-4 text-[11px] text-[#1d1d1f]/50 font-sans">
                                                    {formatDate(project.date_start)} → {project.date_end ? formatDate(project.date_end) : 'Ongoing'}
                                                </p>
                                            )}
                                        </div>

                                        <div className="pt-4 border-t border-black/5 flex items-center justify-between">
                                            <div className="flex items-center gap-3 text-xs text-[#1d1d1f]/60">
                                                <span className="inline-flex items-center gap-1 font-medium">
                                                    <ListTodo className="h-3.5 w-3.5 text-[#0071e3]" /> {project.counts.tasks}
                                                </span>
                                                <span className="inline-flex items-center gap-1 font-medium">
                                                    <FileText className="h-3.5 w-3.5 text-amber-600" /> {project.counts.reports}
                                                </span>
                                                <span className="inline-flex items-center gap-1 font-medium">
                                                    <Paperclip className="h-3.5 w-3.5 text-slate-500" /> {project.counts.files}
                                                </span>
                                            </div>

                                            <Link
                                                href={route('client.projects.calendar.date', { project: project.id, date: new Date().toISOString().slice(0, 10) })}
                                                className="px-3 py-1.5 bg-[#f5f5f7] hover:bg-[#0071e3] text-[#1d1d1f] hover:text-white rounded-full text-xs font-semibold transition-all inline-flex items-center gap-1 cursor-pointer"
                                            >
                                                <CalendarDays className="h-3.5 w-3.5" />
                                                <span>{__('general.board')}</span>
                                            </Link>
                                        </div>
                                    </div>
                                );
                            })}
                        </div>
                    )}

                    {/* Pagination */}
                    {projects?.links && projects.links.length > 3 && (
                        <div className="flex justify-center gap-1 pt-4">
                            {projects.links.map((link, i) => (
                                <Link
                                    key={i}
                                    href={link.url ?? '#'}
                                    preserveScroll
                                    dangerouslySetInnerHTML={{ __html: link.label }}
                                    className={`px-3.5 py-1.5 rounded-lg text-xs font-semibold transition-all ${
                                        link.active
                                            ? 'bg-[#1d1d1f] text-white shadow-xs'
                                            : link.url
                                                ? 'bg-white border border-black/10 text-[#1d1d1f] hover:bg-[#f5f5f7]'
                                                : 'opacity-40 pointer-events-none bg-white text-[#1d1d1f]/40'
                                    }`}
                                />
                            ))}
                        </div>
                    )}

                </div>

            </div>
        </AuthenticatedLayout>
    );
}
