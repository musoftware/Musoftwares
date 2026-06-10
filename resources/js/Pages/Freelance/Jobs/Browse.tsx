import React, { useState, useEffect } from 'react';
import { Link, router, usePage } from '@inertiajs/react';
import PublicLayout from '../PublicLayout';
import { Card } from '@/Components/ui/card';
import { Badge } from '@/Components/ui/badge';
import { Button, buttonVariants } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';
import { formatDate } from '@/lib/utils';
import { Search, SlidersHorizontal, MapPin, Clock, Briefcase, Plus, ChevronRight } from 'lucide-react';
import { useFreelanceMode } from '@/Components/Freelance/FreelanceModeContext';
import { PageHeader } from '@/Components/ui/PageHeader';
import { EmptyState } from '@/Components/ui/EmptyState';
import { CurrencyDisplay as FinancialAmount } from '@/Components/ui/CurrencyDisplay';
import { cn } from '@/lib/utils';
import { __ } from '@/lib/i18n';
import { SeoHead } from '@/Components/ui/SeoHead';

import { FreelanceCard } from '@/Components/Freelance/ui/FreelanceCard';
import { formatMoney } from '@/lib/utils';

function BrowseJobsContent({ jobs: initialJobs, userCurrency }: any) {
    const { auth } = usePage().props as any;
    const { mode, setMode } = useFreelanceMode();
    const [jobs, setJobs] = useState(initialJobs);

    useEffect(() => {
        setJobs(initialJobs);
    }, [initialJobs]);
    
    // Initialize filters and sort from URL parameters
    const urlParams = new URLSearchParams(typeof window !== 'undefined' ? window.location.search : '');
    const [searchTerm, setSearchTerm] = useState(urlParams.get('search') || '');
    const [typeFilter, setTypeFilter] = useState(urlParams.get('type') || 'all');
    const [sortBy, setSortBy] = useState(urlParams.get('sort') || 'newest');
    const [budgetMin, setBudgetMin] = useState(urlParams.get('budget_min') || '');
    const [budgetMax, setBudgetMax] = useState(urlParams.get('budget_max') || '');

    const typeLabels: Record<string, string> = {
        all: __('general.all_types'),
        fixed: __('general.fixed_price'),
        hourly: __('general.hourly_rate')
    };

    const sortLabels: Record<string, string> = {
        newest: __('general.newest_first'),
        budget_high: __('erp.highest_budget'),
        budget_low: __('erp.lowest_budget')
    };

    useEffect(() => {
        if (mode !== 'freelancer') {
            setMode('freelancer');
        }
    }, [mode, setMode]);

    const displayJobs = jobs?.data?.length ? jobs.data : [];

    const handleSearch = (e: React.FormEvent) => {
        e.preventDefault();
        router.get('/freelance/jobs/browse', {
            search: searchTerm || undefined,
            type: typeFilter !== 'all' ? typeFilter : undefined,
            sort: sortBy !== 'newest' ? sortBy : undefined,
            budget_min: budgetMin || undefined,
            budget_max: budgetMax || undefined
        }, { preserveState: true });
    };

    const updateFilters = (newType: string, newSort: string, minBudget?: string, maxBudget?: string) => {
        router.get('/freelance/jobs/browse', {
            search: searchTerm || undefined,
            type: newType !== 'all' ? newType : undefined,
            sort: newSort !== 'newest' ? newSort : undefined,
            budget_min: minBudget !== undefined ? minBudget : (budgetMin || undefined),
            budget_max: maxBudget !== undefined ? maxBudget : (budgetMax || undefined)
        }, { preserveState: true });
    };

    const handleTypeChange = (val: string | null) => {
        const newType = val || 'all';
        setTypeFilter(newType);
        updateFilters(newType, sortBy);
    };

    const handleSortChange = (val: string | null) => {
        const newSort = val || 'newest';
        setSortBy(newSort);
        updateFilters(typeFilter, newSort);
    };

    return (
        <>
            <SeoHead 
                title={`${__('freelance.seo.browse_title', undefined, 'Browse Jobs')} - ${__('freelance.freelance')}`} 
                description={__('freelance.seo.browse_desc', undefined, 'Discover open freelance opportunities and find the perfect project for your skills.')}
                canonicalUrl={typeof window !== 'undefined' ? window.location.origin + window.location.pathname : undefined}
                jsonLd={{
                    "@context": "https://schema.org",
                    "@type": "ItemList",
                    "itemListElement": displayJobs.map((job: any, index: number) => ({
                        "@type": "ListItem",
                        "position": index + 1,
                        "url": typeof window !== 'undefined' ? `${window.location.origin}/freelance/jobs/${job.id}` : ''
                    }))
                }}
            />

            <PageHeader
                title={__('general.find_work')}
                subtitle={__('erp.discover_the_perfect_project_that')}
                icon={Briefcase}
                actions={
                    <Link href="/freelance/jobs/my-jobs" className={cn(buttonVariants({ variant: 'outline' }), "shadow-sm bg-white")}>
                        {__('freelance.my_saved_jobs')}
                    </Link>
                }
            />

            {/* Search Bar */}
            <form 
                onSubmit={handleSearch} 
                className="flex flex-col sm:flex-row items-stretch sm:items-center bg-white border border-slate-200 rounded-xl shadow-sm hover:border-slate-300 transition-all duration-200 p-1.5 gap-2 sm:gap-0 focus-within:ring-4 focus-within:ring-indigo-500/10 focus-within:border-indigo-500 w-full mb-6"
            >
                <div className="relative flex-1 flex items-center min-w-0 pl-3">
                    <Search className="h-5 w-5 text-slate-400 shrink-0" />
                    <input 
                        type="text"
                        placeholder={__('erp.search_for_jobs_skills_or')} 
                        className="w-full bg-transparent border-0 ring-0 focus:ring-0 focus:outline-none pl-3 text-sm text-slate-900 placeholder:text-slate-400 h-11"
                        value={searchTerm}
                        onChange={(e) => setSearchTerm(e.target.value)}
                    />
                </div>
                
                <div className="hidden sm:block w-px h-6 bg-slate-200 mx-2 shrink-0" />

                <div className="w-full sm:w-44 shrink-0">
                    <Select value={typeFilter} onValueChange={handleTypeChange}>
                        <SelectTrigger 
                            className="border-0 bg-transparent hover:bg-slate-50 text-slate-700 shadow-none w-full text-sm font-medium focus-visible:ring-0 focus-visible:ring-offset-0 focus-visible:border-0 rounded-lg px-3 py-2 cursor-pointer transition-colors"
                            style={{ height: '2.75rem' }}
                        >
                            <SelectValue placeholder={__('freelance.job_type')}>
                                {typeLabels[typeFilter] || __('freelance.job_type')}
                            </SelectValue>
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">{__('general.all_types')}</SelectItem>
                            <SelectItem value="fixed">{__('general.fixed_price')}</SelectItem>
                            <SelectItem value="hourly">{__('general.hourly_rate')}</SelectItem>
                        </SelectContent>
                    </Select>
                </div>

                <Button 
                    type="submit" 
                    className="h-11 rounded-lg px-6 text-sm font-semibold bg-indigo-600 hover:bg-indigo-700 text-white shadow-sm shrink-0"
                >
                    {__('general.search')}
                </Button>
            </form>

            <div className="grid grid-cols-1 lg:grid-cols-4 gap-6">
                {/* Filters Sidebar */}
                <div className="space-y-6">
                    <FreelanceCard className="p-5">
                        <h3 className="text-sm font-semibold text-slate-900 mb-4 flex items-center gap-2">
                            <SlidersHorizontal className="h-4 w-4" /> {__('general.filters')}
                        </h3>
                        <div className="space-y-5">
                            <div className="space-y-3">
                                <label className="text-xs font-semibold uppercase tracking-wider text-slate-500">{__('general.experience_level')}</label>
                                <div className="space-y-2.5">
                                    {['Entry Level', 'Intermediate', 'Expert'].map(level => (
                                        <label key={level} className="flex items-center gap-2.5 cursor-pointer group">
                                            <input type="checkbox" className="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 w-4 h-4" />
                                            <span className="text-sm text-slate-700 group-hover:text-slate-900">{__(level)}</span>
                                        </label>
                                    ))}
                                </div>
                            </div>
                            <div className="w-full h-px bg-slate-100"></div>
                            <div className="space-y-3">
                                <label className="text-xs font-semibold uppercase tracking-wider text-slate-500">{__('erp.budget')}</label>
                                <div className="flex items-center gap-2">
                                    <Input 
                                        type="number" 
                                        placeholder={__('erp.min')} 
                                        className="h-9 text-sm" 
                                        value={budgetMin}
                                        onChange={(e) => setBudgetMin(e.target.value)}
                                        onBlur={(e) => updateFilters(typeFilter, sortBy, e.target.value, budgetMax)}
                                    />
                                    <span className="text-slate-400">-</span>
                                    <Input 
                                        type="number" 
                                        placeholder={__('erp.max')} 
                                        className="h-9 text-sm" 
                                        value={budgetMax}
                                        onChange={(e) => setBudgetMax(e.target.value)}
                                        onBlur={(e) => updateFilters(typeFilter, sortBy, budgetMin, e.target.value)}
                                    />
                                </div>
                            </div>
                            <div className="w-full h-px bg-slate-100"></div>
                            <div className="space-y-3">
                                <label className="text-xs font-semibold uppercase tracking-wider text-slate-500">{__('erp.client_history')}</label>
                                <div className="space-y-2.5">
                                    {['No hires', '1 to 9 hires', '10+ hires'].map(history => (
                                        <label key={history} className="flex items-center gap-2.5 cursor-pointer group">
                                            <input type="checkbox" className="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 w-4 h-4" />
                                            <span className="text-sm text-slate-700 group-hover:text-slate-900">{__(history)}</span>
                                        </label>
                                    ))}
                                </div>
                            </div>
                        </div>
                    </FreelanceCard>
                </div>

                {/* Job Listings */}
                <div className="lg:col-span-3 space-y-4">
                    <div className="flex items-center justify-between mb-2">
                        <h2 className="text-sm font-semibold text-slate-900">
                            {jobs?.total || 0} {__('general.open_opportunities')}
                        </h2>
                        <Select value={sortBy} onValueChange={handleSortChange}>
                            <SelectTrigger className="w-[180px] text-sm shadow-sm border-slate-200" style={{ height: '2.25rem' }}>
                                <SelectValue placeholder={__('general.sort_by')}>
                                    {sortLabels[sortBy] || __('general.sort_by')}
                                </SelectValue>
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="newest">{__('general.newest_first')}</SelectItem>
                                <SelectItem value="budget_high">{__('erp.highest_budget')}</SelectItem>
                                <SelectItem value="budget_low">{__('erp.lowest_budget')}</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>

                    {!displayJobs.length ? (
                        <FreelanceCard>
                            <EmptyState
                                icon={Search}
                                title={__('freelance.no_jobs_found')}
                                description={__('general.try_adjusting_your_search_or')}
                                actionLabel={__('general.clear_filters')}
                                actionIcon={Search}
                                onClick={() => router.get('/freelance/jobs/browse')}
                            />
                        </FreelanceCard>
                    ) : (
                        displayJobs.map((job: any) => (
                            <FreelanceCard key={job.id} interactive className="group hover:border-indigo-200 transition-colors cursor-pointer" onClick={() => router.get(`/freelance/jobs/${job.id}`)}>
                                <div className="p-6 flex flex-col sm:flex-row sm:items-start justify-between gap-4">
                                    <div className="space-y-3 flex-1">
                                        <div className="space-y-1">
                                            <h3 className="text-base font-semibold text-slate-900 group-hover:text-indigo-600 transition-colors leading-tight">
                                                {job.title}
                                            </h3>
                                            <div className="flex flex-wrap items-center gap-x-4 gap-y-2 text-xs text-slate-500">
                                                <span className="flex items-center gap-1 font-medium"><Clock className="h-3.5 w-3.5 text-slate-400" /> {__('general.posted')} {formatDate(job.created_at)}</span>
                                                <span className="flex items-center gap-1 font-medium"><MapPin className="h-3.5 w-3.5 text-slate-400" /> {__('general.remote')}</span>
                                            </div>
                                        </div>
                                        
                                        <div className="flex items-center gap-3">
                                            <Badge variant="secondary" className="bg-slate-100 text-slate-700 hover:bg-slate-100 uppercase tracking-wider text-[10px] font-bold border-0">
                                                {__(job.type)}
                                            </Badge>
                                            <div className="flex items-center gap-1.5">
                                                <span className="font-mono text-sm font-semibold">{job.budget !== null && job.budget !== undefined ? formatMoney(job.budget, userCurrency || job.currency) : `${job.budget_points} ${__('freelance.pts', undefined, 'pts')}`}</span>
                                                {job.type === 'hourly' && <span className="text-xs text-slate-500 font-medium">/ {__('general.hr')}</span>}
                                            </div>
                                        </div>

                                        <p className="text-sm text-slate-600 line-clamp-2 leading-relaxed">
                                            {job.description}
                                        </p>

                                        <div className="flex flex-wrap gap-1.5 pt-2">
                                            {job.skills?.map((skill: any) => (
                                                <Badge key={skill.id} variant="outline" className="bg-slate-50 border-slate-200 text-slate-600 font-medium text-[11px] rounded-md px-2">
                                                    {skill.name}
                                                </Badge>
                                            ))}
                                        </div>
                                    </div>
                                    <div className="shrink-0 flex items-center self-center sm:self-start mt-2 sm:mt-0 opacity-0 group-hover:opacity-100 transition-opacity">
                                        <ChevronRight className="h-5 w-5 text-indigo-400" />
                                    </div>
                                </div>
                            </FreelanceCard>
                        ))
                    )}
                    
                    {/* Pagination */}
                    {jobs?.links && jobs.data?.length > 0 && (
                        <div className="flex justify-center gap-2 pt-8">
                            {jobs.links.map((link: any, i: number) => (
                                link.url ? (
                                    <Link
                                        key={i}
                                        href={link.url}
                                        className={cn(
                                            'px-3 py-1.5 rounded-lg text-xs font-semibold border transition-colors',
                                            link.active
                                                ? 'bg-indigo-600 text-white border-indigo-600'
                                                : 'bg-white text-slate-600 border-slate-200 hover:border-indigo-300'
                                        )}
                                        dangerouslySetInnerHTML={{ __html: link.label }}
                                    />
                                ) : (
                                    <span
                                        key={i}
                                        className="px-3 py-1.5 rounded-lg text-xs font-semibold border border-slate-100 bg-slate-50 text-slate-400 cursor-not-allowed"
                                        dangerouslySetInnerHTML={{ __html: link.label }}
                                    />
                                )
                            ))}
                        </div>
                    )}
                </div>
            </div>
        </>
    );
}

export default function BrowseJobs({ jobs, userCurrency }: any) {
    return (
        <PublicLayout>
            <BrowseJobsContent jobs={jobs} userCurrency={userCurrency} />
        </PublicLayout>
    );
}
