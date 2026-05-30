import React, { useState, useEffect } from 'react';
import { Head, Link, router, usePage } from '@inertiajs/react';
import FreelanceLayout from '../Layout';
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

const SectionCard = ({ children, className, ...props }: { children: React.ReactNode, className?: string, [x: string]: any }) => <Card className={cn("shadow-sm border-gray-200 overflow-hidden", className)} {...props}>{children}</Card>;

function BrowseJobsContent({ jobs }: any) {
    const { auth } = usePage().props as any;
    const { mode, setMode } = useFreelanceMode();
    
    // Initialize filters and sort from URL parameters
    const urlParams = new URLSearchParams(typeof window !== 'undefined' ? window.location.search : '');
    const [searchTerm, setSearchTerm] = useState(urlParams.get('search') || '');
    const [typeFilter, setTypeFilter] = useState(urlParams.get('type') || 'all');
    const [sortBy, setSortBy] = useState(urlParams.get('sort') || 'newest');

    const typeLabels: Record<string, string> = {
        all: __('All Types'),
        fixed: __('Fixed Price'),
        hourly: __('Hourly Rate')
    };

    const sortLabels: Record<string, string> = {
        newest: __('Newest First'),
        budget_high: __('Highest Budget'),
        budget_low: __('Lowest Budget')
    };

    useEffect(() => {
        if (mode !== 'freelancer') {
            setMode('freelancer');
        }
    }, [mode, setMode]);

    const displayJobs = jobs?.data?.length ? jobs.data : [];
    const globalCurrency = auth?.user?.preferred_currency || 'USD';

    const handleSearch = (e: React.FormEvent) => {
        e.preventDefault();
        router.get('/freelance/jobs/browse', {
            search: searchTerm || undefined,
            type: typeFilter !== 'all' ? typeFilter : undefined,
            sort: sortBy !== 'newest' ? sortBy : undefined
        }, { preserveState: true });
    };

    const updateFilters = (newType: string, newSort: string) => {
        router.get('/freelance/jobs/browse', {
            search: searchTerm || undefined,
            type: newType !== 'all' ? newType : undefined,
            sort: newSort !== 'newest' ? newSort : undefined
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
            <Head title={`${__('Browse Jobs')} - ${__('Freelance')}`} />

            <PageHeader
                title={__('Find Work')}
                subtitle={__('Discover the perfect project that matches your skills in our unified marketplace.')}
                icon={Briefcase}
                actions={
                    <Link href="/freelance/jobs/my-jobs" className={cn(buttonVariants({ variant: 'outline' }), "shadow-sm bg-white")}>
                        {__('My Saved Jobs')}
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
                        placeholder={__('Search for jobs, skills, or clients...')} 
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
                            <SelectValue placeholder={__('Job Type')}>
                                {typeLabels[typeFilter] || __('Job Type')}
                            </SelectValue>
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">{__('All Types')}</SelectItem>
                            <SelectItem value="fixed">{__('Fixed Price')}</SelectItem>
                            <SelectItem value="hourly">{__('Hourly Rate')}</SelectItem>
                        </SelectContent>
                    </Select>
                </div>

                <Button 
                    type="submit" 
                    className="h-11 rounded-lg px-6 text-sm font-semibold bg-indigo-600 hover:bg-indigo-700 text-white shadow-sm shrink-0"
                >
                    {__('Search')}
                </Button>
            </form>

            <div className="grid grid-cols-1 lg:grid-cols-4 gap-6">
                {/* Filters Sidebar */}
                <div className="space-y-6">
                    <SectionCard className="p-5">
                        <h3 className="text-sm font-semibold text-slate-900 mb-4 flex items-center gap-2">
                            <SlidersHorizontal className="h-4 w-4" /> {__('Filters')}
                        </h3>
                        <div className="space-y-5">
                            <div className="space-y-3">
                                <label className="text-xs font-semibold uppercase tracking-wider text-slate-500">{__('Experience Level')}</label>
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
                                <label className="text-xs font-semibold uppercase tracking-wider text-slate-500">{__('Client History')}</label>
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
                    </SectionCard>
                </div>

                {/* Job Listings */}
                <div className="lg:col-span-3 space-y-4">
                    <div className="flex items-center justify-between mb-2">
                        <h2 className="text-sm font-semibold text-slate-900">
                            {jobs?.total || 0} {__('Open Opportunities')}
                        </h2>
                        <Select value={sortBy} onValueChange={handleSortChange}>
                            <SelectTrigger className="w-[180px] text-sm shadow-sm border-slate-200" style={{ height: '2.25rem' }}>
                                <SelectValue placeholder={__('Sort By')}>
                                    {sortLabels[sortBy] || __('Sort By')}
                                </SelectValue>
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="newest">{__('Newest First')}</SelectItem>
                                <SelectItem value="budget_high">{__('Highest Budget')}</SelectItem>
                                <SelectItem value="budget_low">{__('Lowest Budget')}</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>

                    {!displayJobs.length ? (
                        <SectionCard>
                            <EmptyState
                                icon={Search}
                                title={__('No jobs found')}
                                description={__('Try adjusting your search or filter criteria to find more opportunities.')}
                                actionLabel={__('Clear Filters')}
                                actionIcon={Search}
                                onClick={() => router.get('/freelance/jobs/browse')}
                            />
                        </SectionCard>
                    ) : (
                        displayJobs.map((job: any) => (
                            <SectionCard key={job.id} className="group hover:border-indigo-200 transition-colors cursor-pointer" onClick={() => router.get(`/freelance/jobs/${job.id}`)}>
                                <div className="p-6 flex flex-col sm:flex-row sm:items-start justify-between gap-4">
                                    <div className="space-y-3 flex-1">
                                        <div className="space-y-1">
                                            <h3 className="text-base font-semibold text-slate-900 group-hover:text-indigo-600 transition-colors leading-tight">
                                                {job.title}
                                            </h3>
                                            <div className="flex flex-wrap items-center gap-x-4 gap-y-2 text-xs text-slate-500">
                                                <span className="flex items-center gap-1 font-medium"><Clock className="h-3.5 w-3.5 text-slate-400" /> {__('Posted')} {formatDate(job.created_at)}</span>
                                                <span className="flex items-center gap-1 font-medium"><MapPin className="h-3.5 w-3.5 text-slate-400" /> {__('Remote')}</span>
                                            </div>
                                        </div>
                                        
                                        <div className="flex items-center gap-3">
                                            <Badge variant="secondary" className="bg-slate-100 text-slate-700 hover:bg-slate-100 uppercase tracking-wider text-[10px] font-bold border-0">
                                                {__(job.type)}
                                            </Badge>
                                            <div className="flex items-center gap-1.5">
                                                <FinancialAmount amount={job.budget} currency={globalCurrency} size="sm" />
                                                {job.type === 'hourly' && <span className="text-xs text-slate-500 font-medium">/ {__('hr')}</span>}
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
                            </SectionCard>
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

export default function BrowseJobs({ jobs }: any) {
    return (
        <FreelanceLayout>
            <BrowseJobsContent jobs={jobs} />
        </FreelanceLayout>
    );
}
