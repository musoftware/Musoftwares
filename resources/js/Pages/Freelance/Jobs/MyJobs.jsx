import React, { useEffect } from 'react';
import { Head, Link } from '@inertiajs/react';
import FreelanceLayout from '../Layout';
import { useFreelanceMode } from '@/Components/Freelance/FreelanceModeContext';
import { Card } from '@/Components/ui/card';
import { Badge } from '@/Components/ui/badge';
import { buttonVariants } from '@/Components/ui/button';
import { PageHeader } from '@/Components/ui/PageHeader';
import { EmptyState } from '@/Components/ui/EmptyState';
import { CurrencyDisplay as FinancialAmount } from '@/Components/ui/CurrencyDisplay';
import { cn, formatDate } from '@/lib/utils';
import { Briefcase, Plus, Clock, ChevronRight, FileText } from 'lucide-react';
import { __ } from '@/lib/i18n';

const SectionCard = ({ children, className, ...props }) => (
    <Card className={cn("shadow-sm border-slate-200/60 overflow-hidden", className)} {...props}>
        {children}
    </Card>
);

export default function MyJobs({ auth, jobs }) {
    const freelanceModeContext = useFreelanceMode();
    const globalCurrency = auth?.user?.preferred_currency || 'USD';

    useEffect(() => {
        if (freelanceModeContext && freelanceModeContext.setMode) {
            freelanceModeContext.setMode('client');
        }
    }, [freelanceModeContext]);

    const displayJobs = jobs?.data?.length ? jobs.data : [];

    return (
        <FreelanceLayout>
            <Head title={`${__('My Posted Jobs')} - ${__('Freelance')}`} />

            <PageHeader
                title={__('My Posted Jobs')}
                subtitle={__('Manage and track progress on every freelance opportunity you have published.')}
                icon={Briefcase}
                actions={
                    <Link 
                        href="/freelance/jobs/create" 
                        className={cn(buttonVariants({ variant: 'default' }), "bg-indigo-600 hover:bg-indigo-700 text-white font-semibold shadow-sm")}
                    >
                        <Plus className="h-4 w-4 mr-2" /> {__('Post New Job')}
                    </Link>
                }
            />

            {displayJobs.length === 0 ? (
                <SectionCard>
                    <EmptyState
                        icon={Briefcase}
                        title={__("You haven't posted any jobs yet")}
                        description={__("Get started by publishing your first job opportunity to hire premium experts.")}
                        action="/freelance/jobs/create"
                        actionLabel={__("Post a Job")}
                        actionIcon={Plus}
                    />
                </SectionCard>
            ) : (
                <div className="space-y-4">
                    {displayJobs.map((job) => (
                        <SectionCard 
                            key={job.id} 
                            className="group hover:border-indigo-200 transition-colors cursor-pointer"
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
                                                    {__('Posted')} {formatDate(job.created_at)}
                                                </span>
                                                <span className="flex items-center gap-1 font-medium">
                                                    <FileText className="h-3.5 w-3.5 text-slate-400" />
                                                    <strong className="text-slate-700 font-bold">{job.proposals_count || 0}</strong> {__('Proposals Received')}
                                                </span>
                                            </div>
                                        </div>
                                        
                                        <div className="flex items-center gap-3">
                                            <Badge variant="secondary" className="bg-slate-100 text-slate-700 hover:bg-slate-100 uppercase tracking-wider text-[10px] font-bold border-0">
                                                {__(job.type)}
                                            </Badge>
                                            <div className="flex items-center gap-1.5">
                                                <FinancialAmount amount={job.budget} currency={job.currency_id} size="sm" />
                                                {job.type === 'hourly' && <span className="text-xs text-slate-500 font-medium">/ {__('hr')}</span>}
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
                        </SectionCard>
                    ))}
                </div>
            )}
        </FreelanceLayout>
    );
}
