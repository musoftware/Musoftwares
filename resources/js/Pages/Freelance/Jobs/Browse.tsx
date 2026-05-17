import React, { useState, useEffect } from 'react';
import { Head, Link, router, usePage } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
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

// Unified Layout Structure
const AppLayout = AuthenticatedLayout;
const AppPage = ({ children }: { children: React.ReactNode }) => <div className="max-w-[1200px] mx-auto px-4 py-8 space-y-6">{children}</div>;
const SectionCard = ({ children, className, ...props }: { children: React.ReactNode, className?: string, [x: string]: any }) => <Card className={cn("shadow-sm border-gray-200 overflow-hidden", className)} {...props}>{children}</Card>;

export default function BrowseJobs({ jobs }: any) {
    const { auth } = usePage().props as any;
    const { mode, setMode } = useFreelanceMode();
    const [searchTerm, setSearchTerm] = useState('');
    const [typeFilter, setTypeFilter] = useState('all');

    useEffect(() => {
        if (mode !== 'freelancer') {
            setMode('freelancer');
        }
    }, []);

    const displayJobs = jobs?.data?.length ? jobs.data : [];
    const globalCurrency = auth?.user?.preferred_currency || 'USD';

    const handleSearch = (e: React.FormEvent) => {
        e.preventDefault();
        router.get('/freelance/jobs/browse', { search: searchTerm, type: typeFilter !== 'all' ? typeFilter : undefined }, { preserveState: true });
    };

    return (
        <AppLayout header="Browse Jobs">
            <Head title="Browse Jobs" />

            <AppPage>
                <PageHeader
                    title="Find Work"
                    subtitle="Discover the perfect project that matches your skills in our unified marketplace."
                    icon={Briefcase}
                    actions={
                        <Link href="/freelance/jobs/my-jobs" className={cn(buttonVariants({ variant: 'outline' }), "shadow-sm bg-white")}>
                            My Saved Jobs
                        </Link>
                    }
                />

                {/* Search Bar */}
                <SectionCard className="p-0 border-0 rounded-xl">
                    <form onSubmit={handleSearch} className="flex flex-col sm:flex-row gap-0">
                        <div className="relative flex-1">
                            <Search className="absolute left-4 top-1/2 -translate-y-1/2 h-5 w-5 text-slate-400" />
                            <Input 
                                placeholder="Search for jobs, skills, or clients..." 
                                className="pl-12 border border-r-0 focus-visible:ring-indigo-500 shadow-none h-14 rounded-l-xl rounded-r-none text-sm"
                                value={searchTerm}
                                onChange={(e) => setSearchTerm(e.target.value)}
                            />
                        </div>
                        <div className="w-full sm:w-48 shrink-0 relative">
                            <Select value={typeFilter} onValueChange={(val) => setTypeFilter(val || 'all')}>
                                <SelectTrigger className="border border-r-0 focus:ring-indigo-500 shadow-none h-14 rounded-none bg-white w-full text-sm">
                                    <SelectValue placeholder="Job Type" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">All Types</SelectItem>
                                    <SelectItem value="fixed">Fixed Price</SelectItem>
                                    <SelectItem value="hourly">Hourly Rate</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                        <Button type="submit" className="h-14 rounded-l-none rounded-r-xl px-8 text-sm font-semibold bg-indigo-600 hover:bg-indigo-700 shadow-none">
                            Search
                        </Button>
                    </form>
                </SectionCard>

                <div className="grid grid-cols-1 lg:grid-cols-4 gap-6">
                    {/* Filters Sidebar */}
                    <div className="space-y-6">
                        <SectionCard className="p-5">
                            <h3 className="text-sm font-semibold text-slate-900 mb-4 flex items-center gap-2">
                                <SlidersHorizontal className="h-4 w-4" /> Filters
                            </h3>
                            <div className="space-y-5">
                                <div className="space-y-3">
                                    <label className="text-xs font-semibold uppercase tracking-wider text-slate-500">Experience Level</label>
                                    <div className="space-y-2.5">
                                        {['Entry Level', 'Intermediate', 'Expert'].map(level => (
                                            <label key={level} className="flex items-center gap-2.5 cursor-pointer group">
                                                <input type="checkbox" className="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 w-4 h-4" />
                                                <span className="text-sm text-slate-700 group-hover:text-slate-900">{level}</span>
                                            </label>
                                        ))}
                                    </div>
                                </div>
                                <div className="w-full h-px bg-slate-100"></div>
                                <div className="space-y-3">
                                    <label className="text-xs font-semibold uppercase tracking-wider text-slate-500">Client History</label>
                                    <div className="space-y-2.5">
                                        {['No hires', '1 to 9 hires', '10+ hires'].map(history => (
                                            <label key={history} className="flex items-center gap-2.5 cursor-pointer group">
                                                <input type="checkbox" className="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 w-4 h-4" />
                                                <span className="text-sm text-slate-700 group-hover:text-slate-900">{history}</span>
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
                                {jobs?.total || 0} Open Opportunities
                            </h2>
                            <Select defaultValue="newest">
                                <SelectTrigger className="w-[180px] h-9 text-sm shadow-sm border-slate-200">
                                    <SelectValue placeholder="Sort By" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="newest">Newest First</SelectItem>
                                    <SelectItem value="budget_high">Highest Budget</SelectItem>
                                    <SelectItem value="budget_low">Lowest Budget</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>

                        {!displayJobs.length ? (
                            <SectionCard>
                                <EmptyState
                                    icon={Search}
                                    title="No jobs found"
                                    description="Try adjusting your search or filter criteria to find more opportunities."
                                    actionLabel="Clear Filters"
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
                                                    <span className="flex items-center gap-1 font-medium"><Clock className="h-3.5 w-3.5 text-slate-400" /> Posted {formatDate(job.created_at)}</span>
                                                    <span className="flex items-center gap-1 font-medium"><MapPin className="h-3.5 w-3.5 text-slate-400" /> Remote</span>
                                                </div>
                                            </div>
                                            
                                            <div className="flex items-center gap-3">
                                                <Badge variant="secondary" className="bg-slate-100 text-slate-700 hover:bg-slate-100 uppercase tracking-wider text-[10px] font-bold border-0">
                                                    {job.type}
                                                </Badge>
                                                <div className="flex items-center gap-1.5">
                                                    <FinancialAmount amount={job.budget} currency={globalCurrency} size="sm" />
                                                    {job.type === 'hourly' && <span className="text-xs text-slate-500 font-medium">/ hr</span>}
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
                        
                        {jobs?.links && jobs.data.length > 0 && (
                            <div className="flex justify-center mt-8">
                            </div>
                        )}
                    </div>
                </div>
            </AppPage>
        </AppLayout>
    );
}
