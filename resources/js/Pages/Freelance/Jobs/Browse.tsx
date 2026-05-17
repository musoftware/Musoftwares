import React, { useState, useEffect } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import FreelanceLayout from '../Layout';
import { Card, CardContent, CardHeader, CardTitle, CardDescription, CardFooter } from '@/Components/ui/card';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';
import { formatMoney, formatDate } from '@/lib/utils';
import { Search, SlidersHorizontal, MapPin, Clock, DollarSign, Briefcase } from 'lucide-react';
import { useFreelanceMode } from '@/Components/Freelance/FreelanceModeContext';

export default function BrowseJobs({ auth, jobs }: any) {
    const { mode, setMode } = useFreelanceMode();
    const [searchTerm, setSearchTerm] = useState('');
    const [typeFilter, setTypeFilter] = useState('all');

    useEffect(() => {
        if (mode !== 'freelancer') {
            setMode('freelancer');
        }
    }, []);

    const displayJobs = jobs?.data?.length ? jobs.data : [];

    const handleSearch = (e: React.FormEvent) => {
        e.preventDefault();
        router.get('/freelance/jobs/browse', { search: searchTerm, type: typeFilter !== 'all' ? typeFilter : undefined }, { preserveState: true });
    };

    return (
        <FreelanceLayout auth={auth} clean={true}>
            <Head title="Browse Jobs" />

            <div className="space-y-6 max-w-6xl mx-auto pb-12">
                <div className="flex flex-col gap-2">
                    <h1 className="text-3xl font-bold tracking-tight text-slate-900">Find Work</h1>
                    <p className="text-slate-500">Discover the perfect project that matches your skills.</p>
                </div>

                {/* Search Bar */}
                <Card className="shadow-sm border-slate-200/60 overflow-hidden">
                    <CardContent className="p-0">
                        <form onSubmit={handleSearch} className="flex flex-col sm:flex-row gap-0">
                            <div className="relative flex-1">
                                <Search className="absolute left-4 top-1/2 -translate-y-1/2 h-5 w-5 text-slate-400" />
                                <Input 
                                    placeholder="Search for jobs, skills, or clients..." 
                                    className="pl-12 border-0 focus-visible:ring-0 shadow-none h-14 rounded-none text-base"
                                    value={searchTerm}
                                    onChange={(e) => setSearchTerm(e.target.value)}
                                />
                            </div>
                            <div className="w-px bg-slate-200 hidden sm:block"></div>
                            <div className="w-full sm:w-48 bg-white shrink-0">
                                <Select value={typeFilter} onValueChange={(val) => setTypeFilter(val)}>
                                    <SelectTrigger className="border-0 focus:ring-0 shadow-none h-14 rounded-none bg-white w-full">
                                        <SelectValue placeholder="Job Type" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="all">All Types</SelectItem>
                                        <SelectItem value="fixed">Fixed Price</SelectItem>
                                        <SelectItem value="hourly">Hourly Rate</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                            <Button type="submit" className="h-14 rounded-none px-8 text-base bg-indigo-600 hover:bg-indigo-700">
                                Search
                            </Button>
                        </form>
                    </CardContent>
                </Card>

                <div className="grid grid-cols-1 lg:grid-cols-4 gap-8">
                    {/* Filters Sidebar */}
                    <div className="space-y-6">
                        <div>
                            <h3 className="font-semibold text-slate-900 mb-4 flex items-center gap-2">
                                <SlidersHorizontal className="h-4 w-4" /> Filters
                            </h3>
                            <div className="space-y-4">
                                <div className="space-y-2">
                                    <label className="text-sm font-medium text-slate-700">Experience Level</label>
                                    <div className="space-y-2">
                                        {['Entry Level', 'Intermediate', 'Expert'].map(level => (
                                            <label key={level} className="flex items-center gap-2 cursor-pointer">
                                                <input type="checkbox" className="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500" />
                                                <span className="text-sm text-slate-600">{level}</span>
                                            </label>
                                        ))}
                                    </div>
                                </div>
                                <div className="w-full h-px bg-slate-200"></div>
                                <div className="space-y-2">
                                    <label className="text-sm font-medium text-slate-700">Client History</label>
                                    <div className="space-y-2">
                                        {['No hires', '1 to 9 hires', '10+ hires'].map(history => (
                                            <label key={history} className="flex items-center gap-2 cursor-pointer">
                                                <input type="checkbox" className="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500" />
                                                <span className="text-sm text-slate-600">{history}</span>
                                            </label>
                                        ))}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Job Listings */}
                    <div className="lg:col-span-3 space-y-4">
                        <div className="flex items-center justify-between mb-2">
                            <h2 className="text-lg font-semibold text-slate-900">
                                {jobs?.total || 0} Jobs Found
                            </h2>
                            <Select defaultValue="newest">
                                <SelectTrigger className="w-[180px] h-9">
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
                            <Card className="border-dashed border-2 shadow-none bg-slate-50/50">
                                <CardContent className="flex flex-col items-center justify-center py-16">
                                    <div className="h-12 w-12 bg-slate-100 rounded-full flex items-center justify-center mb-4">
                                        <Briefcase className="h-6 w-6 text-slate-400" />
                                    </div>
                                    <h3 className="text-lg font-semibold text-slate-900 mb-1">No jobs found</h3>
                                    <p className="text-slate-500 text-sm">Try adjusting your search or filter criteria.</p>
                                    <Button variant="outline" className="mt-4" onClick={() => router.get('/freelance/jobs/browse')}>
                                        Clear Filters
                                    </Button>
                                </CardContent>
                            </Card>
                        ) : (
                            displayJobs.map((job: any) => (
                                <Card key={job.id} className="group hover:border-indigo-200 transition-colors cursor-pointer shadow-sm" onClick={() => router.get(`/freelance/jobs/${job.id}`)}>
                                    <CardContent className="p-6">
                                        <div className="flex flex-col sm:flex-row sm:items-start justify-between gap-4">
                                            <div className="space-y-3 flex-1">
                                                <div className="space-y-1">
                                                    <h3 className="text-lg font-semibold text-indigo-700 group-hover:text-indigo-800 transition-colors">
                                                        {job.title}
                                                    </h3>
                                                    <div className="flex flex-wrap items-center gap-x-4 gap-y-2 text-xs text-slate-500">
                                                        <span className="flex items-center gap-1"><Clock className="h-3.5 w-3.5" /> Posted {formatDate(job.created_at)}</span>
                                                        <span className="flex items-center gap-1"><MapPin className="h-3.5 w-3.5" /> Remote</span>
                                                    </div>
                                                </div>
                                                
                                                <div className="flex items-center gap-3">
                                                    <Badge variant="secondary" className="bg-slate-100 text-slate-700 hover:bg-slate-100 uppercase tracking-wider text-[10px] font-bold">
                                                        {job.type}
                                                    </Badge>
                                                    <span className="text-sm font-semibold text-slate-900 flex items-center gap-0.5">
                                                        <DollarSign className="h-3.5 w-3.5 text-slate-400" />
                                                        {formatMoney(job.budget, job.currency_code)}
                                                        {job.type === 'hourly' && <span className="text-slate-500 font-normal text-xs ml-1">/ hr</span>}
                                                    </span>
                                                </div>

                                                <p className="text-sm text-slate-600 line-clamp-2 leading-relaxed">
                                                    {job.description}
                                                </p>

                                                <div className="flex flex-wrap gap-2 pt-2">
                                                    {job.skills?.map((skill: any) => (
                                                        <Badge key={skill.id} variant="outline" className="bg-slate-50/50 text-slate-600 border-slate-200">
                                                            {skill.name}
                                                        </Badge>
                                                    ))}
                                                </div>
                                            </div>
                                        </div>
                                    </CardContent>
                                </Card>
                            ))
                        )}
                        
                        {jobs?.links && jobs.data.length > 0 && (
                            <div className="flex justify-center mt-8">
                                {/* Simple Pagination or lazy loading trigger could go here */}
                            </div>
                        )}
                    </div>
                </div>
            </div>
        </FreelanceLayout>
    );
}
