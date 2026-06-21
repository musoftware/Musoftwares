import React, { useEffect } from 'react';
import { Head, Link } from '@inertiajs/react';
import FreelanceLayout from '../Layout';
import { useFreelanceMode } from '@/Components/Freelance/FreelanceModeContext';
import { Card } from '@/Components/ui/card';
import { Badge } from '@/Components/ui/badge';
import { buttonVariants } from '@/Components/ui/button';
import { PageHeader } from '@/Components/ui/PageHeader';
import { EmptyState } from '@/Components/ui/EmptyState';
import { formatMoney } from '@/lib/utils';
import { CurrencyDisplay as FinancialAmount } from '@/Components/ui/CurrencyDisplay';
import { cn, formatDate } from '@/lib/utils';
import { Briefcase, Plus, Clock, ChevronRight, FileText } from 'lucide-react';
import { __ } from '@/lib/i18n';

import { FreelanceCard } from '@/Components/Freelance/ui/FreelanceCard';

export default function MyJobs({ jobs, userCurrency }) {
    const freelanceModeContext = useFreelanceMode();

    useEffect(() => {
        if (freelanceModeContext && freelanceModeContext.setMode) {
            freelanceModeContext.setMode('client');
        }
    }, [freelanceModeContext]);

    const displayJobs = jobs?.data?.length ? jobs.data : [];

    return (
        <FreelanceLayout>
            <Head title={`${__('freelance.my_posted_jobs')} - ${__('freelance.freelance')}`} />

            <PageHeader
                title={__('freelance.my_posted_jobs')}
                subtitle={__('freelance.manage_and_track_progress_on')}
                icon={Briefcase}
                actions={
                    <Link 
                        href="/freelance/jobs/create" 
                        className={cn(buttonVariants({ variant: 'default' }), "bg-indigo-600 hover:bg-indigo-700 text-white font-semibold shadow-sm")}
                    >
                        <Plus className="h-4 w-4 me-2" /> {__('freelance.post_new_job')}
                    </Link>
                }
            />

            {displayJobs.length === 0 ? (
                <FreelanceCard>
                    <EmptyState
                        icon={Briefcase}
                        title={__("You haven't posted any jobs yet")}
                        description={__("freelance.get_started_by_publishing_your")}
                        action="/freelance/jobs/create"
                        actionLabel={__("freelance.post_a_job")}
                        actionIcon={Plus}
                    />
                </FreelanceCard>
            ) : (
                <div className="space-y-4">
                    {displayJobs.map((job) => (
                        <FreelanceCard 
                            key={job.id} 
                            interactive
                        >
                            <Link href={`/freelance/jobs/${job.id}`} className="block">
                                <div className="p-6 flex flex-col sm:flex-row sm:items-start justify-between gap-4">
                                    <div className="space-y-3 flex-1">
                                        <div className="space-y-1">
                                            <div className="flex items-center gap-3">
                                                <h3 className="text-base font-semibold text-slate-900 group-hover:text-indigo-600 transition-colors leading-tight">
                                                    {job.title}
                                                </h3>
                                                <Badge 
                                                    variant="outline"
                                                    className={cn(
                                                        "text-[10px] font-bold uppercase tracking-wider",
                                                        job.status === 'open' 
                                                            ? 'bg-emerald-50 text-emerald-700 border-emerald-200' 
                                                            : job.status === 'in_progress'
                                                            ? 'bg-blue-50 text-blue-700 border-blue-200'
                                                            : 'bg-slate-50 text-slate-600 border-slate-200'
                                                    )}
                                                >
                                                    {__(job.status.replace('_', ' '))}
                                                </Badge>
                                            </div>
                                            
                                            <div className="flex flex-wrap items-center gap-x-4 gap-y-2 text-xs text-slate-500">
                                                <span className="flex items-center gap-1 font-medium">
                                                    <Clock className="h-3.5 w-3.5 text-slate-400" /> 
                                                    {__('general.posted')} {formatDate(job.created_at)}
                                                </span>
                                                <span className="flex items-center gap-1 font-medium">
                                                    <FileText className="h-3.5 w-3.5 text-slate-400" />
                                                    <strong className="text-slate-700 font-bold">{job.proposals_count || 0}</strong> {__('freelance.proposals_received')}
                                                </span>
                                            </div>
                                        </div>
                                        
                                        <div className="flex items-center gap-3">
                                            <Badge variant="secondary" className="bg-slate-100 text-slate-700 hover:bg-slate-100 uppercase tracking-wider text-[10px] font-bold border-0">
                                                {__(job.type)}
                                            </Badge>
                                            <div className="flex items-center gap-1.5">
                                                <span className="font-mono text-sm font-semibold">{job.budget !== null && job.budget !== undefined ? formatMoney(job.budget, userCurrency) : `${job.budget_points} ${__('freelance.pts', undefined, 'pts')}`}</span>
                                                {job.type === 'hourly' && <span className="text-xs text-slate-500 font-medium">/ {__('general.hr')}</span>}
                                            </div>
                                        </div>

                                        <p className="text-sm text-slate-600 line-clamp-2 leading-relaxed">
                                            {job.description}
                                        </p>
                                    </div>
                                    <div className="shrink-0 flex items-center self-center sm:self-start mt-2 sm:mt-0 opacity-0 group-hover:opacity-100 transition-opacity">
                                        <ChevronRight className="h-5 w-5 text-indigo-400" />
                                    </div>
                                </div>
                            </Link>
                        </FreelanceCard>
                    ))}
                </div>
            )}
        </FreelanceLayout>
    );
}
