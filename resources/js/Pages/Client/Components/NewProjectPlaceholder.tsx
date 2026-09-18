import React from 'react';
import { Link } from '@inertiajs/react';
import { FolderPlus, ArrowRight, Sparkles, Calculator, ShieldCheck, CheckCircle2 } from 'lucide-react';

export interface CompletedProjectItem {
    id: number;
    name?: string;
    project_name?: string;
    delivered_at?: string;
    progress_percentage?: number;
}

interface NewProjectPlaceholderProps {
    completedProjects?: CompletedProjectItem[];
}

export const NewProjectPlaceholder: React.FC<NewProjectPlaceholderProps> = ({
    completedProjects = [],
}) => {
    const latestDelivered = completedProjects[0];

    return (
        <section className="w-full max-w-full min-w-0 rounded-2xl bg-white dark:bg-[#141416]/95 border border-black/5 dark:border-white/10 p-4 sm:p-6 lg:p-8 shadow-sm dark:shadow-2xl backdrop-blur-2xl transition-colors duration-200">
            {/* Header / Ready State */}
            <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-6 border-b border-black/5 dark:border-white/5 w-full max-w-full min-w-0">
                <div className="space-y-1.5 min-w-0 max-w-full">
                    <div className="flex flex-wrap items-center gap-2.5">
                        <span className="relative flex h-2.5 w-2.5 shrink-0">
                            <span className="animate-ping absolute inline-flex h-full w-full rounded-full bg-blue-400 opacity-75" />
                            <span className="relative inline-flex rounded-full h-2.5 w-2.5 bg-[#0071e3]" />
                        </span>
                        <h2 className="text-lg sm:text-xl font-semibold text-[#1d1d1f] dark:text-white tracking-tight font-sans break-words min-w-0">
                            Ready for Your Next Project
                        </h2>
                        <span className="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-medium bg-blue-50 text-blue-700 dark:bg-blue-500/10 dark:text-blue-400 border border-blue-200/60 dark:border-blue-500/20 shrink-0">
                            Studio Available
                        </span>
                    </div>
                    <p className="text-xs text-zinc-600 dark:text-zinc-400 max-w-2xl font-normal leading-relaxed">
                        All prior sprint deliverables are successfully finalized and signed off. Launch a new project to start engineering specifications, continuous sprint tracking, and zero-loss delivery.
                    </p>
                </div>

                <div className="flex items-center gap-2 self-start sm:self-auto shrink-0">
                    <div className="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-amber-50 dark:bg-amber-500/10 border border-amber-200/80 dark:border-amber-500/20 text-xs font-mono font-medium text-amber-900 dark:text-amber-300">
                        <Sparkles className="w-3.5 h-3.5 text-amber-600 dark:text-amber-400" />
                        <span>+100 Loyalty PTS on Scope Setup</span>
                    </div>
                </div>
            </div>

            {/* Action Grid */}
            <div className="grid grid-cols-1 md:grid-cols-2 gap-4 pt-6">
                {/* Primary: Start New Project */}
                <div className="group relative p-5 rounded-xl border border-[#0071e3]/20 dark:border-white/10 bg-gradient-to-br from-blue-50/40 via-white to-white dark:from-zinc-900/60 dark:to-zinc-950/80 hover:border-[#0071e3]/40 dark:hover:border-white/20 transition-all shadow-xs flex flex-col justify-between space-y-4">
                    <div className="space-y-2.5">
                        <div className="flex items-center gap-3">
                            <div className="p-2.5 rounded-xl bg-[#0071e3]/10 text-[#0071e3] dark:bg-blue-500/20 dark:text-blue-400">
                                <FolderPlus className="w-5 h-5" />
                            </div>
                            <div>
                                <h3 className="text-sm font-semibold text-[#1d1d1f] dark:text-white">
                                    Start New Project Workspace
                                </h3>
                                <span className="text-[11px] font-mono uppercase text-zinc-400 dark:text-zinc-500">
                                    Engineering & Architecture
                                </span>
                            </div>
                        </div>
                        <p className="text-xs text-zinc-600 dark:text-zinc-400 leading-relaxed">
                            Define functional goals, database schemas, and milestone deliverables. Our studio team and AI workspace will structure your sprints immediately.
                        </p>
                    </div>

                    <div className="pt-2">
                        <Link
                            href="/projects/create-new"
                            className="inline-flex items-center justify-center gap-2 w-full sm:w-auto px-5 py-2.5 rounded-xl bg-[#0071e3] hover:bg-[#0077ed] text-white text-xs font-semibold transition-all shadow-sm shadow-blue-500/20 active:scale-[0.98] cursor-pointer"
                        >
                            <span>Launch New Project</span>
                            <ArrowRight className="w-4 h-4" />
                        </Link>
                    </div>
                </div>

                {/* Secondary: Scope & Cost Estimator */}
                <div className="group relative p-5 rounded-xl border border-black/5 dark:border-white/10 bg-[#fbfbfd] dark:bg-zinc-950/60 hover:border-black/10 dark:hover:border-white/20 transition-all shadow-xs flex flex-col justify-between space-y-4">
                    <div className="space-y-2.5">
                        <div className="flex items-center gap-3">
                            <div className="p-2.5 rounded-xl bg-zinc-100 text-zinc-700 dark:bg-zinc-900 dark:text-zinc-300 border border-black/5 dark:border-white/5">
                                <Calculator className="w-5 h-5" />
                            </div>
                            <div>
                                <h3 className="text-sm font-semibold text-[#1d1d1f] dark:text-white">
                                    Project Scope & Cost Estimator
                                </h3>
                                <span className="text-[11px] font-mono uppercase text-zinc-400 dark:text-zinc-500">
                                    Interactive Cost Calculator
                                </span>
                            </div>
                        </div>
                        <p className="text-xs text-zinc-600 dark:text-zinc-400 leading-relaxed">
                            Estimate architecture models, engineering hours, and stack parameters before starting. Generate budget benchmarks in real time.
                        </p>
                    </div>

                    <div className="pt-2">
                        <Link
                            href="/estimator"
                            className="inline-flex items-center justify-center gap-2 w-full sm:w-auto px-5 py-2.5 rounded-xl border border-black/10 dark:border-white/10 bg-white dark:bg-zinc-900 hover:bg-[#f5f5f7] dark:hover:bg-zinc-800 text-[#1d1d1f] dark:text-white text-xs font-semibold transition-all shadow-xs active:scale-[0.98] cursor-pointer"
                        >
                            <span>Open Scope Estimator</span>
                            <ArrowRight className="w-4 h-4" />
                        </Link>
                    </div>
                </div>
            </div>

            {/* Archived / Completed Projects Notification Footer */}
            {completedProjects.length > 0 && (
                <div className="mt-6 pt-5 border-t border-black/5 dark:border-white/5 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 text-xs text-zinc-500 dark:text-zinc-400">
                    <div className="flex items-center gap-2">
                        <CheckCircle2 className="w-4 h-4 text-emerald-500 shrink-0" />
                        <span>
                            {completedProjects.length} project(s) delivered and archived
                            {latestDelivered?.name && (
                                <>
                                    {' — '}
                                    <strong className="text-zinc-800 dark:text-zinc-200 font-medium">
                                        {latestDelivered.name}
                                    </strong>{' '}
                                    verified
                                </>
                            )}
                        </span>
                    </div>
                    <Link
                        href="/projects"
                        className="inline-flex items-center gap-1 text-xs font-semibold text-[#0071e3] dark:text-sky-400 hover:underline"
                    >
                        <span>View Deliverables & Archive</span>
                        <ArrowRight className="w-3.5 h-3.5" />
                    </Link>
                </div>
            )}
        </section>
    );
};
